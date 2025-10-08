<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnsureLdapRight
{
    /**
     * Usage in routes:
     *   ->middleware('ldap.right:generators.password')
     *   ->middleware('ldap.right:dhcp')
     *   ->middleware('ldap.right:generators.*') // if you want to guard a parent URL
     */
    public function handle(Request $request, Closure $next, string $rightKey)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $groups = $this->groupsForRight($rightKey);

        if (empty($groups)) {
            // Unknown right (or all env values empty) -> hide existence
            abort(404);
        }

        $ttl = (int) config('rights.cache_ttl', 300);

        $allowed = Cache::remember(
            "authz:u:{$user->id}:right:$rightKey",
            now()->addSeconds($ttl),
            fn () => $user->inAnyLdapGroup($groups)
        );

        if (!$allowed) {
            abort(403, 'You are not authorized for this right.');
        }

        return $next($request);
    }

    /**
     * Collect all LDAP groups that grant the given right, including:
     *   '*' (global), 'segment.*' ancestors, and the exact key.
     * For 'generators.password' -> ['*', 'generators.*', 'generators.password'].
     *
     * Filters out null/empty env values automatically.
     *
     * @return array<int, string>
     */
    protected function groupsForRight(string $rightKey): array
    {
        $map = (array) config('rights.rights', []);

        // Build candidate keys (global + hierarchical wildcards + exact)
        $candidates = ['*'];
        $parts = explode('.', $rightKey);
        $accum = '';
        foreach ($parts as $i => $p) {
            $accum = $i === 0 ? $p : ($accum . '.' . $p);
            if ($i < count($parts) - 1) {
                $candidates[] = $accum . '.*';
            }
        }
        $candidates[] = $rightKey;

        // Merge group lists from all candidates
        $groups = [];
        foreach ($candidates as $k) {
            if (isset($map[$k]) && is_array($map[$k])) {
                $groups = array_merge($groups, $map[$k]);
            }
        }

        // Drop null/empty entries and de-dup
        $groups = array_values(array_unique(array_filter($groups, fn ($g) => !empty($g))));

        return $groups;
    }
}
