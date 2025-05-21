<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Mail\AdminApprovalMail;
use App\Models\Branch;
use App\Models\CourseProvider;
use App\Models\Customer;
use App\Models\EduInfo;
use App\Models\Seller;
use App\Models\User;
use App\Models\WorkExperience;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CourseProviderController extends Controller
{
    /**
     * @api Course Provider
     * @author shohag <shohag@atilimited.net>
     * @since 03.02.2025
     * @return collection
     */
    public function index($need = null)
    {
        try {
            if ($need == null) {
                $provider = CourseProvider::orderByRaw("CASE WHEN approve_flag = 'N' THEN 0 ELSE 1 END")->get();
            } else {
                $provider = CourseProvider::orderByRaw("CASE WHEN approve_flag = 'N' THEN 0 ELSE 1 END")->paginate($need);
            }

            if (!$provider) {
                return (new ErrorResource("Course Provider not found", 404))->response()->setStatusCode(404);
            }

            return (new ApiCommonResponseResource($provider, "Course Provider fetched successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Course Provider not found, Please try again.', 501))->response()->setStatusCode(501);
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
        $validator = Validator::make($request->all(), [
            'ref_user_pid' => 'required',
            'provider_info.*' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {

            $is_exist_user = CourseProvider::where('ref_user_pid', $request->ref_user_pid)->first();

            if ($is_exist_user) {
                return (new ApiCommonResponseResource($is_exist_user, "Course Provider Already Exits!", 409))->response()->setStatusCode(409);
            }

            try {

                $user_info = Customer::where('user_pid', $request->ref_user_pid)->first();
                if (!$user_info) {
                    $user_info = Seller::where('user_pid', $request->ref_user_pid)->first();
                }

                $mentorInfo = $request->input('provider_info');
                DB::beginTransaction();
                // handle Student information.
                $insertMentorInfo = new CourseProvider();
                $insertMentorInfo->ref_user_pid = $request->ref_user_pid;
                $insertMentorInfo->providor_name =  $mentorInfo["providor_name"];
                $insertMentorInfo->mobile_no = $user_info->mobile_no;
                $insertMentorInfo->email_id = $user_info->email;
                $insertMentorInfo->website_address =  $mentorInfo["website_address"];
                $insertMentorInfo->address_line =  $mentorInfo["address_line"];
                $insertMentorInfo->trade_licence =  $mentorInfo["trade_licence"];
                $insertMentorInfo->vat_reg_id =  $mentorInfo["vat_reg_id"];
                $insertMentorInfo->tax_reg_id =  $mentorInfo["tax_reg_id"];
                $insertMentorInfo->tin_number =  $mentorInfo["tin_number"];
                $insertMentorInfo->save();
                // handle Education information.
                $provider = CourseProvider::select('providor_pid')->where('providor_id', $insertMentorInfo->providor_id)->first();
                $insertMentorInfo->providor_pid = $provider->providor_pid;
                $mentorEducationInfo = $request->input('education_info');
                if ($mentorEducationInfo) {
                    foreach ($mentorEducationInfo as $data) {
                        $insertEduInfo = new EduInfo();
                        $insertEduInfo->ref_student_pid = 0;
                        $insertEduInfo->ref_mentor_pid = $provider->providor_pid;
                        $insertEduInfo->degree = $data["degree"];
                        $insertEduInfo->group_department = $data["group"];
                        $insertEduInfo->passing_year = $data["passing_year"];
                        $insertEduInfo->result_gpa = $data["result"];
                        $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"];
                        $insertEduInfo->save();
                    }
                }
                // handle Experience information.
                $mentorExperienceInfo = $request->input('experience_info');
                if ($mentorExperienceInfo) {
                    foreach ($mentorExperienceInfo as $data) {

                        $insertEduInfo = new WorkExperience();
                        $insertEduInfo->ref_student_pid = 0;
                        $insertEduInfo->ref_mentor_pid = $provider->providor_pid;
                        $insertEduInfo->work_as = $data["work_as"];
                        $insertEduInfo->experiance = $data["experience"];
                        $insertEduInfo->institution = $data["institution"];
                        $insertEduInfo->relavent_dgree = $data["relavent_dgree"];
                        $insertEduInfo->save();
                    }
                }
                // handle branch information.
                $branch_info = $request->input('branch_info');
                if ($branch_info) {
                    foreach ($branch_info as $data) {

                        $insertBranchInfo = new Branch();
                        $insertBranchInfo->providor_pid = $provider->providor_pid;
                        $insertBranchInfo->branch_name = $data["branch_name"];
                        $insertBranchInfo->website_address = $data["website_address"];
                        $insertBranchInfo->address_line = $data["address_line"];
                        $insertBranchInfo->remarks = $data["remarks"];
                        $insertBranchInfo->save();
                    }
                }
                DB::commit();
                return (new ApiCommonResponseResource($insertMentorInfo, "Course Provider information saved", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Course provider adding failed, Please try again.', 501))->response()->setStatusCode(501);
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
                                            m.providor_pid,
                                            u.name,
                                            CONCAT('$serverUrl/', af.file_url) as profile_photo,
                                            m.providor_name,
                                            m.mobile_no,
                                            m.email_id,
                                            m.website_address,
                                            m.address_line,
                                            m.trade_licence,
                                            m.vat_reg_id,
                                            m.tax_reg_id,
                                            m.tin_number
                                        FROM
                                            users u
                                            LEFT JOIN attached_file af on u.user_pid = af.ref_pid
                                            LEFT JOIN trn_providor m on u.user_pid = m.ref_user_pid
                                        where
                                            u.user_pid = ? ", [$id]);

            $ediInfos = EduInfo::where('ref_mentor_pid', $getMentorInfo->providor_pid)->get();
            if ($ediInfos) {
                $getMentorInfo->education_info = $ediInfos;
            }

            $workExperience = WorkExperience::where('ref_mentor_pid', $getMentorInfo->providor_pid)->get();
            if ($workExperience) {
                $getMentorInfo->experience = $workExperience;
            }
            $branch = Branch::where('providor_pid', $getMentorInfo->providor_pid)->where('active_status', 1)->get();
            if ($branch) {
                $getMentorInfo->branch = $branch;
            }


            return (new ApiCommonResponseResource((array) $getMentorInfo, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Data not found, Please try again.', 404))->response()->setStatusCode(404);
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
            'ref_user_pid' => 'required',
            'provider_info.*' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {

            try {


                $mentorInfo = $request->input('provider_info');
                DB::beginTransaction();
                // handle Student information.
                $insertMentorInfo = CourseProvider::where('providor_pid', $id)->first();

                $mentorInfo["providor_name"] ? $insertMentorInfo->providor_name =  $mentorInfo["providor_name"] : null;
                if ($request->ref_user_pid) {
                    $user_info = Customer::where('user_pid', $request->ref_user_pid)->first();
                    if (!$user_info) {
                        $user_info = Seller::where('user_pid', $request->ref_user_pid)->first();
                    }
                    $insertMentorInfo->ref_user_pid = $request->ref_user_pid;
                    $insertMentorInfo->mobile_no = $user_info->mobile_no;
                    $insertMentorInfo->email_id = $user_info->email;
                }

                $mentorInfo["website_address"] ? $insertMentorInfo->website_address =  $mentorInfo["website_address"] : null;
                $mentorInfo["address_line"] ? $insertMentorInfo->address_line =  $mentorInfo["address_line"] : null;
                $mentorInfo["trade_licence"] ? $insertMentorInfo->trade_licence =  $mentorInfo["trade_licence"] : null;
                $mentorInfo["vat_reg_id"] ? $insertMentorInfo->vat_reg_id =  $mentorInfo["vat_reg_id"] : null;
                $mentorInfo["tax_reg_id"] ? $insertMentorInfo->tax_reg_id =  $mentorInfo["tax_reg_id"] : null;
                $mentorInfo["tin_number"] ? $insertMentorInfo->tin_number =  $mentorInfo["tin_number"] : null;
                $insertMentorInfo->update();
                // handle Education information.
                $provider = CourseProvider::select('providor_pid')->where('providor_id', $insertMentorInfo->providor_id)->first();
                $mentorEducationInfo = $request->input('education_info');
                if ($mentorEducationInfo) {
                    foreach ($mentorEducationInfo as $data) {

                        if ($data["educatmap_pid"]) {
                            $insertEduInfo = EduInfo::where('educatmap_pid', $data["educatmap_pid"])->first();
                            $insertEduInfo->ref_student_pid = 0;
                            $insertEduInfo->ref_mentor_pid = $provider->providor_pid;
                            $data["degree"] ? $insertEduInfo->degree = $data["degree"] : null;
                            $data["group"] ? $insertEduInfo->group_department = $data["group"] : null;
                            $data["passing_year"] ? $insertEduInfo->passing_year = $data["passing_year"] : null;
                            $data["result"] ? $insertEduInfo->result_gpa = $data["result"] : null;
                            $data["gpa_cgpa_outof"] ? $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"] : null;
                            $insertEduInfo->update();
                        } else {

                            $insertEduInfo =  new EduInfo();
                            $insertEduInfo->ref_student_pid = 0;
                            $insertEduInfo->ref_mentor_pid = $provider->providor_pid;
                            $data["degree"] ? $insertEduInfo->degree = $data["degree"] : null;
                            $data["group"] ? $insertEduInfo->group_department = $data["group"] : null;
                            $data["passing_year"] ? $insertEduInfo->passing_year = $data["passing_year"] : null;
                            $data["result"] ? $insertEduInfo->result_gpa = $data["result"] : null;
                            $data["gpa_cgpa_outof"] ? $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"] : null;
                            $insertEduInfo->save();
                        }
                    }
                }
                // handle Experience information.
                $mentorExperienceInfo = $request->input('experience_info');
                if ($mentorExperienceInfo) {
                    foreach ($mentorExperienceInfo as $data) {

                        if ($data["expcatmap_pid"]) {

                            $insertEduInfo = WorkExperience::where('expcatmap_pid', $data["expcatmap_pid"])->first();
                            $insertEduInfo->ref_student_pid = 0;
                            $insertEduInfo->ref_mentor_pid = $provider->providor_pid;
                            $data["work_as"] ? $insertEduInfo->work_as = $data["work_as"] : null;
                            $data["experience"] ? $insertEduInfo->experiance = $data["experience"] : null;
                            $data["institution"] ? $insertEduInfo->institution = $data["institution"] : null;
                            $data["relavent_dgree"] ? $insertEduInfo->relavent_dgree = $data["relavent_dgree"] : null;
                            $insertEduInfo->update();
                        } else {

                            $insertEduInfo = new WorkExperience();
                            $insertEduInfo->ref_student_pid = 0;
                            $insertEduInfo->ref_mentor_pid = $provider->providor_pid;
                            $data["work_as"] ? $insertEduInfo->work_as = $data["work_as"] : null;
                            $data["experience"] ? $insertEduInfo->experiance = $data["experience"] : null;
                            $data["institution"] ? $insertEduInfo->institution = $data["institution"] : null;
                            $data["relavent_dgree"] ? $insertEduInfo->relavent_dgree = $data["relavent_dgree"] : null;
                            $insertEduInfo->save();
                        }
                    }
                }
                // handle branch information.
                $branch_info = $request->input('branch_info');
                if ($branch_info) {
                    foreach ($branch_info as $data) {

                        if ($data["branch_pid"]) {

                            $insertBranchInfo = Branch::where('branch_pid', $data["branch_pid"])->first();
                            $insertBranchInfo->providor_pid = $provider->providor_pid;
                            $data["branch_name"] ? $insertBranchInfo->branch_name = $data["branch_name"] : null;
                            $data["website_address"] ? $insertBranchInfo->website_address = $data["website_address"] : null;
                            $data["address_line"] ? $insertBranchInfo->address_line = $data["address_line"] : null;
                            $data["remarks"] ? $insertBranchInfo->remarks = $data["remarks"] : null;
                            $insertBranchInfo->update();
                        } else {

                            $insertBranchInfo = new Branch();
                            $insertBranchInfo->providor_pid = $provider->providor_pid;
                            $data["branch_name"] ? $insertBranchInfo->branch_name = $data["branch_name"] : null;
                            $data["website_address"] ? $insertBranchInfo->website_address = $data["website_address"] : null;
                            $data["address_line"] ? $insertBranchInfo->address_line = $data["address_line"] : null;
                            $data["remarks"] ? $insertBranchInfo->remarks = $data["remarks"] : null;
                            $insertBranchInfo->save();
                        }
                    }
                }
                DB::commit();
                return (new ApiCommonResponseResource($insertMentorInfo, "Course Provider information updated", 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Course provider info updating failed, Please try again.', 501))->response()->setStatusCode(501);
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



    /**
     * Remove the specified experience from storage.
     */
    public function destroy_experience(string $id)
    {
        $experience = WorkExperience::where('expcatmap_pid', $id)->first();

        if (!$experience) {
            return (new ErrorResource("Experience not Found !!", 404))->response()->setStatusCode(404);
        }

        $experience->delete();
        return (new ApiCommonResponseResource($experience, "Experience Deleted successfully", 200))->response()->setStatusCode(200);
    }


    /**
     * Remove the specified education from storage.
     */
    public function destroy_education(string $id)
    {
        $education = EduInfo::where('educatmap_pid', $id)->first();

        if (!$education) {
            return (new ErrorResource("Educational info not found !!", 404))->response()->setStatusCode(404);
        }

        $education->delete();
        return (new ApiCommonResponseResource($education, "Educational Info Deleted Successfully", 200))->response()->setStatusCode(200);
    }


    /**
     * Remove the specified education from storage.
     */
    public function destroy_branch(string $id)
    {
        $branch = Branch::where('branch_pid', $id)->where('active_status', 1)->first();

        if (!$branch) {
            return (new ErrorResource("Branch info not found !!", 404))->response()->setStatusCode(404);
        }

        $branch->update([
            'active_status' => 0
        ]);
        return (new ApiCommonResponseResource($branch, "Branch Info Deleted Successfully", 200))->response()->setStatusCode(200);
    }

    public function course_provider_approve_process(Request $request, $provider_pid)
    {
        $is_admin = User::where('user_pid', $request->user_pid)->first();
        if (!$is_admin) {
            return (new ErrorResource("Oops! You can't approve this user!", 404))->response()->setStatusCode(404);
        }

        $provider_info = CourseProvider::where('providor_pid', $provider_pid)->first();
        if (!$provider_info) {
            return (new ErrorResource("Oops! Course Provider not found!", 404))->response()->setStatusCode(404);
        }

        try {
            DB::beginTransaction();
            $provider_info->update([
                'approve_flag'  => $request->approve_status ?? 'N', // 'Y' for Approve, 'C' for Cancel
                'approve_by'    => $is_admin->user_pid,
                'approve_date'  => Carbon::now(),
            ]);
            DB::commit();

            // mailing process
            $user_info = User::where('user_pid', $provider_info->ref_user_pid)->first();
            $subject = null;
            $approve_status = null;
            if ($request->approve_status == 'C') {
                $subject = 'Course Provider register request Cancel by Admin';
                $approve_status = 'Canceled';
            } else {
                $subject = 'Course Provider register request Approved by Admin';
                $approve_status = 'Approved';
            }
            Mail::to($user_info->email)->send(new AdminApprovalMail($user_info, $subject, 'Course Provider', $approve_status));
            return (new ApiCommonResponseResource($provider_info, 'Course Provider ' . $approve_status . ' successfully!', 200))->response()->setStatusCode(200);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return (new ErrorResource($th->getMessage(), 501))->response()->setStatusCode(501);
        }
    }
}
