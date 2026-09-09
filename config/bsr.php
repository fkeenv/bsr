<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin seed credentials
    |--------------------------------------------------------------------------
    |
    | Used by SuperAdminSeeder to upsert the bootstrap Super Admin User Account.
    | Set these in the environment. There is no in-app "create Super Admin" flow.
    |
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
        'mobile_number' => env('SUPER_ADMIN_MOBILE_NUMBER'),
    ],

];
