<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventOrganizerCollection;
use App\Http\Resources\EventOrganizerResource;
use App\Mail\AdminApprovalMail;
use App\Models\EventOrganizer;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EventOrganizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $org = EventOrganizer::where('active_status', 1)
            ->orderBy('ud_serialno', 'asc')
            ->paginate(10);

        if (!$org) {
            return (new ErrorResource("No org Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventOrganizerCollection($org, "org fetch successfully", 200))->response()->setStatusCode(200);
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
            'org_name'     => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle venue information.
                $insertOrg = new EventOrganizer();
                $insertOrg->org_name         = $request->org_name;
                $insertOrg->org_email        = $request->org_email;
                $insertOrg->phone_no         = $request->phone_no;
                $insertOrg->org_address      = $request->org_address;
                $insertOrg->remarks          = $request->remarks;
                $insertOrg->active_status    = $request->active_status;
                $insertOrg->designation      = $request->designation;
                $insertOrg->org_type         = $request->org_type;
                $insertOrg->org_website      = $request->org_website;
                $insertOrg->ref_user_pid     = $request->ref_user_pid;
                // $insertOrg->ud_serialno      = $request->ud_serialno;
                // $insertOrg->pid_currdate     = $request->pid_currdate;
                // $insertOrg->pid_prefix       = $request->pid_prefix;
                // $insertOrg->cre_by           = Auth::user()->user_pid;
                // $insertOrg->unit_no          = $request->unit_no;
                $insertOrg->save();

                DB::commit();
                return (new EventOrganizerResource($insertOrg, "Organizer created successfully", 201))->response()->setStatusCode(201);
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
        $org = EventOrganizer::select('org_pid', 'org_name', 'org_address', 'designation', 'org_type', 'org_website', 'ref_user_pid')
            ->where('org_pid', $id)
            ->where('active_status', 1)
            ->orderBy('ud_serialno', 'asc')
            ->first();

        if (!$org) {
            return (new ErrorResource("No org Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventOrganizerResource($org, "org fetch successfully", 200))->response()->setStatusCode(200);
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
        $validator = Validator::make($request->all(), [
            'org_name'      => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle venue information.
                $insertOrg = EventOrganizer::where('org_pid', $id)->first();
                if ($request->org_name) {
                    $insertOrg->org_name         = $request->org_name;
                }
                if ($request->org_email) {
                    $insertOrg->org_email = $request->org_email;
                }

                if ($request->phone_no) {
                    $insertOrg->phone_no = $request->phone_no;
                }

                if ($request->org_address) {
                    $insertOrg->org_address = $request->org_address;
                }

                if ($request->remarks) {
                    $insertOrg->remarks = $request->remarks;
                }

                if ($request->active_status) {
                    $insertOrg->active_status = $request->active_status;
                }

                if ($request->designation) {
                    $insertOrg->designation = $request->designation;
                }

                if ($request->org_type) {
                    $insertOrg->org_type = $request->org_type;
                }

                if ($request->org_website) {
                    $insertOrg->org_website = $request->org_website;
                }

                if ($request->ref_user_pid) {
                    $insertOrg->ref_user_pid = $request->ref_user_pid;
                }

                $insertOrg->upd_date = date('Y-m-d H:i:s');
                // $insertOrg->ud_serialno      = $request->ud_serialno;
                // $insertOrg->pid_currdate     = $request->pid_currdate;
                // $insertOrg->pid_prefix       = $request->pid_prefix;
                // $insertOrg->upd_by           = Auth::user()->user_pid;
                // $insertOrg->unit_no          = $request->unit_no;
                $insertOrg->update();

                DB::commit();
                return (new EventOrganizerResource($insertOrg, "Organizer update successfully", 201))->response()->setStatusCode(201);
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

    /**
     * organizer approval function
     *
     * @param  Request  $request
     * @param  string  $org_pid
     * @return void
     */
    public function organizer_approve_process(Request $request, string $org_pid)
    {
        $is_admin = User::where('user_pid', $request->user_pid)->first();
        if (!$is_admin) {
            return (new ErrorResource("Oops! You can't approve this user!", 404))->response()->setStatusCode(404);
        }

        $event_org = EventOrganizer::where('org_pid', $org_pid)->first();
        if (!$event_org) {
            return (new ErrorResource("Oops! Organizer not found!", 404))->response()->setStatusCode(404);
        }

        try {
            DB::beginTransaction();
            $event_org->update([
                'approve_flag'  => $request->approve_status ?? 'N', // 'Y' for Approve, 'C' for Cancel
                'approve_by'    => $is_admin->user_pid,
                'approve_date'  => Carbon::now(),
            ]);
            DB::commit();

            // mailing process
            $user_info = User::where('user_pid', $event_org->ref_user_pid)->first();
            $subject = null;
            $approve_status = null;
            if ($request->approve_status == 'C') {
                $subject = 'Event Organizer register request Cancel by Admin';
                $approve_status = 'Canceled';
            } else {
                $subject = 'Event Organizer register request Approved by Admin';
                $approve_status = 'Approved';
            }
            Mail::to($user_info->email)->send(new AdminApprovalMail($user_info, $subject, 'Event Organizer', $approve_status));
            return (new ApiCommonResponseResource($event_org, 'Event Organizer ' . $approve_status . ' successfully!', 200))->response()->setStatusCode(200);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong!', 501))->response()->setStatusCode(501);
        }
    }
}
