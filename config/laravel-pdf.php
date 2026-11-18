<?php

return [
    /*
     * The default PDF driver. DOMPDF is used because it is pure PHP and
     * needs no Node, Chromium, or external service in production.
     */
    'driver' => env('LARAVEL_PDF_DRIVER', 'dompdf'),
];
