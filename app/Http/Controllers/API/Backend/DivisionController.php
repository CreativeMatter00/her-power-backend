<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\DivisionResource;
use App\Http\Resources\ErrorResource;
use App\Models\Division;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DivisionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $division = Division::all();

        if (!$division) {
            return (new ErrorResource("No Division Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new ApiCommonResponseResource($division, "Division fetch successfully", 200))->response()->setStatusCode(200);
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
            'division_code'     => 'required'
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle schedule information.
                $insert = new Division();
                $insert->division_code          = $request->division_code;
                $insert->division_name          = $request->division_name;
                $insert->bn_division_name       = $request->bn_division_name;
                $insert->population             = $request->population;
                $insert->area_in_sqmeter        = $request->area_in_sqmeter;
                $insert->ud_serialno            = $request->ud_serialno;
                $insert->remarks                = $request->remarks;
                // $insert->cre_by                 = Auth::user()->user_pid;
                // $insert->upd_date               = null;
                // $insert->upd_by                 = null;
                $insert->active_status          = 1;
                $insert->save();

                DB::commit();
                return (new DivisionResource($insert, "Division created successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Division adding failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $division = Division::where('division_pid', $id)->get();

        if (!$division) {
            return (new ErrorResource("No Division Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new DivisionResource($division, "Division fetch successfully", 200))->response()->setStatusCode(200);
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
            'division_code'     => 'required'
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle schedule information.
                $insert = Division::where('division_pid', $id)->first();
                $insert->division_code          = $request->division_code;
                $insert->division_name          = $request->division_name;
                $insert->bn_division_name       = $request->bn_division_name;
                $insert->population             = $request->population;
                $insert->area_in_sqmeter        = $request->area_in_sqmeter;
                $insert->ud_serialno            = $request->ud_serialno;
                $insert->remarks                = $request->remarks;
                // $insert->cre_by                 = Auth::user()->user_pid;
                // $insert->upd_date               = null;
                // $insert->upd_by                 = null;
                $insert->active_status          = $request->active_status ?  $request->active_status : $insert->active_status;
                $insert->update();

                DB::commit();
                return (new DivisionResource($insert, "Division updated successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Division updating failed, Please try again.', 501))->response()->setStatusCode(501);
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
