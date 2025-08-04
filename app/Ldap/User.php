<?php

namespace App\Ldap;

use LdapRecord\Models\Entry;

class User extends Entry
{
    protected string $guidKey = 'uid';

    public function getContext(): string
    {
        return substr(preg_replace(['/[a-zA-Z]+=/','/,/'], ['.'], $this->getDn()), 1);
    }
}

