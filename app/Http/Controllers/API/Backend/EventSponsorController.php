<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventSponsorCollection;
use App\Http\Resources\EventSponsorResource;
use App\Models\EventSponsor;
use App\Service\ImageUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventSponsorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sponsors = EventSponsor::orderBy('ud_serialno', 'asc')->get();

        if ($sponsors->isEmpty()) {
            return (new ErrorResource("No sponsor Found !!", 404))->response()->setStatusCode(404);
        } else {
            foreach ($sponsors as $sponsor) {
                if ($sponsor->sponsor_image) {
                    $sponsor->sponsor_image = asset('/public/' . $sponsor->sponsor_image);
                }
            }
            return (new ApiCommonResponseResource($sponsors, "Sponsors fetched successfully", 200))->response()->setStatusCode(200);
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
            'sponsor_name'      => 'required',
            'contact_phone'      => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                $uploadSponsorImage = null;
                $directory = 'attachments/sponsor/' . now()->format('Ymd') . '/';
                if ($request->hasFile('sponsor_image')) {
                    $uploadSponsorImage = $imageUploadService->uploadFileAndReturnPath($request->sponsor_image, $request->sponsor_name, $directory);
                }
                DB::beginTransaction();
                // handle venue information.
                $insertSponsor = new EventSponsor();
                $insertSponsor->sponsor_name        = $request->sponsor_name;
                $insertSponsor->contract_persone    = $request->contract_persone;
                $insertSponsor->contact_email       = $request->contact_email;
                $insertSponsor->contact_phone       = $request->contact_phone;
                $insertSponsor->address_line        = $request->address_line;
                $insertSponsor->description         = $request->description;
                $insertSponsor->description         = $request->description;
                $insertSponsor->sponsor_image       = $uploadSponsorImage ? $uploadSponsorImage : '';
                $insertSponsor->save();
                DB::commit();
                $insertSponsor->sponsor_image = asset('/public/' . $insertSponsor->sponsor_image);
                return (new EventSponsorResource($insertSponsor, "Event Sponsor created successfully", 201))->response()->setStatusCode(201);
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
        $sponsor = EventSponsor::where('sponsor_pid', $id)->first();

        if ($sponsor->sponsor_image) {
            $sponsor->sponsor_image = asset('/public/' . $sponsor->sponsor_image);
        }
        if (empty($sponsor)) {
            return (new ErrorResource("No Sponsor Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new ApiCommonResponseResource($sponsor, "Sponsor fetch successfully", 200))->response()->setStatusCode(200);
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

            $updateData = [];
            if ($request->has('sponsor_name')) {
                $updateData['sponsor_name'] = $request->sponsor_name;
            }
            if ($request->has('contract_persone')) {
                $updateData['contract_persone'] = $request->contract_persone;
            }
            if ($request->has('contact_email')) {
                $updateData['contact_email'] = $request->contact_email;
            }
            if ($request->has('contact_phone')) {
                $updateData['contact_phone'] = $request->contact_phone;
            }
            if ($request->has('address_line')) {
                $updateData['address_line'] = $request->address_line;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            if ($request->hasFile('sponsor_image')) {
                $directory = 'attachments/sponsor/' . now()->format('Ymd') . '/';
                $uploadSponsorImage = $imageUploadService->uploadFileAndReturnPath($request->sponsor_image, $request->sponsor_name, $directory);
                $updateData['sponsor_image'] = $uploadSponsorImage;
            }
            DB::beginTransaction();
            EventSponsor::where('sponsor_pid', $id)->update($updateData);
            DB::commit();
            return (new EventSponsorResource($updateData, "Event Sponsor updated successfully", 201))->response()->setStatusCode(201);
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
