<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;
use App\Support\FeatureFlags;

class Authenticate extends Middleware
{
    public function handle($request, \Closure $next, ...$guards)
    {
        if (FeatureFlags::useMock()) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return $this->guardRedirect($request, route('login'));
        }
    }

    private function guardRedirect($request, string $target): ?string
    {
        $key = '_redirect_loop_guard';
        $state = $request->session()->get($key, []);
        $lastTarget = is_array($state) ? ($state['target'] ?? null) : null;
        $lastAt = is_array($state) ? (int) ($state['at'] ?? 0) : 0;
        $now = time();

        if ($lastTarget === $target && ($now - $lastAt) < 5) {
            Log::warning('REDIRECT_LOOP_ABORT target=' . $target);
            abort(409, 'Redirect loop detected');
        }

        Log::info('REDIRECT_TARGET target=' . $target);
        $request->session()->put($key, ['target' => $target, 'at' => $now]);
        return $target;
    }
}
