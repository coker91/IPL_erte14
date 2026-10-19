<?php

return [
    'opening_date' => env('IPL_OPENING_DATE', '2026-10-15'),
    'source_file' => env('IPL_SOURCE_FILE', database_path('seeders/data/source-private.json')),
    'demo_password' => env('IPL_DEMO_PASSWORD'),
    'arrears_threshold' => 3,
];
