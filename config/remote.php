<?php

return [
    'dhcp' => [
        'host' => env('DHCP_HOST', '127.0.0.1'),
        'port' => env('DHCP_PORT', 22),
        'user' => env('DHCP_USER', 'remote'),
        'password' => env('DHCP_PASSWORD', 'remote'),
    ],
];
