<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\EduInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use App\Models\Student;
use Exception;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(int $need = null)
    {
        if ($need == null) {
            $data = Student::with('user_info', 'cust_info')->where('active_status', 1)->get();
        } else {
            $data = Student::with('user_info', 'cust_info')->where('active_status', 1)->paginate($need);
        }

        if ($data->isEmpty()) {
            return (new ErrorResource('Data not found!', 404))->response()->setStatusCode(404);
        }
        return (new ApiCommonResponseResource($data, "Student data fatch successfully!", 200))->response()->setStatusCode(200);
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

        $validator = Validator::make($request->all(), [
            'student_info.full_name' => 'required|string|max:255',
            'student_info.dob' => 'required',
            'student_info.gender' => 'required',
            'ref_user_pid' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                $studentInfo = $request->input('student_info');
                DB::beginTransaction();
                // handle Student information.
                $insertStuInfo = new Student();
                $insertStuInfo->ref_user_pid = $request->ref_user_pid;
                $insertStuInfo->full_name =  $studentInfo["full_name"];
                $insertStuInfo->dob =  $studentInfo["dob"];
                $insertStuInfo->gender =  $studentInfo["gender"];
                $insertStuInfo->save();
                // handle Education information.
                $insertStuInfo->student_pid = $studentPID = Student::select('student_pid')
                                ->where('student_id', $insertStuInfo->student_id)
                                ->first();
                $studentEducationInfo = $request->input('education_info');
                foreach ($studentEducationInfo as $data) {

                    $insertEduInfo = new EduInfo();
                    $insertEduInfo->ref_student_pid = $studentPID->student_pid;
                    $insertEduInfo->ref_mentor_pid = 0;
                    $insertEduInfo->degree = $data["degree"];
                    $insertEduInfo->group_department = $data["group"];
                    $insertEduInfo->passing_year = $data["passing_year"];
                    $insertEduInfo->result_gpa = $data["result"];
                    $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"];
                    $insertEduInfo->save();
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertStuInfo, "Student information saved", 201))->response()->setStatusCode(201);
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
            if ($ediInfos) {
                $getStudentInfo->education_info = $ediInfos;
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
        $validator = Validator::make($request->all(), [
            // 'student_info.full_name' => 'required|string|max:255',
            // 'student_info.dob' => 'required',
            // 'student_info.gender' => 'required',
            // 'ref_user_pid' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                $studentInfo = $request->input('student_info');
                DB::beginTransaction();
                // handle Student information.
                $insertStuInfo = Student::where('student_pid', $id)->first();
                $insertStuInfo->ref_user_pid = $request->ref_user_pid;
                $studentInfo["full_name"] ? $insertStuInfo->full_name =  $studentInfo["full_name"] : null;
                $studentInfo["dob"] ? $insertStuInfo->dob =  $studentInfo["dob"] : null;
                $studentInfo["gender"] ? $insertStuInfo->gender =  $studentInfo["gender"] : null;
                $insertStuInfo->update();
                // handle Education information.
                $studentPID = Student::select('student_pid')->where('student_id', $insertStuInfo->student_id)->first();
                $studentEducationInfo = $request->input('education_info');
                if ($studentEducationInfo) {
                    foreach ($studentEducationInfo as $data) {

                        if ($data["educatmap_pid"]) {


                            $insertEduInfo = EduInfo::where('educatmap_pid', $data["educatmap_pid"])->first();
                            $insertEduInfo->ref_student_pid = $studentPID->student_pid;
                            $insertEduInfo->ref_mentor_pid = 0;
                            $data["degree"] ? $insertEduInfo->degree = $data["degree"] : null;
                            $data["group"] ? $insertEduInfo->group_department = $data["group"] : null;
                            $data["passing_year"] ? $insertEduInfo->passing_year = $data["passing_year"] : null;
                            $data["result"] ? $insertEduInfo->result_gpa = $data["result"] : null;
                            $data["gpa_cgpa_outof"] ? $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"] : null;
                            $insertEduInfo->update();
                        } else {


                            $insertEduInfo = new EduInfo();
                            $insertEduInfo->ref_student_pid = $studentPID->student_pid;
                            $insertEduInfo->ref_mentor_pid = 0;
                            $data["degree"] ? $insertEduInfo->degree = $data["degree"] : null;
                            $data["group"] ? $insertEduInfo->group_department = $data["group"] : null;
                            $data["passing_year"] ? $insertEduInfo->passing_year = $data["passing_year"] : null;
                            $data["result"] ? $insertEduInfo->result_gpa = $data["result"] : null;
                            $data["gpa_cgpa_outof"] ? $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"] : null;
                            $insertEduInfo->save();
                        }
                    }
                }


                DB::commit();
                return (new ApiCommonResponseResource($insertStuInfo, "Student information Updated", 200))->response()->setStatusCode(200);
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
