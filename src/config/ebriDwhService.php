<?php

/*
 * This file is part of the ebriDwhService
 *
 * (c) Andriyanto <me@andriyanto.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default configuration for eBriAutoSync
    |--------------------------------------------------------------------------
    |
    |
    |
    */

    /* Set virtual host development*/
    'localhost' => '192.168.10.10',

    /* Host BRI Data Warehouse */
    'hosts'   => [
        0 => '172.18.41.76',
        1 => '172.18.41.81',
        2 => '172.18.41.82',
    ],

    'sourceFiles' => [
        'dwh_branch'    => '/ReportServer/Pages/ReportViewer.aspx?%2fCR_2005%2fDWH_BRANCH',
        'MIR03'         => '/ReportServer/Pages/ReportViewer.aspx?%2fKeragaan%2fUnit%2fMIR03%2fMIR03_KWL_DETAIL2',
        'LW321'         => '/ReportServer/Pages/ReportViewer.aspx?%2fCR_2005%2fLW321PNSingleRow',
        'GI405'         => '/',
        'S1246'         => '/',
    ],

    'formatFiles'  => [
        'xml'           => '&rs:Format=XML',
        'csv'           => '&rs:Format=CSV',
        'excel'         => '&rs:Format=EXCEL'
    ],

    'storageFile'  => [
        'dwh_branch'   => 'Scheduler/dwh_branch',
        'MIR03'        => 'Scheduler/MIR03',
        'LW321'        => 'Scheduler/LW321'
    ],

    'extension'   => [
        'xml'           => '.xml',
        'csv'           => '.csv',
        'excel'         => '.xls'
    ],

    'storage'     => [
        'MIR03'         => [
            'basePath'       => 'Scheduler/MIR03',
            'data_source'    => 'Scheduler/MIR03/data_source',
            'data_source_zip'=> 'Scheduler/MIR03/data_source_zip',
            'log'            => 'Scheduler/MIR03/log'
        ],
        'LW321'         => [
            'basePath'       => 'Scheduler/LW321',
            'data_source'    => 'Scheduler/LW321/data_source',
            'data_source_zip'=> 'Scheduler/LW321/data_source_zip',
            'log'            => 'Scheduler/LW321/log'
        ],
        'S1246'         => [
            'data_source'    => 'Scheduler/MIR03/data_source',
            'data_source_zip'=> 'Scheduler/MIR03/data_source_zip',
            'log'            => 'Scheduler/MIR03/log'
        ],
        'GI405'         => [
            'data_source'    => 'Scheduler/MIR03/data_source',
            'data_source_zip'=> 'Scheduler/MIR03/data_source_zip',
            'log'            => 'Scheduler/MIR03/log'
        ]
    ],

];
