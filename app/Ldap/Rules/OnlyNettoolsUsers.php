<?php

namespace App\Ldap\Rules;

use Illuminate\Database\Eloquent\Model as Eloquent;
use LdapRecord\Laravel\Auth\Rule;
use LdapRecord\Models\Model as LdapRecord;
use LdapRecord\Models\ActiveDirectory\Group;

class OnlyNettoolsUsers implements Rule
{
    /**
     * Check if the rule passes validation.
     */
    public function passes(LdapRecord $user, Eloquent $model = null): bool
    {
        $allowedGroup = Group::find('cn=YourGroupCN,ou=Groups,dc=example,dc=org');

        return $allowedGroup && $user->groups()->recursive()->exists($allowedGroup);
    }
    }
}
