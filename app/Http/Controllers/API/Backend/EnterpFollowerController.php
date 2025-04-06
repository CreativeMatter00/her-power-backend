<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeleteResource;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\FollowerResource;
use App\Models\EnterpFollower;
use App\Rules\UniqueCustomerEnterpreneur;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EnterpFollowerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'customer_pid' => 'required',
            'enterpenure_pid' => [
                'required',
                new UniqueCustomerEnterpreneur($request->input('customer_pid')),
            ],
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle venue information.
                $insertFollower = new EnterpFollower();
                $insertFollower->enterpenure_pid = $request->enterpenure_pid;
                $insertFollower->customer_pid = $request->customer_pid;
                $insertFollower->save();

                DB::commit();
                return (new FollowerResource($insertFollower, "Successfully Followed", 201))->response()->setStatusCode(201);
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

        $getFollowerList = DB::select("SELECT ep.user_pid, 	ep.enterpenure_pid,ep.fname, ep.lname, ep.shop_name, ( select COUNT(eef.follower_id) as TOTAL_FOLLOWER from ec_enterp_follower eef where eef.enterpenure_pid = ef.enterpenure_pid ) as TOTAL_FOLLOWER, ( select COUNT (er.rating_marks) as seller_total_rating FROM ec_enterp_rating er where er.enterpenure_pid = ef.enterpenure_pid ) as seller_total_rating, ( select COALESCE( ROUND( SUM(er.rating_marks) / COUNT(er.enterpenure_pid) :: numeric, 1 ), 0 ) as seller_avg_rating FROM ec_enterp_rating er where er.enterpenure_pid = ef.enterpenure_pid ) as seller_avg_rating, ( SELECT COALESCE( ROUND( ( COUNT( CASE WHEN er.rating_marks BETWEEN 3 AND 5 THEN 1 END )*100.0 ) / NULLIF(COUNT(*), 0) ), 0 ) AS positive_rating_percentage FROM ec_enterp_rating er WHERE er.enterpenure_pid = ef.enterpenure_pid ) AS positive_seller_rating, af.file_url as profile_photo FROM ec_enterp_follower ef left join ec_enterpenure ep on ef.enterpenure_pid = ep.enterpenure_pid left join ec_customer cs on ef.customer_pid = cs.customer_pid left join attached_file af on cs.user_pid = af.ref_pid WHERE ef.customer_pid = ?", [$id]);

        if ($getFollowerList) {

            return (new FollowerResource($getFollowerList, "Data fetch successfully", 201))->response()->setStatusCode(201);
        } else {
            return (new ErrorResource("Data not found", 200,true))->response()->setStatusCode(200);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        try {
            $enterPCustomerId = explode(',', $id);
            $deleteFollower = EnterpFollower::where('customer_pid', $enterPCustomerId[0])->where('enterpenure_pid', $enterPCustomerId[1])->first();
            if (!empty($deleteFollower)) {
                $deleteFollower->delete();
                return (new DeleteResource("Successfully Unfollowed", 200))->response()->setStatusCode(200);
            } else {
                return (new DeleteResource("Data not found !", 404))->response()->setStatusCode(404);
            }
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
