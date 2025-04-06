<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventParticipantCollection;
use App\Http\Resources\EventParticipantResource;
use App\Models\EventParticipant;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventParticipantController extends Controller
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
        // validation part
        $validator = Validator::make($request->all(), [
            'user_pid'    => 'required',
            'event_pid'   => 'required'
        ]);


        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {

            $is_exist_event = EventParticipant::where('event_pid', $request->event_pid)->where('user_pid', $request->user_pid)->first();

            if ($is_exist_event) {
                return (new EventParticipantResource($is_exist_event, "Participant Already Exist For This Event!", 409))->response()->setStatusCode(409);
            } else {
                try {

                    $user_info = User::with(['customer', 'seller'])->where('user_pid', $request->user_pid)->first();

                    DB::beginTransaction();

                    $house_number = $user_info->customer->house_number ?? $user_info->seller->house_number;
                    $street_name = $user_info->customer->street_name ?? $user_info->seller->street_name;
                    $area_name = $user_info->customer->area_name ?? $user_info->seller->area_name;
                    $city_name = $user_info->customer->city_name ?? $user_info->seller->city_name;
                    $zip_code = $user_info->customer->zip_postal_code ?? $user_info->seller->zip_postal_code;

                    // handle participant information.
                    $insertParticipant = new EventParticipant();
                    $insertParticipant->event_pid           = $request->event_pid;
                    $insertParticipant->ticket_pid          = $request->ticket_pid;
                    $insertParticipant->user_pid            = $request->user_pid;
                    $insertParticipant->participant_name    = $user_info->customer->fname ?? $user_info->seller->fname;
                    $insertParticipant->participant_email   = $user_info->email;
                    $insertParticipant->phone_no            = $user_info->customer->mobile_no ?? $user_info->seller->mobile_no;
                    $insertParticipant->participant_adress  = $house_number . ', ' . $street_name . ', ' . $area_name . ', ' . $city_name . '-' . $zip_code . '.';
                    $insertParticipant->active_status       = $request->active_status ?? 1;
                    // $insertParticipant->remarks             = $request->remarks ?? null;
                    // $insertParticipant->ud_serialno         = $request->ud_serialno;
                    // $insertParticipant->pid_currdate        = $request->pid_currdate;
                    // $insertParticipant->pid_prefix          = $request->pid_prefix;
                    // $insertParticipant->unit_no             = $request->unit_no;
                    // $insertParticipant->cre_by              = Auth::user()->user_pid;
                    $insertParticipant->save();

                    DB::commit();
                    return (new EventParticipantResource($insertParticipant, "Event participant created successfully", 201))->response()->setStatusCode(201);
                } catch (Exception $e) {
                    DB::rollBack();
                    return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
                }
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $user_pid)
    {
        $participant = Eventparticipant::with('event', 'attachments', 'venues', 'eventSchedule', 'tricketInfo', 'notification')
            ->where('user_pid', $user_pid)
            ->where('active_status', 1)
            ->paginate(15);

        if (!$participant) {
            return (new ErrorResource("No participant Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventParticipantCollection($participant, "Participant fetch successfully", 200))->response()->setStatusCode(200);
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
    public function update(Request $request, string $id)
    {
        // validation part
        // $validator = Validator::make($request->all(), [
        //     'event_pid'             => 'required',
        //     'ticket_pid'            => 'required',
        //     'participant_name'      => 'required',
        //     'participant_email'     => 'required',
        //     'phone_no'              => 'required',
        //     'participant_adress'    => 'required',
        // ]);

        // if ($validator->fails()) {
        //     return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        // } else {
        //     try {

        //         DB::beginTransaction();

        //         // handle participant information.
        //         $updateParticipant = EventParticipant::where('participant_pid', $id)->first();
        //         $updateParticipant->event_pid           = $request->event_pid;
        //         $updateParticipant->ticket_pid          = $request->ticket_pid;
        //         $updateParticipant->participant_name    = $request->participant_name;
        //         $updateParticipant->participant_email   = $request->participant_email;
        //         $updateParticipant->phone_no            = $request->phone_no;
        //         $updateParticipant->participant_adress  = $request->participant_adress;
        //         $updateParticipant->remarks             = $request->remarks;
        //         $updateParticipant->active_status       = $request->active_status;
        //         // $updateParticipant->user_pid            = Auth::user()->user_pid; // important for live
        //         // $updateParticipant->ud_serialno         = $request->ud_serialno;
        //         // $updateParticipant->pid_currdate        = $request->pid_currdate;
        //         // $updateParticipant->pid_prefix          = $request->pid_prefix;
        //         // $updateParticipant->unit_no             = $request->unit_no;
        //         // $updateParticipant->upd_by              = Auth::user()->user_pid;
        //         // $updateParticipant->upd_date            = date('Y-m-d H:i:s');
        //         $updateParticipant->update();

        //         DB::commit();
        //         return (new EventParticipantResource($updateParticipant, "Event participant updated successfully", 201))->response()->setStatusCode(201);
        //     } catch (Exception $e) {
        //         DB::rollBack();
        //         return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        //     }
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
