<?php

return [
    'dhcp' => [
        'host' => env('DHCP_HOST', '127.0.0.1'),
        'port' => env('DHCP_PORT', 22),
        'user' => env('DHCP_USER', 'remote'),
        'password' => env('DHCP_PASSWORD', 'remote'),
        'cluster' => [
            'ip' => env('DHCP_CLUSTER_IP', '127.0.0.1')
        ]
    ],
    'dns' => [
        'host' => env('DNS_HOST', '127.0.0.1'),
        'port' => env('DNS_PORT', 22),
        'user' => env('DNS_USER', 'remote'),
        'password' => env('DNS_PASSWORD', 'remote'),
        'cluster' => [
            'ip' => env('DNS_CLUSTER_IP', '127.0.0.1')
        ]
    ],
];
