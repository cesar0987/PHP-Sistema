<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIFEN Configuration - Paraguay
    |--------------------------------------------------------------------------
    |
    | Configuration for the Integrated National Electronic Invoicing System (SIFEN).
    | Based on Technical Manual v150.
    |
    */

    'version' => '150',

    // environment: 'test' or 'production'
    'environment' => env('SIFEN_ENV', 'test'),

    'issuer' => [
        'system_facturation' => 1, // 1=Sistema propio del contribuyente
        'tipo_emision' => 1,      // 1=Normal
        'info_emi' => '1',        // Información de interés del emisor
        'info_fisc' => 'Información de interés del Fisco',
    ],

    'qr' => [
        'base_url' => env('SIFEN_ENV', 'test') === 'production'
            ? 'https://ekuatia.set.gov.py/consultas/qr?'
            : 'https://ekuatia.set.gov.py/consultas-test/qr?',
        
        // CSC (Código Seguro del Contribuyente) provided by SET
        'csc_id' => env('SIFEN_CSC_ID', '0001'),
        'csc_val' => env('SIFEN_CSC_VAL', 'ABCD0000000000000000000000000000'),
    ],

    'certificate' => [
        'path' => storage_path('app/sifen/certificate.p12'),
        'password' => env('SIFEN_CERT_PASSWORD', ''),
    ],
];
