<?php

return [
    // Cache duration for LDAP lookup + authorization decisions (seconds)
    'cache_ttl' => env('RIGHTS_CACHE_TTL', 300),

    // Map: right-key => array of LDAP groups (DNs or CNs)
    // Supports wildcards via middleware ('*' and 'section.*')
    'rights' => [
        // Global admin (access to everything)
        '*' => [
            env('RIGHTS_LDAP_GROUP_ADMIN'),
        ],

        // Flat rights
        'dhcp' => [
            env('RIGHTS_LDAP_GROUP_DHCP'),
        ],
        'dns' => [
            env('RIGHTS_LDAP_GROUP_DNS'),
        ],
        'ldap' => [
            env('RIGHTS_LDAP_GROUP_LDAP'),
        ],

        // Parent right: all generator tools
        'generators.*' => [
            env('RIGHTS_LDAP_GROUP_GENERATORS'),
        ],

        // Generator leaves
        'generators.password' => [
            env('RIGHTS_LDAP_GROUP_GENERATORS.PASSWORD'),
        ],
        'generators.ovirt_serial_number' => [
            env('RIGHTS_LDAP_GROUP_GENERATORS.OVIRT_SERIAL_NUMBER'),
        ],
        'generators.subnetting' => [
            env('RIGHTS_LDAP_GROUP_GENERATORS.SUBNETTING'),
        ],
        'generators.firewall_vorlage' => [
            env('RIGHTS_LDAP_GROUP_GENERATORS.FIREWALL_VORLAGE'),
        ],
    ],

    // How to resolve the LDAP entry for the current Eloquent user
    'lookup' => [
        'ldap_model'     => App\Ldap\User::class,
        'ldap_attr'      => env('RIGHTS_LDAP_ATTR', 'uid'),       // e.g. 'uid' or 'sAMAccountName'
        'eloquent_field' => env('RIGHTS_ELOQUENT_FIELD', 'username'),
    ],
];
