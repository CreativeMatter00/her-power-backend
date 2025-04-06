<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventVenueCollection;
use App\Http\Resources\EventVenueResource;
use App\Models\Division;
use App\Models\EventVenue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventVenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $venue = EventVenue::orderBy('ud_serialno', 'asc')->with('events')->get();

        if (!$venue) {
            return (new ErrorResource("No Venue Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new ApiCommonResponseResource($venue, "Venue fetch successfully", 200))->response()->setStatusCode(200);
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
            'venue_name'    => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            $division = Division::where('division_code', $request->division_code)->first();
            try {

                DB::beginTransaction();

                // handle venue information.
                $insertVenue = new EventVenue();
                $insertVenue->venue_name = $request->venue_name;
                $insertVenue->venue_title = $request->venue_title;
                $insertVenue->capacity = $request->capacity;
                $insertVenue->venue_address = $request->venue_address;
                $insertVenue->per_day_rent = $request->per_day_rent;
                $insertVenue->division_code = $request->division_code;
                $insertVenue->division_name = $division->division_name;
                $insertVenue->bn_division_name = $division->bn_division_name;
                $insertVenue->save();

                DB::commit();
                return (new EventVenueResource($insertVenue, "Event Venue created successfully", 201))->response()->setStatusCode(201);
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

        $venue = EventVenue::where('venue_pid', $id)->with('events')->first();
        if (empty($venue)) {
            return (new ErrorResource("No Veneue Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventVenueResource($venue, "Veneue fetch successfully", 200))->response()->setStatusCode(200);
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
            $updateVenue = EventVenue::where('venue_pid', $id)->first();
            if ($request->venue_name) {
                $updateVenue->venue_name = $request->venue_name;
            }
            if ($request->venue_title) {
                $updateVenue->venue_title = $request->venue_title;
            }
            if ($request->capacity) {
                $updateVenue->capacity = $request->capacity;
            }
            if ($request->venue_address) {
                $updateVenue->venue_address = $request->venue_address;
            }
            if ($request->per_day_rent) {
                $updateVenue->per_day_rent = $request->per_day_rent;
            }
            if ($request->division_code) {
                $updateVenue->division_code = $request->division_code;
            }
            if ($request->division_name) {
                $updateVenue->division_name = $request->division_name;
            }
            if ($request->bn_division_name) {
                $updateVenue->bn_division_name = $request->bn_division_name;
            }
            $updateVenue->upd_date = date('Y-m-d H:i:s');
            $updateVenue->update();

            DB::commit();
            return (new EventVenueResource($updateVenue, "Event Venue updated successfully", 201))->response()->setStatusCode(201);
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
