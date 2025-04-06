<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventOrganizerCollection;
use App\Http\Resources\EventOrganizerResource;
use App\Models\EventOrganizer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventOrganizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $org = EventOrganizer::select('org_pid', 'org_name', 'org_address', 'designation', 'org_type', 'org_website', 'ref_user_pid')
            ->where('active_status', 1)
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
}
