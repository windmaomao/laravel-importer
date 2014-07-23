<?php namespace QPlot\Importer;

use Illuminate\Support\MessageBag;
use Whoops\Example\Exception;

/**
 * Class Setting
 * @package QPlot\EnvironmentColor
 */
class Importer {

    /**
     * Messages outputted during the import process
     *
     * @var
     */
    private $messages;

    /**
     * Database connection;
     */
    private $db;

    /**
     * Import source folder;
     */
    private $path;

    /**
     * beforeSave Callback
     */
    private $beforeSaveCallback;

    /**
     * Internally store pre processed data
     *
     * @var
     */
    private $data;

    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app=null){
        if(!$app){
            $app = app();   //Fallback when $app is not given
        }
        $this->app = $app;
        $this->messages = new MessageBag();
    }

    /**
     * Import
     */
    public function import($callback = '') {
        $config = $this->app['config'];

        // check path exists
        $this->path = base_path() . '/' . $config->get('importer::config.path');
        if (!file_exists($this->path)) {
            $this->messages->add('error', 'import path does not exist');
            return;
        }

        // check database connection
        $db = $this->app['config']->get('importer::config.database');
        try {
            $this->db = $this->app['db']->connection($db);
        } catch (Exception $e) {
            $this->messages->add('error', 'database connection error for ' . $db);
            return;
        }

        // assign callback
        $this->beforeSaveCallback = $callback;
        if (!is_callable($this->beforeSaveCallback)) {
            $this->messages->add('error', 'callback function is not callable.');
            return;
        }

        // process all imports
        $this->data = [];
        $imports = $config->get('importer::config.imports');
        foreach($imports as $name => &$import) {
            $import['name'] = $name;
            $this->process($import);
        }
    }

    /**
     *
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * Process each import file
     */
    protected function process($import) {
        $changeme = $this->app['hash']->make('changeme');

        // Preset variables
        $mapping = $import['mapping'];
        $unique_field = $import['unique'];
        $Table = str_plural($import['model']); // etc. users
        $Model = ucfirst($import['model']); // etc. User
        $Rules = $import['rules'];
        $data = [];

        // Load file
        $filename = $this->path . '/' . $import['file'];

        if (!file_exists($filename)) {
            $this->messages->add('error', 'File missing: ' . $filename);
        }
        ini_set("auto_detect_line_endings", true);
        $content = file($filename, FILE_IGNORE_NEW_LINES);

        // Load csv and fetch header
        $rows = array_map('str_getcsv', $content);
        $headers = array_shift($rows); // header required

        // Check unique col
        if ($unique_field) {
            if (!isset($mapping[$unique_field])) {
                $this->messages->add('error', 'Unique Column Def missing: ' . $unique_field);
                return;
            }
            $unique_col = $mapping[$unique_field];

            // Check existing rows
            $unique_mapping_index = array_search($unique_col, $headers);
            if (!$unique_mapping_index) {
                $this->messages->add('error', 'Unique Column missing: ' . $unique_field);
                return;
            }
            $unique_cols = array_pluck($rows, $unique_mapping_index);
            $query = call_user_func(
                $Model . '::whereIn',
                $unique_field, $unique_cols
            );
            $data['unique'] = $query->lists($unique_field);
        } else {
            $data['unique'] = [];
        }

        // Check mapping and prepare
        foreach($mapping as $field => $map) {
            if (!is_array($map)) {
                if (!in_array($map, $headers)) {
                    $this->messages->add('error', 'Column missing: ' . $map);
                    return;
                }
            } else {
                // for reference row
                if ($map['type'] == 'reference') {
                    $ref_mapping_index = array_search($map['column'], $headers);
                    if (!$ref_mapping_index) {
                        $this->messages->add('error', 'Unique Column missing: ' . $map['column']);
                        return;
                    }
                    $ref_cols = array_pluck($rows, $ref_mapping_index);
                    $RefModel = ucfirst($map['model']);
                    $query = call_user_func(
                        $RefModel . '::whereIn',
                        $map['foreign_field'], $ref_cols
                    );
                    $result_fields = [$map['foreign_ref']];
                    $result_fields = array_merge($result_fields, [$map['foreign_field']]);
                    $result_fields = array_merge($result_fields, $map['foreign_data']);
                    $results = $query->get($result_fields);
//                        $data[$field] = $query->lists($map['foreign_ref'], $map['foreign_field']);
                    $ref_data = [];
                    foreach($results as $result) {
                        $key = $result[$map['foreign_field']];
                        $ref_data[$key] = $result->toArray();
                    }
                    $data[$field] = $ref_data;
                }
            }
        }

        // Import
        $importing = [];
        $records = [];
        $i = 0;
        foreach($rows as $item) {
            $i++;

            // assemble row
            $row = array_combine($headers, $item);

            // remove non-unique
            if ($unique_field) {
                $unique_value = $row[$unique_col];
                // skip existing one
                if (in_array($unique_value, $data['unique']) || in_array($unique_value, $importing)) {
                    continue;
                }
            } else {
                $unique_value = $i;
            }

            // assemble record
            $record = [];
            foreach($mapping as $field => $map) {
                if (!is_array($map)) {
                    $record[$field] = $row[$map];
                } else {
                    switch($map['type']) {
                        case 'constant':
                            $record[$field] = $map['value'];
                            break;
                        case 'reference':
                            $ref_data = &$data[$field];
                            $value = $row[$map['column']];
                            if (isset($ref_data[$value]))
                            {
                                $record[$field] = $ref_data[$value][$map['foreign_ref']];
                            } else
                            {
                                $this->messages->add('error', 'Reference missing: ' . $unique_field);
                            }
                            break;
                    }
                }
            }

            // call custom function
            call_user_func_array($this->beforeSaveCallback, array($import, &$record, &$data));

            // validate record
            $validator = $this->app['validator'];
            $validation = $validator->make($record, $Rules);
            if ($validation->fails()) {
                foreach($validation->messages()->all() as $msg) {
                    $this->messages->add('error', "[$Model] " . $unique_value . ': ' . $msg);
                }
            } else {
                $importing[] = $unique_value;
                $records[] = $record;
            }
        }
//        Debugbar::info($records);

        if ($records) {
            try {
                $this->db->table($Table)->insert($records);
            } catch(Exception $e) {
                $this->messages->add('error', $e->getMessage());
                return;
            }
        }

        $this->messages->add('info', count($importing) . ' ' . $import['name'] . ' has been imported');
        return;
    }

}