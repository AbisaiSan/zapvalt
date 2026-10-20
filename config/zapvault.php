<?php

use App\Support\DataPath;

return [
    'data_path' => DataPath::resolve(env('ZAPVAULT_DATA_PATH')),
];
