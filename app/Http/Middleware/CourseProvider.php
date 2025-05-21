<?php

namespace App\Http\Middleware;

use App\Http\Resources\ErrorResource;
use App\Models\CourseProvider as ModelsCourseProvider;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CourseProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $course_provider = ModelsCourseProvider::where('ref_user_pid', $request->user()->user_pid)->first();

        if ($course_provider->approve_flag == 'Y') {
            return $next($request);
        }

        return (new ErrorResource('Oops! Your provider registration is not approved by admin.'))->response()->setStatusCode(501);
    }
}
