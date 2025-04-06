<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventParticipantResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\UpcomingEventResource;
use App\Http\Resources\UpcomingEventsCollection;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
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



    public function get_first_eight_of_all()
    {
        $firstEight = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->where('active_status', 1)
            ->orderBy('event_pid', 'desc')
            ->take(8)
            ->get();

        try {
            foreach ($firstEight as $item) {
                $item->attachments->each(function ($attachment) use (&$banner_file_url, &$thumbnail_file_url) {
                    $banner_file_url = $attachment->file_url;
                    $thumbnail_file_url = $attachment->file_url;
                });

                $item->banner_file_url = isset($banner_file_url) ? asset('/public/' . $banner_file_url) : null;
                $item->thumbnail_file_url = isset($thumbnail_file_url) ? asset('/public/' . $thumbnail_file_url) : null;
                unset($item->attachments);
            }
            return (new ApiCommonResponseResource($firstEight, "Event fetch successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    public function get_all()
    {
        $upcomingEvents = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        if (empty($upcomingEvents)) {
            return (new EventResource($upcomingEvents, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventCollection($upcomingEvents, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }



    /**
     * upcomming events.
     */
    public function upcomming_events_firstsix()
    {
        $upcomming_events = Event::select(['event_pid', 'event_title', 'event_desc'])->with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->whereHas('eventSchedule', function ($query) {
                $query->where('start_datetime', '>=', Carbon::now());
            })->where('active_status', 1)
            ->take(6)
            ->get();

        if (count($upcomming_events) == 0) {
            return (new EventResource($upcomming_events, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new UpcomingEventResource($upcomming_events, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }

    public function upcomming_events()
    {
        $upcomming_events = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->whereHas('eventSchedule', function ($query) {
                $query->where('start_datetime', '>=', Carbon::now());
            })->where('active_status', 1)
            ->paginate(10);

        if (empty($upcomming_events)) {
            return (new EventResource($upcomming_events, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventCollection($upcomming_events, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }

    public function upcomming_events_details(string $id)
    {
        $upcomming_events = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->where('event_pid', $id)
            ->where('active_status', 1)
            ->first();

        if (empty($upcomming_events)) {
            return (new ApiCommonResponseResource($upcomming_events, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventResource($upcomming_events, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }


    public function featured_events_firstEight()
    {
        $firstEight = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->whereHas('eventSchedule', function ($query) {
                $query->where('start_datetime', '>=', Carbon::now());
            })
            ->where('featchered_event', 1)
            ->where('active_status', 1)
            ->orderBy('event_pid', 'desc')
            ->take(8)
            ->get();

        try {
            foreach ($firstEight as $item) {
                $item->attachments->each(function ($attachment) use (&$banner_file_url, &$thumbnail_file_url) {
                    $banner_file_url = $attachment->file_url;
                    $thumbnail_file_url = $attachment->file_url;
                });

                $item->banner_file_url = isset($banner_file_url) ? asset('/public/' . $banner_file_url) : null;
                $item->thumbnail_file_url = isset($thumbnail_file_url) ? asset('/public/' . $thumbnail_file_url) : null;
                unset($item->attachments);
            }
            return (new ApiCommonResponseResource($firstEight, "Event fetch successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    public function featured_events()
    {
        $upcomingEvents = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->whereHas('eventSchedule', function ($query) {
                $query->where('start_datetime', '>=', Carbon::now());
            })
            ->where('featchered_event', 1)
            ->where('active_status', 1)
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        if (empty($upcomingEvents)) {
            return (new EventResource($upcomingEvents, "Event fetch successfully.", 401))->response()->setStatusCode(401);
        } else {
            return (new EventCollection($upcomingEvents, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }

    public function post_events_firstSix()
    {
        $pastEvents = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->whereHas('eventSchedule', function ($query) {
                $query->where('start_datetime', '<=', Carbon::now());
            })
            ->where('active_status', 1)
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get();

        try {
            foreach ($pastEvents as $item) {
                $item->attachments->each(function ($attachment) use (&$banner_file_url, &$thumbnail_file_url) {
                    $banner_file_url = $attachment->file_url;
                    $thumbnail_file_url = $attachment->file_url;
                });

                $item->banner_file_url = isset($banner_file_url) ? asset('/public/' . $banner_file_url) : null;
                $item->thumbnail_file_url = isset($thumbnail_file_url) ? asset('/public/' . $thumbnail_file_url) : null;
                unset($item->attachments);
            }
            return (new ApiCommonResponseResource($pastEvents, "Event fetch successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    public function post_events_all()
    {
        $pastEvents = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->whereHas('eventSchedule', function ($query) {
                $query->where('start_datetime', '<=', Carbon::now());
            })
            ->where('active_status', 1)
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        if (empty($pastEvents)) {
            return (new ApiCommonResponseResource($pastEvents, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventCollection($pastEvents, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }


    public function events_by_division(string $code)
    {
        $events = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->whereHas('eventSchedule', function ($query) {
                $query->where('start_datetime', '>=', Carbon::now());
            })
            ->whereHas('venues', function ($query) use ($code) {
                $query->where('division_code', $code);
            })
            ->where('active_status', 1)
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        if (empty($events)) {
            return (new ApiCommonResponseResource($events, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventCollection($events, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }


    public function events_by_month_year(string $month, int $year)
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date("Y-m-t", strtotime($startDate));

        $events = Event::whereHas('eventSchedule', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_datetime', [$startDate, $endDate]);
        })
            ->with(['venues' => function ($query) {
                $query->select('venue_pid', 'venue_pid', 'venue_address');
            }, 'eventSchedule' => function ($query) {
                $query->select('schedule_id', 'event_pid', 'start_datetime');
            }])
            ->select('event_pid', 'event_title')
            ->where('active_status', 1)
            ->paginate(10);

        if ($events->isEmpty()) {
            return (new ApiCommonResponseResource($events, "Event not found!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventCollection($events, "Events fetched successfully", 200))->response()->setStatusCode(200);
        }
    }

    public function my_events(string $user_pid)
    {
        $myEvents = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->where('cre_by', $user_pid)
            ->where('active_status', 1)
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        if (empty($myEvents)) {
            return (new ApiCommonResponseResource($myEvents, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventCollection($myEvents, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }


    public function events_by_organizer(string $org_pid)
    {
        $myEvents = Event::with('eventSchedule', 'venue')
            ->where('org_id', $org_pid)
            ->where('active_status', 1)
            ->orderBy('start_date', 'desc')
            ->get();

        if ($myEvents->isEmpty()) {
            return (new ApiCommonResponseResource($myEvents, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            $formattedEvents = $myEvents->map(function ($event) {
                $today          = date('Y-m-d 00:00:00');
                $firstSchedule  = $event->eventSchedule->first();
                $status         = $firstSchedule ? ($firstSchedule->start_datetime === $today ? 'Today' : ($firstSchedule->start_datetime < $today ? 'Past' : 'Coming up')) : null;
                return [
                    'event_pid'         => $event->event_pid,
                    'event_title'       => $event->event_title,
                    'start_datetime'    => $firstSchedule ? $firstSchedule->start_datetime : null,
                    'status'            => $status,
                    'total_register'    => EventParticipant::where('event_pid', $event->event_pid)->get()->count(),
                    'division_code'     => $event->venue->division_code ?? null,
                ];
            });
            return (new ApiCommonResponseResource($formattedEvents, "Events fetched successfully", 200))->response()->setStatusCode(200);
        }
    }

    public function registration_overview(string $user_pid)
    {
        $start_day = date('Y-m-d 00:00:00');
        $end_day = date('Y-m-d 23:59:59');

        $totalRegistration = EventParticipant::where('active_status', 1)->count();
        $registrationToday = EventParticipant::where('active_status', 1)->whereBetween('cre_date', [$start_day, $end_day])->count();
        $scheduled = EventSchedule::where('active_status', 1)->where('cre_by', $user_pid)->get();

        $scheduled_event = $scheduled->map(function ($event) {
            return [
                'participant'    => EventParticipant::where('event_pid', $event->event_pid)->where('active_status', 1)->get()->count(),
            ];
        });

        $formattedEvents = [
            'tot_registration'      => $totalRegistration,
            'registration_today'    => $registrationToday,
            'scheduled_event'       => $scheduled_event->sum('participant'),
        ];

        if (empty($formattedEvents)) {
            return (new ApiCommonResponseResource($formattedEvents, "Data not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new ApiCommonResponseResource($formattedEvents, "Data fetched successfully", 200))->response()->setStatusCode(200);
        }
    }


    public function perticipant(string $event_pid)
    {
        $participant_info = EventParticipant::select('participant_name', 'participant_email', 'phone_no', 'participant_adress')
            ->where('event_pid', $event_pid)
            ->where('active_status', 1)
            ->get();

        if (empty($participant_info)) {
            return (new ApiCommonResponseResource($participant_info, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new ApiCommonResponseResource($participant_info, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }


    public function search_event(Request $request)
    {
        $input = $request->search;

        $searchEvents = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
            ->where('active_status', 1)
            ->where('event_title', 'LIKE', '%' . $input . '%')
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        if (empty($searchEvents)) {
            return (new ApiCommonResponseResource($searchEvents, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventCollection($searchEvents, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }


    public function event_by_participant(string $id)
    {
        $event = EventParticipant::with('event')->where('participant_pid', $id)->where('active_status', 1)->first();

        if (empty($event)) {
            return (new ApiCommonResponseResource($event, "Event not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventParticipantResource($event, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }

    public function users_of_event(string $id)
    {
        $users = EventParticipant::select('participant_name', 'participant_email', 'phone_no', 'participant_adress')
        ->where('event_pid', $id)
        ->where('active_status', 1)
        ->get();

        if (empty($users)) {
            return (new ApiCommonResponseResource($users, "Users not found !!", 401))->response()->setStatusCode(401);
        } else {
            return (new EventParticipantResource($users, "Users fetch successfully", 200))->response()->setStatusCode(200);
        }
    }
}
