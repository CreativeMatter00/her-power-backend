<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\DestroyResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventByIdResource;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventScheduleResource;
use App\Models\Attachment;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\EventParticipant;
use App\Models\EventSchedule;
use App\Models\EventVenue;
use App\Models\TricketPayment;
use App\Service\ImageUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EventController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event = Event::with('venues:venue_pid,venue_name,venue_title,capacity,venue_address,per_day_rent')
            ->where('active_status', 1)
            ->orderBy('ud_serialno', 'asc')
            ->get();

        if (!$event) {
            return (new ErrorResource("No event Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new ApiCommonResponseResource($event, "event fetch successfully", 200))->response()->setStatusCode(200);
        }
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
    public function store(Request $request, ImageUploadService $imageUploadService)
    {
        // validation part
        $validator = Validator::make($request->all(), [
            'event_title'   => 'required',
            'event_desc'    => 'max:1000',
            'banner'        => 'required|image|mimes:jpg,png',
            'thumbnail'     => 'required|image|mimes:jpg,png',
        ]);

        if ($validator->fails()) {
            // Dynamically get all the errors for the fields in the rules
            $msg = collect($validator->errors()->messages())->flatten()->filter()->values()->toArray();
            return $this->sendError($msg, 400)->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                // handle venue information.
                $insertEvent = new Event();
                // $insertEvent->event_name     = $request->event_name;
                $insertEvent->event_title       = $request->event_title;
                $insertEvent->event_desc        = $request->event_desc;
                $insertEvent->category_pid      = $request->category_pid;
                $insertEvent->featchered_event  = $request->featured_event == 'true' ? 1 : 0;

                // $insertEvent->banner_image  = $request->banner_image;
                // $insertEvent->thumbnail_image  = $request->thumbnail_image;

                /************** Location Section **************/
                if (!isset($request->virtual_event)) {
                    $insertEvent->virtual_event = '0';
                    $insertEvent->venue_pid = $request->venue_pid;
                    // $insertEvent->vanue_name    = $request->vanue_name;
                    // $insertEvent->vanue_area    = $request->vanue_area;
                    // $insertEvent->vanue_city    = $request->vanue_city;
                    // $insertEvent->zip_code      = $request->zip_code;
                } else {
                    $insertEvent->virtual_event = '1';
                    $insertEvent->venue_pid = null;
                }

                /************** Tricket Section **************/
                $request->ticket_type === 'F' ? $insertEvent->ticket_type = 'F' : $insertEvent->ticket_type = 'P';

                // $insertEvent->start_date    = date("Y-m-d", strtotime($request->start_date));
                // $insertEvent->end_date      = date("Y-m-d", strtotime($request->end_date));
                // $insertEvent->cre_by        = Auth::user()->user_pid;
                $insertEvent->tage          = $request->tags;
                $insertEvent->remarks       = $request->remarks;
                $insertEvent->org_id        = $request->org_pid;
                $insertEvent->save();

                $event_pid = Event::where('event_id', $insertEvent->event_id)->pluck('event_pid')->first();


                // /************** DateTime Section **************/
                try {
                    DB::beginTransaction();

                    $scheduleData = [];

                    // Helper function to prepare schedule data
                    function prepareScheduleData($event_pid, $data)
                    {
                        return [
                            'event_pid'      => $event_pid,
                            'event_desc'     => $data->event_desc ?? null,
                            'start_datetime' => date("Y-m-d", strtotime($data->start_datetime)),
                            'end_datetime'   => date("Y-m-d", strtotime($data->end_datetime)),
                            'from_time'      => date("H:i:s", strtotime($data->from_time)),
                            'to_time'        => date("H:i:s", strtotime($data->to_time)),
                            'segment_name'   => $data->segment_name ?? null,
                            'speaker_pid'    => $data->speaker_pid ?? null,
                            // 'ud_serialno'    => $data->ud_serialno ?? null,
                            // 'remarks'        => $data->remarks ?? null,
                            // 'pid_currdate'   => $data->pid_currdate ?? null,
                            // 'pid_prefix'     => $data->pid_prefix ?? null,
                            // 'cre_by'        => $data->cre_by ?? Auth::user()->user_pid,
                            // 'active_status'  => $data->active_status ?? null,
                            // 'unit_no'        => $data->unit_no ?? null,
                        ];
                    }

                    if (!isset($request->breakdown) && !isset($request->multidate)) {
                        $single_day = json_decode($request->singleday);
                        $scheduleData[] = prepareScheduleData($event_pid, $single_day);
                    } elseif (!isset($request->breakdown) && isset($request->multidate)) {
                        $multidate = json_decode($request->multidate);
                        foreach ($multidate as $day) {
                            $scheduleData[] = prepareScheduleData($event_pid, $day);
                        }
                    } elseif (isset($request->breakdown) && !isset($request->multidate)) {
                        $breakdown = json_decode($request->breakdown);
                        foreach ($breakdown as $day) {
                            $scheduleData[] = prepareScheduleData($event_pid, $day);
                        }
                    }

                    foreach ($scheduleData as $data) {
                        $insertSchedule = new EventSchedule($data);
                        $insertSchedule->save();
                    }

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                }



                // /************** Tricket Section **************/
                if ($request->ticket_type === 'P') {
                    try {
                        DB::beginTransaction();
                        $tickets = json_decode($request->tickets);
                        foreach ($tickets as $ticket) {
                            $insertTricketPayment = new TricketPayment();
                            $insertTricketPayment->event_pid          = $event_pid;
                            $insertTricketPayment->ticket_name      = $ticket->ticket_name;
                            $insertTricketPayment->ticket_amount      = $ticket->ticket_amount;
                            $insertTricketPayment->remarks            = $ticket->remarks;
                            // $insertTricketPayment->transaction_id     = $ticket->transaction_id;
                            // $insertTricketPayment->participant_pid    = $ticket->participant_pid;
                            // $insertTricketPayment->event_name         = $ticket->event_name;
                            // $insertTricketPayment->paymentid          = $ticket->paymentid;
                            // $insertTricketPayment->payment_date       = $ticket->payment_date;
                            // $insertTricketPayment->payment_method     = $ticket->payment_method;
                            // $insertTricketPayment->ud_serialno        = $ticket->ud_serialno;
                            // $insertTricketPayment->pid_currdate       = $ticket->pid_currdate;
                            // $insertTricketPayment->pid_prefix         = $ticket->pid_prefix;
                            // $insertTricketPayment->cre_by             = Auth::user()->user_pid;
                            // $insertTricketPayment->unit_no            = $ticket->unit_no;
                            $insertTricketPayment->save();
                        }
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('Error saving event ticket: ' . $e->getMessage());
                    }
                }

                // /************** Notification Section **************/
                try {
                    if ($request->venue_pid != '') {
                        $venue_name = EventVenue::where('venue_pid', $request->venue_pid)->pluck('venue_name')->first();
                    } else {
                        $venue_name = $request->vanue_name;
                    }

                    DB::beginTransaction();
                    $insertNotification = new EventNotification();
                    $insertNotification->event_pid                  = $event_pid;
                    $insertNotification->notification_vanue         = $venue_name;
                    $insertNotification->notification_media         = $request->notification_type;
                    $insertNotification->notification_days          = $request->notification_schedule;
                    // $insertNotification->short_name              = $request->short_name;
                    // $insertNotification->notification_timefrom   = $request->notification_timefrom;
                    // $insertNotification->notification_timeto     = $request->notification_timeto;
                    // $insertNotification->ud_serialno             = $request->ud_serialno;
                    // $insertNotification->remarks                 = $request->remarks;
                    // $insertNotification->pid_currdate            = $request->pid_currdate;
                    // $insertNotification->pid_prefix              = $request->pid_prefix;
                    // $insertNotification->cre_by                     = Auth::user()->user_pid;
                    // $insertNotification->active_status           = $request->active_status;
                    // $insertNotification->unit_no                 = $request->unit_no;
                    $insertNotification->save();
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                }

                // banner
                $banner_directory = 'attachments/event_banner/' . now()->format('Ymd') . '/';
                $storeBanImage = $imageUploadService->uploadEventBannerImage($request, $request->event_title, $banner_directory, $event_pid, "events_banner");
                if ($storeBanImage != 200) {
                    return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Event Image Upload');
                }

                // thumnail
                $thumbnail_directory = 'attachments/event_thumbnail/' . now()->format('Ymd') . '/';
                $storeThumImage = $imageUploadService->uploadEventThumnailImage($request, $request->event_title, $thumbnail_directory, $event_pid, "events_thumbnail");
                if ($storeThumImage != 200) {
                    return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Event Image Upload');
                }

                DB::commit();

                $new_event = Event::with('attachments')->where('event_id', $insertEvent->event_id)->first();

                return (new EventResource($new_event, "Event created successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $event = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->where('event_pid', $id)
            ->where('active_status', 1)
            ->first();

        if (empty($event)) {
            return (new ErrorResource("No Event Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventResource($event, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
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
    public function update(Request $request, string $id, ImageUploadService $imageUploadService)
    {
        try {
            DB::beginTransaction();

            $updateEvent = Event::where('event_pid', $id)->first();

            if (!$updateEvent) {
                return (new ErrorResource("No Event Found !!", 404))->response()->setStatusCode(404);
            }

            // Update event details
            $updateEvent->event_title       = $request->event_title ?? $updateEvent->event_title;
            $updateEvent->event_desc        = $request->event_desc ?? $updateEvent->event_desc;
            $updateEvent->category_pid      = $request->category_pid ?? $updateEvent->category_pid;
            $updateEvent->featchered_event  = isset($request->featured_event) ? ($request->featured_event == 'true' ? 1 : 0) : $updateEvent->featchered_event;

            if (!isset($request->virtual_event)) {
                $updateEvent->virtual_event = '0';
                $updateEvent->venue_pid = $request->venue_pid ?? $updateEvent->venue_pid;
            } else {
                $updateEvent->virtual_event = '1';
                $updateEvent->venue_pid = null;
            }

            $updateEvent->ticket_type = $request->ticket_type ?? $updateEvent->ticket_type;
            $updateEvent->tage        = $request->tags ?? $updateEvent->tage;
            $updateEvent->remarks     = $request->remarks ?? $updateEvent->remarks;
            $updateEvent->org_id      = $request->org_pid ?? $updateEvent->org_id;

            $updateEvent->update();

            $event_pid = $updateEvent->event_pid;

            // Update schedule data
            if ($request->has('singleday') || $request->has('multidate') || $request->has('breakdown')) {

                if (!isset($request->breakdown) && !isset($request->multidate)) {
                    $singleday = json_decode($request->singleday);
                    $updateSchedule = EventSchedule::where('schedule_pid', $singleday->schedule_pid)->where('event_pid', $event_pid)->first();
                    $updateSchedule->event_desc     = $singleday->event_desc ?? null;
                    $updateSchedule->start_datetime = date("Y-m-d", strtotime($singleday->start_datetime));
                    $updateSchedule->end_datetime   = date("Y-m-d", strtotime($singleday->end_datetime));
                    $updateSchedule->from_time      = date("H:i:s", strtotime($singleday->from_time));
                    $updateSchedule->to_time        = date("H:i:s", strtotime($singleday->to_time));
                    $updateSchedule->update();
                } elseif (!isset($request->breakdown) && !isset($request->singleday)) {
                    $multidate = json_decode($request->multidate);
                    for ($i = 0; $i < count($multidate); $i++) {
                        if ($multidate[$i]->schedule_pid) {
                            $updateSchedule = EventSchedule::where('schedule_pid', $multidate[$i]->schedule_pid)->where('event_pid', $event_pid)->first();
                            $updateSchedule->event_desc     = $multidate[$i]->event_desc ?? null;
                            $updateSchedule->start_datetime = date("Y-m-d", strtotime($multidate[$i]->start_datetime));
                            $updateSchedule->end_datetime   = date("Y-m-d", strtotime($multidate[$i]->end_datetime));
                            $updateSchedule->from_time      = date("H:i:s", strtotime($multidate[$i]->from_time));
                            $updateSchedule->to_time        = date("H:i:s", strtotime($multidate[$i]->to_time));
                            $updateSchedule->update();
                        } else {
                            $insertSchedule = new EventSchedule();
                            $insertSchedule->event_desc     = $multidate[$i]->event_desc ?? null;
                            $insertSchedule->start_datetime = date("Y-m-d", strtotime($multidate[$i]->start_datetime));
                            $insertSchedule->end_datetime   = date("Y-m-d", strtotime($multidate[$i]->end_datetime));
                            $insertSchedule->from_time      = date("H:i:s", strtotime($multidate[$i]->from_time));
                            $insertSchedule->to_time        = date("H:i:s", strtotime($multidate[$i]->to_time));
                            $insertSchedule->save();
                        }
                    }
                } elseif (!isset($request->singleday) && !isset($request->multidate)) {
                    $breakdown = json_decode($request->breakdown);
                    for ($i = 0; $i < count($breakdown); $i++) {
                        if ($breakdown[$i]->schedule_pid) {
                            $updateSchedule = EventSchedule::where('schedule_pid', $breakdown[$i]->schedule_pid)->where('event_pid', $event_pid)->first();
                            $updateSchedule->event_desc     = $breakdown[$i]->event_desc ?? null;
                            $updateSchedule->start_datetime = date("Y-m-d", strtotime($breakdown[$i]->start_datetime));
                            $updateSchedule->end_datetime   = date("Y-m-d", strtotime($breakdown[$i]->end_datetime));
                            $updateSchedule->from_time      = date("H:i:s", strtotime($breakdown[$i]->from_time));
                            $updateSchedule->to_time        = date("H:i:s", strtotime($breakdown[$i]->to_time));
                            $updateSchedule->segment_name   = $breakdown[$i]->segment_name ?? null;
                            $updateSchedule->speaker_pid    = $breakdown[$i]->speaker_pid ?? null;
                            $updateSchedule->update();
                        } else {
                            $insertSchedule = new EventSchedule();
                            $insertSchedule->event_desc     = $breakdown[$i]->event_desc ?? null;
                            $insertSchedule->start_datetime = date("Y-m-d", strtotime($breakdown[$i]->start_datetime));
                            $insertSchedule->end_datetime   = date("Y-m-d", strtotime($breakdown[$i]->end_datetime));
                            $insertSchedule->from_time      = date("H:i:s", strtotime($breakdown[$i]->from_time));
                            $insertSchedule->to_time        = date("H:i:s", strtotime($breakdown[$i]->to_time));
                            $insertSchedule->segment_name   = $breakdown[$i]->segment_name ?? null;
                            $insertSchedule->speaker_pid    = $breakdown[$i]->speaker_pid ?? null;
                            $insertSchedule->save();
                        }
                    }
                }
            }

            // Update ticket data
            if ($request->ticket_type === 'P' && $request->has('tickets')) {
                $tickets = json_decode($request->tickets);
                for ($i = 0; $i < count($tickets); $i++) {
                    if ($tickets[$i]->ticket_pid) {
                        $updateTricketPayment = TricketPayment::where('ticket_pid', $tickets[$i]->ticket_pid)->where('event_pid', $event_pid)->first();
                        $updateTricketPayment->ticket_name    = $tickets[$i]->ticket_name;
                        $updateTricketPayment->ticket_amount  = $tickets[$i]->ticket_amount;
                        $updateTricketPayment->remarks        = $tickets[$i]->facilities;
                        $updateTricketPayment->update();
                    } else {
                        $updateTricketPayment = new TricketPayment();
                        $updateTricketPayment->ticket_name    = $tickets[$i]->ticket_name;
                        $updateTricketPayment->ticket_amount  = $tickets[$i]->ticket_amount;
                        $updateTricketPayment->remarks        = $tickets[$i]->facilities;
                        $updateTricketPayment->save();
                    }
                }
            }

            // Update notification data
            if ($request->has('notification_type') && $request->has('notification_schedule')) {
                $venue_name = $request->venue_pid ? EventVenue::where('venue_pid', $request->venue_pid)->pluck('venue_name')->first() : $request->vanue_name;

                $updateNotification = EventNotification::where('event_pid', $event_pid)->where('notification_pid', $request->notification_pid)->first();
                $updateNotification->notification_vanue = $venue_name;
                $updateNotification->notification_media = $request->notification_type;
                $updateNotification->notification_days  = $request->notification_schedule;
                $updateNotification->update();
            }

            // banner
            if ($request->hasFile('banner')) {
                Attachment::where('ref_pid', $event_pid)->where('ref_object_name', 'events_banner')->delete();
                $banner_directory = 'attachments/event_banner/' . now()->format('Ymd') . '/';
                $storeBanImage = $imageUploadService->uploadEventBannerImage($request, $request->event_title, $banner_directory, $event_pid, "events_banner");
                if ($storeBanImage != 200) {
                    return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Event Image Upload');
                }
            }

            // thumnail
            if ($request->hasFile('thumbnail')) {
                Attachment::where('ref_pid', $event_pid)->where('ref_object_name', 'events_thumbnail')->delete();
                $thumbnail_directory = 'attachments/event_thumbnail/' . now()->format('Ymd') . '/';
                $storeThumImage = $imageUploadService->uploadEventThumnailImage($request, $request->event_title, $thumbnail_directory, $event_pid, "events_thumbnail");
                if ($storeThumImage != 200) {
                    return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Event Image Upload');
                }
            }

            DB::commit();

            $updated_event = Event::with('attachments')->where('event_pid', $event_pid)->first();

            return (new EventResource($updated_event, "Event updated successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            DB::rollBack();
            // return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            return (new ErrorResource($e->getMessage(), 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->where('event_pid', $id)
            ->where('active_status', 1)
            ->first();

        if ($event) {
            $event->update([
                'active_status' => 0
            ]);
            return (new DestroyResource($event, "Event Deleted successfully", 200))->response()->setStatusCode(200);
        } else {
            return (new ErrorResource("No Event Found !!", 404))->response()->setStatusCode(404);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show_event(string $event_pid, string $user_pid = null)
    {
        $is_exist_event = EventParticipant::where('event_pid', $event_pid)
            ->where('user_pid', $user_pid)
            ->where('active_status', 1)
            ->first() ?? null;

        $event_info = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->where('event_pid', $event_pid)
            ->where('active_status', 1)
            ->first();

        if (empty($event_info)) {
            return (new ErrorResource("No Event Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventByIdResource($event_info, $is_exist_event, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }
}
