<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\EduInfo;
use App\Models\JobProvider;
use App\Models\JobSeeker;
use App\Models\JobSeekerAchievement;
use App\Models\JobSeekerExperience;
use App\Models\JobSeekerSkill;
use App\Models\Mentor;
use App\Models\SeekerEduInfo;
use App\Models\Skill;
use App\Models\WorkExperience;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SkillController extends Controller
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
    public function skillsetStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skill_name' => 'required',
            'skill_desc' => 'required|string|max:255'
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                // handle Student information.
                $insertSkillset = new Skill();
                $insertSkillset->skill_name = $request->skill_name;
                $insertSkillset->skill_desc =  $request->skill_desc;
                $insertSkillset->skill_type =  1;
                $insertSkillset->skill_category =  "Skillset";
                $insertSkillset->save();
                $insertSkillset->skill_pid = DB::table('job_skill')->where('skill_id', $insertSkillset->skill_id)->pluck('skill_pid')->first();
               

                DB::commit();
                return (new ApiCommonResponseResource($insertSkillset, "Skillset created successfully.", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }



    public function getSkillset()
    {
        try {
            $getSkillset = Skill::select('skill_pid', 'skill_name', 'skill_desc')->where('skill_type', 1)->get();
            return (new ApiCommonResponseResource($getSkillset, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }


    public function updateSkillset(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'skill_name' => 'required_without:skill_desc',
            'skill_desc' => 'required_without:skill_name|string|max:255'
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                // handle Student information.
                $updateSkillset = Skill::where('skill_pid', $id)->first();
                if ($request->has('skill_name')) {
                    $updateSkillset->skill_name = $request->skill_name;
                }
                if ($request->has('skill_desc')) {
                    $updateSkillset->skill_desc = $request->skill_desc;
                }
                $updateSkillset->skill_type = 1;
                $updateSkillset->skill_category = "Skillset";
                $updateSkillset->update();
               

                DB::commit();
                return (new ApiCommonResponseResource($updateSkillset, "Skillset updated successfully.", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }



    public function skillStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skill_name' => 'required',
            'skill_desc' => 'required|string|max:255'
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                // handle Student information.
                $insertSkill = new Skill();
                $insertSkill->parent_skill_pid = $request->parent_skill_pid;
                $insertSkill->skill_name = $request->skill_name;
                $insertSkill->skill_desc =  $request->skill_desc;
                $insertSkill->skill_type =  2;
                $insertSkill->skill_category =  "Skill";
                $insertSkill->save();
                $insertSkill->skill_pid = DB::table('job_skill')->where('skill_id', $insertSkill->skill_id)->pluck('skill_pid')->first();
               

                DB::commit();
                return (new ApiCommonResponseResource($insertSkill, "Skill created successfully.", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }


    public function getSkillsBySkillsetId($id)
    {
        try {
            $getSkills = Skill::select('skill_pid', 'skill_name', 'skill_desc')->where('parent_skill_pid', $id)->where('skill_type', 2)->get();
            return (new ApiCommonResponseResource($getSkills, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }

    public function getAllSkills()
    {
        try {
            $getSkills = Skill::select('skill_pid', 'skill_name', 'skill_desc', 'parent_skill_pid as skillset_pid')->where('skill_type', 2)->get();
            foreach($getSkills as $skill){
                $parentSkill = Skill::select('skill_pid', 'skill_name', 'skill_desc')->where('skill_pid', $skill->skillset_pid)->first();
                $skill->skillset_name = $parentSkill->skill_name;
            }
            
            return (new ApiCommonResponseResource($getSkills, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }


    public function updateSkill(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'skill_name' => 'required_without:skill_desc',
            'skill_desc' => 'required_without:skill_name|string|max:255'
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                // handle Student information.
                $updateSkill = Skill::where('skill_pid', $id)->first();
                if ($request->has('skill_name')) {
                    $updateSkill->skill_name = $request->skill_name;
                }
                if ($request->has('skill_desc')) {
                    $updateSkill->skill_desc = $request->skill_desc;
                }
                $updateSkill->skill_type = 2;
                $updateSkill->skill_category = "Skill";
                $updateSkill->update();
               

                DB::commit();
                return (new ApiCommonResponseResource($updateSkill, "Skill updated successfully.", 201))->response()->setStatusCode(201);
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
