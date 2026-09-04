<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Official Reporting Hierarchy Configuration
    |--------------------------------------------------------------------------
    |
    | Defines which roles report to which parent roles in the official sales line:
    | Branch Manager (BM) → Area Sales Manager (ASM) → Assistant Section Officer (ASO) → Dealers
    |
    | Key: The subordinate role name.
    | Value: The parent reporting manager role name.
    |
    */

    'relations' => [
        'Area Sales Manager (ASM)' => 'Branch Manager (BM)',
        'ASM'                      => 'Branch Manager (BM)',

        'Assistant Section Officer (ASO)' => 'Area Sales Manager (ASM)',
        'ASO'                             => 'Area Sales Manager (ASM)',

        'Dealer' => 'Assistant Section Officer (ASO)',
    ],
];
