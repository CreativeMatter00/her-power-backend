<?php

namespace App\Http\Middleware;

use App\Http\Resources\ErrorResource;
use App\Models\JobProvider as ModelsJobProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JobProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $job_provider = ModelsJobProvider::where('user_pid', $request->user()->user_pid)->first();
        
        if ($job_provider->approve_flag == 'Y') {
            return $next($request);
        }

        return (new ErrorResource('Oops! Your provider registration is not approved by admin.'))->response()->setStatusCode(501);
    }
}
