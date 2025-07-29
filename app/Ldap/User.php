<?php

namespace App\Ldap;

use LdapRecord\Models\Entry;

class User extends Entry
{
    protected string $guidKey = 'uid';
}
