# Importer for Laravel 4

Importer can import csv files and map column to fields in database based on config file.

    'customers'     => [
        'file'      => 'customers.csv',
        'model'     => 'user',
        'unique'    => 'fullname',
        'mapping'   => [
            'fullname'      => 'Full Name',
            'email'         => 'E-mail',
            'created_at'    => 'Signup Date',
        ],
        'rules'     => [
            'fullname' => 'required',
            'email' => 'required|email|unique:users',
            'username' => 'required',
        ]
    ],
    'kids'          => [
        'file'      => 'appointments.csv',
        'model'     => 'kid',
        'unique'    => 'fullname',
        'mapping'   => [
            'fullname'      => 'Kid Name',
            'parent_id'     => [
                'column'    => 'Full Name',
                'type'      => 'reference',
                'model'     => 'user',
                'foreign_ref'   => 'id',
                'foreign_field' => 'fullname',
                'foreign_data'  => []
            ]
        ],
        'rules'     => []
    ],
 
The idea is that you can just set these settings and then call $importer->import() to get everything imported. 


### Installation

Add package to the `composer.json` file

	"require": {
        "qplot/importer": "dev-master"
        
Then run composer update,
        
    composer update

Add provider `app/config/app.php` file

	'providers' => array(
        'QPlot\Importer\ImporterServiceProvider'
        
To change settings, you need to first publish config file, 

    php artisan config:publish qplot/importer 
        
### Todo

* Make facade
* Make more documentation

### Changelog

#### 0.1.0

- Add service provider
