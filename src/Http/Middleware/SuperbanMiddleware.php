<?php

namespace Edenlife\Superban\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class SuperbanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle($request, Closure $next, $maxRequests, $decayMinutes, $banDuration)
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->isBanned($key)) {
            return  response()->error('You are banned', null, 429);
        }

        if ($this->tooManyAttempts($key, $maxRequests, $decayMinutes, $banDuration)) {
            logger()->info("Rate limit hit for key: $key");
            return  response()->error('Too many attempts', null, 429);
        }

        return $next($request);
    }

    /**
     * @param $request
     * @return string
     */
    protected function resolveRequestSignature($request) : string
    {
        // Using IP, User-Agent, and if available, User ID or Email.
        $userId = auth()->user()?->id ?? 'guest_id';
        $email = auth()->user()?->email ?? 'guest_email';
        return sha1($request->ip() . '|' . $request->header('User-Agent') . '|' . $userId . '|' . $email);
    }

    /**
     * @param $key
     * @param $maxAttempts
     * @param $decayMinutes
     * @param $banDuration
     * @return bool
     */
    protected function tooManyAttempts($key, $maxAttempts, $decayMinutes, $banDuration) : bool
    {
        RateLimiter::for($key, function () use ($maxAttempts, $decayMinutes) {
            return Limit::perMinute($maxAttempts)->by($decayMinutes);
        });

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            if (!$this->isBanned($key)) {
                $this->ban($key, $banDuration);
            }
            return true;
        }

        RateLimiter::hit($key);
        return false;
    }

    /**
     * @param $key
     * @param $minutes
     * @return void
     */
    protected function ban($key, $minutes) : void
    {
        $cache = $this->getCacheStore();
        $cache->put($key . ':banned', true, now()->addMinutes($minutes));
    }

    /**
     * @param $key
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function isBanned($key) : mixed
    {
        $cache = $this->getCacheStore();
        return $cache->get($key . ':banned', false);
    }

    /**
     * @return Repository
     */
    protected function getCacheStore(): Repository
    {
        $default = config('superban.default');
        return Cache::store($default);
    }

}
