<?php

use App\Support\DataPath;

return [
    'data_path' => DataPath::resolve(env('ZAPVAULT_DATA_PATH')),

    'import' => [
        // Limites de segurança para arquivos .zip.
        'max_uncompressed_bytes' => (int) env('ZAPVAULT_IMPORT_MAX_UNCOMPRESSED', 20 * 1024 ** 3),
        'max_entries' => (int) env('ZAPVAULT_IMPORT_MAX_ENTRIES', 100_000),
        // Razão descompactado/compactado acima da qual uma entrada grande é recusada.
        'max_compression_ratio' => (int) env('ZAPVAULT_IMPORT_MAX_RATIO', 200),
        'ratio_min_bytes' => 1024 * 1024,
        // Pacotes .zip dentro de .zip são abertos até este nível.
        'max_zip_depth' => 2,
        'thumb_size' => 640,
    ],
];
