<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventScheduleCollection;
use App\Http\Resources\EventScheduleResource;
use App\Models\EventSchedule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $schedule_id = $request->query('scheduleId');
        $schedule_pid = $request->query('schedulePid');

        if ($schedule_id && $schedule_pid) {

            $schedule = EventSchedule::where('schedule_id', $schedule_id)
                ->where('schedule_pid', $schedule_pid)
                ->first();

            if (empty($schedule)) {
                return (new ErrorResource("No Schedule Found !!", 404))->response()->setStatusCode(404);
            } else {
                return (new EventScheduleResource($schedule, "Schedule fetch successfully", 200))->response()->setStatusCode(200);
            }
        } elseif ($schedule_id) {

            $schedule = EventSchedule::where('schedule_id', $schedule_id)
                ->orderBy('ud_serialno', 'asc')
                ->paginate(15);

            if (!$schedule) {
                return (new ErrorResource("No Schedule Found !!", 404))->response()->setStatusCode(404);
            } else {
                return (new EventScheduleCollection($schedule, "schedule fetch successfully", 200))->response()->setStatusCode(200);
            }
        } else {
            return (new ErrorResource("No Schedule Found !!", 404))->response()->setStatusCode(404);
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
    public function store(Request $request)
    {
        // validation part
        $validator = Validator::make($request->all(), [
            'event_pid'     => 'required',
            'event_desc'    => 'required',
            'pid_currdate'  => 'required',
            'pid_prefix'    => 'required'
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle schedule information.
                $insertSchedule = new EventSchedule();
                $insertSchedule->event_pid      = $request->event_pid;
                $insertSchedule->event_desc     = $request->event_desc;
                $insertSchedule->start_datetime = $request->start_datetime;
                $insertSchedule->end_datetime   = $request->end_datetime;
                $insertSchedule->ud_serialno    = $request->ud_serialno;
                $insertSchedule->remarks        = $request->remarks;
                $insertSchedule->pid_currdate   = $request->pid_currdate;
                $insertSchedule->pid_prefix     = $request->pid_prefix;
                $insertSchedule->cre_by         = Auth::user()->user_pid;
                $insertSchedule->active_status  = $request->active_status;
                $insertSchedule->unit_no        = $request->unit_no;
                $insertSchedule->save();

                DB::commit();
                return (new EventScheduleResource($insertSchedule, "Event schedule created successfully", 201))->response()->setStatusCode(201);
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
        // validation part
        $validator = Validator::make($request->all(), [
            'event_pid'     => 'required',
            'event_desc'    => 'required',
            'pid_currdate'  => 'required',
            'pid_prefix'    => 'required'
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle schedule information.
                $insertSchedule = EventSchedule::where('schedule_pid', $id)->first();
                $insertSchedule->event_pid      = $request->event_pid;
                $insertSchedule->event_desc     = $request->event_desc;
                $insertSchedule->start_datetime = $request->start_datetime;
                $insertSchedule->end_datetime   = $request->end_datetime;
                $insertSchedule->ud_serialno    = $request->ud_serialno;
                $insertSchedule->remarks        = $request->remarks;
                $insertSchedule->pid_currdate   = $request->pid_currdate;
                $insertSchedule->pid_prefix     = $request->pid_prefix;
                $insertSchedule->upd_date       = date('Y-m-d H:i:s');
                $insertSchedule->upd_by         = Auth::user()->user_pid;
                $insertSchedule->active_status  = $request->active_status;
                $insertSchedule->unit_no        = $request->unit_no;
                $insertSchedule->update();

                DB::commit();
                return (new EventScheduleResource($insertSchedule, "Event schedule updated successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
