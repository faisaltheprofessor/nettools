<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use LdapRecord\Models\Model as LdapRecordModel;
use App\Ldap\User as LdapEntryModel;

class User extends Authenticatable implements LdapAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use AuthenticatesWithLdap, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* =========================================================================
     | LDAP helpers for rights (no caching)
     |=========================================================================*/

    /**
     * Resolve this Eloquent user to its LDAP entry using your App\Ldap\User model
     * and the configured attribute mapping. No caching.
     */
    public function ldapEntry(): ?LdapRecordModel
    {
        $ldapModel   = config('rights.lookup.ldap_model', LdapEntryModel::class);
        $ldapAttr    = config('rights.lookup.ldap_attr', 'uid');
        $eloquentCol = config('rights.lookup.eloquent_field', 'username');

        $value = $this->{$eloquentCol} ?? null;
        if (!$ldapModel || !$value) {
            return null;
        }

        /** @var \LdapRecord\Models\Model $model */
        $model = new $ldapModel();

        return $model::query()
            ->whereEquals($ldapAttr, $value)
            ->first();
    }

    /**
     * Collect group DNs from common attributes / relations across AD & eDirectory.
     */
    protected function ldapGroupDns(): array
    {
        $entry = $this->ldapEntry();
        if (!$entry) {
            return [];
        }

        // Attribute-based memberships:
        $memberOf        = (array) ($entry->getAttribute('memberOf') ?? []);         // AD
        $groupMembership = (array) ($entry->getAttribute('groupMembership') ?? []);  // eDirectory

        $dns = array_values(array_unique(array_filter([
            ...$memberOf,
            ...$groupMembership,
        ])));

        // Relation-based (if available):
        try {
            if (method_exists($entry, 'groups')) {
                $relatedDns = $entry->groups()->get(['dn'])->pluck('dn')->all();
                $dns = array_values(array_unique([...$dns, ...$relatedDns]));
            }
        } catch (\Throwable $ignored) {
            // ignore if relation not supported
        }

        return array_map('strval', $dns);
    }

    /**
     * Check membership in a group by DN or CN. No caching.
     * - DN: try inGroup($dn, true) if available; else compare membership DNs.
     * - CN: match if any membership DN contains "CN=<cn>," (case-insensitive).
     */
    public function inLdapGroup(string $groupDnOrCn): bool
    {
        $entry = $this->ldapEntry();
        if (!$entry) {
            return false;
        }

        $isDn = str_contains($groupDnOrCn, '=');

        if ($isDn && method_exists($entry, 'inGroup')) {
            try {
                return (bool) $entry->inGroup($groupDnOrCn, true);
            } catch (\Throwable $e) {
                // fall through to attribute comparison
            }
        }

        $memberships = array_map('strtolower', $this->ldapGroupDns());

        if ($isDn) {
            return in_array(strtolower($groupDnOrCn), $memberships, true);
        }

        // CN check (best effort)
        $needle = 'cn=' . strtolower($groupDnOrCn) . ',';
        foreach ($memberships as $dn) {
            if (str_contains($dn, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True if the user is in ANY of the given groups (DNs or CNs). No caching.
     *
     * @param array<int, string> $groups
     */
    public function inAnyLdapGroup(array $groups): bool
    {
        foreach ($groups as $g) {
            if ($this->inLdapGroup($g)) {
                return true;
            }
        }
        return false;
    }
}
