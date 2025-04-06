<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\EduInfo;
use App\Models\Mentor;
use App\Models\WorkExperience;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MentorController extends Controller
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
        // print_r($request->all());exit;

        $validator = Validator::make($request->all(), [
            'mentor_info.full_name' => 'required|string|max:255',
            'mentor_info.dob' => 'required',
            'mentor_info.gender' => 'required',
            'ref_user_pid' => 'required',

        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                $mentorInfo = $request->input('mentor_info');
                DB::beginTransaction();
                // handle Student information.
                $insertMentorInfo = new Mentor();
                $insertMentorInfo->ref_user_pid = $request->ref_user_pid;
                $insertMentorInfo->full_name =  $mentorInfo["full_name"];
                $insertMentorInfo->dob =  $mentorInfo["dob"];
                $insertMentorInfo->gender =  $mentorInfo["gender"];
                $insertMentorInfo->trad_licence =  $mentorInfo["trad_licence"];
                $insertMentorInfo->vat_reg_id =  $mentorInfo["vat_reg_id"];
                $insertMentorInfo->tin_number =  $mentorInfo["tin_number"];
                $insertMentorInfo->tax_reg_id =  $mentorInfo["tax_reg_id"];
                $insertMentorInfo->nid =  $mentorInfo["nid"];
                $insertMentorInfo->save();
                // handle Education information.
                $mentorPID = Mentor::select('mentor_pid')->where('mentor_id',$insertMentorInfo->mentor_id)->first();

                $mentorEducationInfo = $request->input('education_info');
                foreach ($mentorEducationInfo as $data) {

                    $insertEduInfo = new EduInfo();
                    $insertEduInfo->ref_student_pid = 0;
                    $insertEduInfo->ref_mentor_pid = $mentorPID->mentor_pid;
                    $insertEduInfo->degree = $data["degree"];
                    $insertEduInfo->group_department = $data["group"];
                    $insertEduInfo->passing_year = $data["passing_year"];
                    $insertEduInfo->result_gpa = $data["result"];
                    $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"];
                    $insertEduInfo->save();
                }

                  // handle Experience information.

                  $mentorExperienceInfo = $request->input('experience_info');

                  foreach ($mentorExperienceInfo as $data) {

                    $insertEduInfo = new WorkExperience();
                    $insertEduInfo->ref_student_pid = 0;
                    $insertEduInfo->ref_mentor_pid = $mentorPID->mentor_pid;
                    $insertEduInfo->work_as = $data["work_as"];
                    $insertEduInfo->experiance = $data["experience"];
                    $insertEduInfo->institution = $data["institution"];
                    $insertEduInfo->relavent_dgree = $data["relavent_dgree"];
                    $insertEduInfo->save();
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertMentorInfo, "Mentor information saved", 201))->response()->setStatusCode(201);
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
            $getMentorInfo = DB::selectOne("SELECT
                                            u.user_pid,
                                            m.mentor_pid,
                                            u.name,
                                            CONCAT('$serverUrl/', af.file_url) as profile_photo,
                                            m.full_name,
                                            m.gender,
                                            m.dob,
                                            m.tax_reg_id,
                                            m.tin_number,
                                            m.trad_licence,
                                            m.vat_reg_id,
                                            m.nid
                                            FROM
                                                users u
                                                LEFT JOIN attached_file af on u.user_pid = af.ref_pid
                                                LEFT JOIN trn_mentor m on u.user_pid = m.ref_user_pid
                                            where
                                                u.user_pid = ?", [$id]);

            $ediInfos = EduInfo::where('ref_mentor_pid', $getMentorInfo->mentor_pid)->get();
            if($ediInfos){
                $getMentorInfo->education_info= $ediInfos;
            }
            
            return (new ApiCommonResponseResource((array) $getMentorInfo, "Data fetched", 200))->response()->setStatusCode(200);
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
