<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventSpeakerCollection;
use App\Http\Resources\EventSpeakerResource;
use App\Models\EventSpeaker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventSpeakerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $speaker = EventSpeaker::orderBy('speaker_id', 'DESC')
            ->get();

        if (!$speaker) {
            return (new ErrorResource("No speaker Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new ApiCommonResponseResource($speaker, "speaker fetch successfully", 200))->response()->setStatusCode(200);
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
            'speaker_name'  => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle venue information.
                $insertSpeaker = new EventSpeaker();
                $insertSpeaker->speaker_name    = $request->speaker_name;
                $insertSpeaker->speaker_email   = $request->speaker_email;
                $insertSpeaker->phone_no        = $request->phone_no;
                $insertSpeaker->org_address     = $request->org_address;
                $insertSpeaker->speaker_bio     = $request->speaker_bio;
                $insertSpeaker->remarks         = $request->remarks;
                $insertSpeaker->designation         = $request->designation;
                $insertSpeaker->description         = $request->description;
                $insertSpeaker->speaker_profile_link = $request->speaker_profile_link;
                // $insertSpeaker->cre_by          = Auth::user()->user_pid;
                $insertSpeaker->save();

                DB::commit();
                return (new EventResource($insertSpeaker, "Speaker created successfully", 201))->response()->setStatusCode(201);
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
        $speaker = EventSpeaker::where('speaker_pid', $id)->first();

        if (empty($speaker)) {
            return (new ErrorResource("No speaker Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventSpeakerResource($speaker, "speaker fetch successfully", 200))->response()->setStatusCode(200);
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

        try {

            DB::beginTransaction();

            // handle venue information.
            $updateSpeaker = EventSpeaker::where('speaker_pid', $id)->first();
            if ($request->speaker_name) {
                $updateSpeaker->speaker_name    = $request->speaker_name;
            }
            if ($request->speaker_email) {
                $updateSpeaker->speaker_email   = $request->speaker_email;
            }
            if ($request->phone_no) {
                $updateSpeaker->phone_no        = $request->phone_no;
            }
            if ($request->org_address) {
                $updateSpeaker->org_address     = $request->org_address;
            }
            if ($request->speaker_bio) {
                $updateSpeaker->speaker_bio     = $request->speaker_bio;
            }
            if ($request->remarks) {
                $updateSpeaker->remarks         = $request->remarks;
            }
            if ($request->active_status) {
                $updateSpeaker->active_status   = $request->active_status;
            }

            if ($request->designation) {
                $updateSpeaker->designation   = $request->designation;
            }
            if ($request->description) {
                $updateSpeaker->description   = $request->description;
            }
            if ($request->speaker_profile_link) {
                $updateSpeaker->speaker_profile_link   = $request->speaker_profile_link;
            }

            // $updateSpeaker->upd_by          = Auth::user()->user_pid;
            $updateSpeaker->upd_date        = date('Y-m-d H:i:s');
            $updateSpeaker->update();

            DB::commit();
            return (new EventResource($updateSpeaker, "Speaker updated successfully", 201))->response()->setStatusCode(201);
        } catch (Exception $e) {
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
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
