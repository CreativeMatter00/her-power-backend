<?php

namespace App\Http\Middleware;

use App\Http\Resources\ErrorResource;
use App\Models\EventOrganizer as ModelsEventOrganizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventOrganizer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event_org = ModelsEventOrganizer::where('ref_user_pid', $request->user()->user_pid)->first();
        
        if ($event_org->approve_flag == 'Y') {
            return $next($request);
        }

        return (new ErrorResource('Oops! Your organizer registration is not approved by admin.'))->response()->setStatusCode(501);
    }
}
