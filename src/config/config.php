<?php

use Illuminate\Support\Facades\Config;

return array(

    /*
     |--------------------------------------------------------------------------
     | Import data path
     |--------------------------------------------------------------------------
     |
     | Where all source file is stored
     |
     */

    'path' => 'public/files',

    /*
     |--------------------------------------------------------------------------
     | Database connection
     |--------------------------------------------------------------------------
     |
     | Which database connection
     |
     */

    'database' => 'mysql',

    /*
     |--------------------------------------------------------------------------
     | Import settings
     |--------------------------------------------------------------------------
     |
     | Import settings
     |
     */
    'imports' => array(

        /*
         |--------------------------------------------------------------------------
         | Import name
         |--------------------------------------------------------------------------
         |
         | Import code name
         |
         */
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
        'services'      => [
            'file'      => 'appointments.csv',
            'model'     => 'service',
            'unique'    => 'title',
            'mapping'   => [
                'title'         => 'Service',
                'duration'      => 'Hours',
                'calendar_id'   => [
                    'type'      => 'constant',
                    'value'     => 1,
                ]
            ],
            'rules'     => []
        ],
        'appointments'  => [
            'file'      => 'appointments.csv',
            'model'     => 'appointment',
            'unique'    => '',
            'mapping'   => [
                'kid_id'        => [
                    'column'    => 'Kid Name',
                    'type'      => 'reference',
                    'model'     => 'kid',
                    'foreign_ref'   => 'id',
                    'foreign_field' => 'fullname',
                    'foreign_data'  => []
                ],
                'service_id'        => [
                    'column'    => 'Service',
                    'type'      => 'reference',
                    'model'     => 'service',
                    'foreign_ref'   => 'id',
                    'foreign_field' => 'title',
                    'foreign_data'  => ['duration']
                ],
                'date'          => 'Date',
                'from'          => 'Time',
                'to'            => 'Service'
            ],
            'rules'     => []
        ]

    ),

);
