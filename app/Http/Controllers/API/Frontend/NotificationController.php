<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\NotificationResource;
use App\Models\Event;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /**
     * @ upcomming events.
     */
    public function recentNotifications($id)
    {
        $recentNotifications = Notification::where('active_status', 1)->where('customer_pid', $id)->orderBy('notification_id', 'desc')->take(4)->get();

        if (count($recentNotifications) == 0) {
            return (new NotificationResource($recentNotifications, "Notifications not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new NotificationResource($recentNotifications, "Notifications fetched successfully", 200))->response()->setStatusCode(200);
        }
    }

    public function allNotifications($id)
    {
        $allNotifications = Notification::where('active_status', 1)->where('customer_pid', $id)->orderBy('notification_id', 'desc')->get();

        if (count($allNotifications) == 0) {
            return (new NotificationResource($allNotifications, "Notifications not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new NotificationResource($allNotifications, "Notifications fetched successfully", 200))->response()->setStatusCode(200);
        }
    }
}
