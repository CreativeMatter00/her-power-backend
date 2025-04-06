<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\EduInfo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrontendStudentController extends Controller
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
        try {

   
            $serverUrl = asset('/public');
            $getStudentInfo = DB::selectOne("SELECT
                                    u.user_pid,
                                    stu.student_pid,
                                    u.name,
                                    CONCAT('$serverUrl/',af.file_url) as profile_photo,
                                    stu.full_name,
                                    stu.gender,
                                    stu.dob
                                    FROM
                                    users u
                                    LEFT JOIN attached_file af on u.user_pid = af.ref_pid
                                    LEFT JOIN trn_student stu on u.user_pid = stu.ref_user_pid
                                where
                                    u.user_pid = ? ", [$id]);

            $ediInfos = EduInfo::where('ref_student_pid', $getStudentInfo->student_pid)->get();
            if($ediInfos){
                $getStudentInfo->education_info= $ediInfos;
            }
            return (new ApiCommonResponseResource((array) $getStudentInfo, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
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
        //
    }
}
