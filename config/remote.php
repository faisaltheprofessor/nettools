<?php

return [
    'dhcp' => [
        'port' => env('DHCP_PORT', 22),
        'user' => env('DHCP_USER', 'remote'),
        'password' => env('DHCP_PASSWORD', 'remote'),
        'cluster' => [
            'host' => env('DHCP_HOST', '127.0.0.1'),
            'ip' => env('DHCP_CLUSTER_IP', '127.0.0.1'),
        ],
    ],
    'dns' => [
        'port' => env('DNS_PORT', 22),
        'user' => env('DNS_USER', 'remote'),
        'password' => env('DNS_PASSWORD', 'remote'),
        'cluster' => [
            'host' => env('DNS_HOST', '127.0.0.1'),
            'ip' => env('DNS_CLUSTER_IP', '127.0.0.1'),
        ],
    ],
];
