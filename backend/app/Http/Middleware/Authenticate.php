<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests, don't redirect, just return null
        // The exception handler will convert this to a JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }
        
        return route('login');
    }
}