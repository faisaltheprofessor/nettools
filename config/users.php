<?php

return [
    'admins' => env('ADMIN_USERS', ''),
    'ldap_raw' => array_filter(array_map('trim', explode(',', env('LDAP_RAW_ACCESS', '')))),
    'LDAP' => [
        'group' => env('LDAP_GROUP_NAME', ''),
    ],
];
