<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Date formats
    |--------------------------------------------------------------------------
    |
    | The date formats a school can choose from when displaying dates. The key
    | is the PHP date format, the value is the human readable preview.
    |
    */

    'date_formats' => [
        'd/m/Y' => 'DD/MM/YYYY',
        'd-m-Y' => 'DD-MM-YYYY',
        'm/d/Y' => 'MM/DD/YYYY',
        'Y-m-d' => 'YYYY-MM-DD',
        'd M Y' => '01 Jan 2026',
        'jS F Y' => '1st January 2026',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default school settings
    |--------------------------------------------------------------------------
    |
    | Fallback values used before a school has saved its own settings.
    |
    */

    'defaults' => [
        'school.name' => 'School Management System',
        'school.short_name' => 'SMS',
        'school.email' => null,
        'school.phone' => null,
        'school.address' => null,
        'school.timezone' => 'UTC',
        'school.date_format' => 'd/m/Y',
        'school.logo_path' => null,
    ],

];
