<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnsureLdapRight
{
    public function handle(Request $request, Closure $next, string $rightKey)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $groups = $this->groupsForRight($rightKey);
        if (empty($groups)) {
            abort(404);
        }

        $ttl = (int) config('rights.cache_ttl', 300);

        $compute = fn () => $user->inAnyLdapGroup($groups);

        // If TTL <= 0: compute fresh every request
        if ($ttl <= 0) {
            $allowed = $compute();
        } else {
            $cacheKey = "authz:u:{$user->id}:right:$rightKey";
            // Prefer tags if supported
            try {
                $allowed = Cache::tags(['authz', "user:{$user->id}"])->remember(
                    $cacheKey,
                    now()->addSeconds($ttl),
                    $compute
                );
            } catch (\Throwable) {
                // Fallback without tags
                $allowed = Cache::remember(
                    $cacheKey,
                    now()->addSeconds($ttl),
                    $compute
                );
            }
        }

        if (! $allowed) {
            abort(403, 'You are not authorized for this right.');
        }

        return $next($request);
    }

    protected function groupsForRight(string $rightKey): array
    {
        $map = (array) config('rights.rights', []);

        $candidates = ['*'];
        $parts = explode('.', $rightKey);
        $accum = '';
        foreach ($parts as $i => $p) {
            $accum = $i === 0 ? $p : ($accum.'.'.$p);
            if ($i < count($parts) - 1) {
                $candidates[] = $accum.'.*';
            }
        }
        $candidates[] = $rightKey;

        $groups = [];
        foreach ($candidates as $k) {
            if (isset($map[$k]) && is_array($map[$k])) {
                $groups = array_merge($groups, $map[$k]);
            }
        }

        return array_values(array_unique(array_filter($groups, fn ($g) => ! empty($g))));
    }
}
