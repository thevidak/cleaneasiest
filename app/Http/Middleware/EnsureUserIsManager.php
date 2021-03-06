<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        $user = Auth::user();

        if ($user->userRole() == Role::MANAGER) {
            return $next($request);
        }
        else {
            return response([
                'error' => 'Unathorized'
            ]);
        }
    }
}
