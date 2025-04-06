<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\JobCollection;
use App\Http\Resources\TaskCollection;
use App\Models\EduInfo;
use App\Models\JobPost;
use App\Models\JobProvider;
use App\Models\JobSeeker;
use App\Models\JobSeekerAchievement;
use App\Models\JobSeekerExperience;
use App\Models\JobSeekerSkill;
use App\Models\Mentor;
use App\Models\SeekerEduInfo;
use App\Models\TaskPost;
use App\Models\WorkExperience;
use App\Service\ImageUploadService;
use Exception;
use Illuminate\Console\View\Components\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\VarDumper\VarDumper;

class JobController extends Controller
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
    public function jobProviderStore(Request $request)
    {
        // print_r($request->all());exit;

        $validator = Validator::make($request->all(), [
            'user_pid' => 'required|string|max:20|unique:job_provider,user_pid',
            'provider_name' => 'required',
            'designation' => 'required',
            'address_line' => 'required',
            'company_type' => 'required',
            'websites_name' => 'required',

        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                // handle Student information.
                $insertJobProvider = new JobProvider();
                $insertJobProvider->user_pid = $request->user_pid;
                $insertJobProvider->provider_name =  $request->provider_name;
                $insertJobProvider->designation =  $request->designation;
                $insertJobProvider->address_line =  $request->address_line;
                $insertJobProvider->company_type =  $request->company_type;
                $insertJobProvider->websites_name =  $request->websites_name;
                $insertJobProvider->save();
                $insertJobProvider->jobprovider_pid = DB::table('job_provider')->where('jobprovider_id', $insertJobProvider->jobprovider_id)->pluck('jobprovider_pid')->first();


                DB::commit();
                return (new ApiCommonResponseResource($insertJobProvider, "Job Provider registered successfully.", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    // Job provider update
    public function updateJobProvider(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_pid' => 'required|string|max:20|unique:job_provider,user_pid,' . $id . ',jobprovider_pid',
            'provider_name' => 'required',
            'designation' => 'required',
            'address_line' => 'required',
            'company_type' => 'required',
            'websites_name' => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                DB::beginTransaction();

                $jobProvider = JobProvider::where('jobprovider_pid', $id)
                    ->first();

                if (!$jobProvider) {
                    return (new ErrorResource("Job Provider not found", 404))->response()->setStatusCode(404);
                }

                $jobProvider->user_pid = $request->user_pid;
                $jobProvider->provider_name = $request->provider_name;
                $jobProvider->designation = $request->designation;
                $jobProvider->address_line = $request->address_line;
                $jobProvider->company_type = $request->company_type;
                $jobProvider->websites_name = $request->websites_name;
                $jobProvider->save();

                DB::commit();
                return (new ApiCommonResponseResource($jobProvider, "Job Provider updated successfully.", 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    // Job provider by id
    public function getJobProviderById($id)
    {
        try {
            $jobProvider = JobProvider::where('jobprovider_pid', $id)
                ->orWhere('user_pid', $id)
                ->get();

            if (!$jobProvider) {
                return (new ErrorResource("Job Provider not found", 404))->response()->setStatusCode(404);
            }

            return (new ApiCommonResponseResource($jobProvider, "Job Provider fetched successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    // Job seeker store
    public function jobSeekerStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_pid' => 'required|string|max:20|unique:job_seeker,user_pid',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                // Insert Seeker info
                $seekerInfo = $request->input('job_seeker_info');

                DB::beginTransaction();
                $insertJobSeekerInfo = new JobSeeker();
                $insertJobSeekerInfo->user_pid = $request->user_pid;
                $insertJobSeekerInfo->full_name = "Test name";  // This should not be a required field bit it is in the database.
                $insertJobSeekerInfo->work_profile = $seekerInfo["work_profile"];
                $insertJobSeekerInfo->portfolio = $seekerInfo["portfolio"];

                // CV Path file upload
                if ($files = $request->file('job_seeker_info.cv_path')) {
                    $destinationPath = public_path('/attachments/job_seeker/');
                    $cv = date('Ymd') . mt_rand(1000, 9999) . '.' . $files->getClientOriginalExtension();
                    $files->move($destinationPath, $cv);
                    $insertJobSeekerInfo->cv_path = url('public/attachments/job_seeker/' . $cv);
                }
                // CV Path file upload done

                $insertJobSeekerInfo->save();
                $jobSeekerPID = JobSeeker::select('profile_pid')->where('profile_id', $insertJobSeekerInfo->profile_id)->first();

                // Insert education info of seeker
                $seekerEducationInfo = $request->input('education_info');
                foreach ($seekerEducationInfo as $data) {

                    $insertEduInfo = new SeekerEduInfo();
                    $insertEduInfo->profile_pid = $jobSeekerPID->profile_pid;
                    $insertEduInfo->edu_dgree = $data["edu_dgree"];
                    $insertEduInfo->group_department = $data["group_department"];
                    $insertEduInfo->passing_year = $data["passing_year"];
                    $insertEduInfo->result_gpa = $data["result_gpa"];
                    $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"];
                    $insertEduInfo->save();
                }


                // Insert skill info of seeker

                $seekerSkillInfo = $request->input('skill_info');
                foreach ($seekerSkillInfo as $data) {

                    $insertSkillInfo = new JobSeekerSkill();
                    $insertSkillInfo->profile_pid = $jobSeekerPID->profile_pid;
                    $insertSkillInfo->skill_group = $data["skill_group"];
                    $insertSkillInfo->skill_category = $data["skill_category"];
                    $insertSkillInfo->save();
                }

                // Insert work experience info of seeker

                $seekerExperienceInfo = $request->input('work_experience_info');
                foreach ($seekerExperienceInfo as $data) {

                    $insertExperienceInfo = new JobSeekerExperience();
                    $insertExperienceInfo->profile_pid = $jobSeekerPID->profile_pid;
                    $insertExperienceInfo->experience_title = $data["experience_title"];
                    $insertExperienceInfo->experience_desc = $data["experience_desc"];
                    $insertExperienceInfo->institution_name = $data["institution_name"];
                    $insertExperienceInfo->save();
                }

                // Insert achievement experience info of seeker

                $seekerAchievementInfo = $request->input('achievement_info');
                foreach ($seekerAchievementInfo as $key => $data) {
                    $insertAchievementInfo = new JobSeekerAchievement();
                    $insertAchievementInfo->profile_pid = $jobSeekerPID->profile_pid;
                    $insertAchievementInfo->achievment_title = $data["achievment_title"];
                    $attachedDocKey = 'achievement_info.' . $key . '.attached_doc';
                    if ($files = $request->file($attachedDocKey)) {
                        $destinationPath = public_path('/attachments/job_seeker/');
                        $attachment = date('Ymd') . mt_rand(1000, 9999) . '.' . $files->getClientOriginalExtension();
                        $files->move($destinationPath, $attachment);
                        $insertAchievementInfo->attached_doc = url('public/attachments/job_seeker/' . $attachment);
                    }
                    $insertAchievementInfo->save();
                }

                $jobSeeker = JobSeeker::with('workExperienceInfo')->with('skillInfo')->with('achievementInfo')->with('educationInfo')->where('profile_pid', $jobSeekerPID->profile_pid)->first();

                DB::commit();
                return (new ApiCommonResponseResource($jobSeeker, "Job Seeker information saved", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }


    // Job Seeker Update
    public function updateJobSeeker(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_pid' => 'required|string|max:20|unique:job_seeker,user_pid,' . $id . ',profile_pid',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                DB::beginTransaction();

                $jobSeeker = JobSeeker::where('profile_pid', $id)->first();

                if (!$jobSeeker) {
                    return (new ErrorResource("Job Seeker not found", 404))->response()->setStatusCode(404);
                }

                $jobSeeker->user_pid = $request->user_pid;
                $jobSeeker->full_name = $request->input('job_seeker_info.full_name', $jobSeeker->full_name);
                $jobSeeker->work_profile = $request->input('job_seeker_info.work_profile', $jobSeeker->work_profile);
                $jobSeeker->portfolio = $request->input('job_seeker_info.portfolio', $jobSeeker->portfolio);

                if ($files = $request->file('job_seeker_info.cv_path')) {
                    $destinationPath = public_path('/attachments/job_seeker/');
                    $cv = date('Ymd') . mt_rand(1000, 9999) . '.' . $files->getClientOriginalExtension();
                    $files->move($destinationPath, $cv);
                    $jobSeeker->cv_path = url('public/attachments/job_seeker/' . $cv);
                }

                $jobSeeker->save();

                // Update education info
                SeekerEduInfo::where('profile_pid', $id)->delete();
                $seekerEducationInfo = $request->input('education_info');
                foreach ($seekerEducationInfo as $data) {
                    $insertEduInfo = new SeekerEduInfo();
                    $insertEduInfo->profile_pid = $id;
                    $insertEduInfo->edu_dgree = $data["edu_dgree"];
                    $insertEduInfo->group_department = $data["group_department"];
                    $insertEduInfo->passing_year = $data["passing_year"];
                    $insertEduInfo->result_gpa = $data["result_gpa"];
                    $insertEduInfo->gpa_cgpa_outof = $data["gpa_cgpa_outof"];
                    $insertEduInfo->save();
                }

                // Update skill info
                JobSeekerSkill::where('profile_pid', $id)->delete();
                $seekerSkillInfo = $request->input('skill_info');
                foreach ($seekerSkillInfo as $data) {
                    $insertSkillInfo = new JobSeekerSkill();
                    $insertSkillInfo->profile_pid = $id;
                    $insertSkillInfo->skill_group = $data["skill_group"];
                    $insertSkillInfo->skill_category = $data["skill_category"];
                    $insertSkillInfo->save();
                }

                // Update work experience info
                JobSeekerExperience::where('profile_pid', $id)->delete();
                $seekerExperienceInfo = $request->input('work_experience_info');
                foreach ($seekerExperienceInfo as $data) {
                    $insertExperienceInfo = new JobSeekerExperience();
                    $insertExperienceInfo->profile_pid = $id;
                    $insertExperienceInfo->experience_title = $data["experience_title"];
                    $insertExperienceInfo->experience_desc = $data["experience_desc"];
                    $insertExperienceInfo->institution_name = $data["institution_name"];
                    $insertExperienceInfo->save();
                }

                // Update achievement info
                JobSeekerAchievement::where('profile_pid', $id)->delete();
                $seekerAchievementInfo = $request->input('achievement_info');
                foreach ($seekerAchievementInfo as $key => $data) {
                    $insertAchievementInfo = new JobSeekerAchievement();
                    $insertAchievementInfo->profile_pid = $id;
                    $insertAchievementInfo->achievment_title = $data["achievment_title"];
                    $attachedDocKey = 'achievement_info.' . $key . '.attached_doc';
                    if ($files = $request->file($attachedDocKey)) {
                        $destinationPath = public_path('/attachments/job_seeker/');
                        $attachment = date('Ymd') . mt_rand(1000, 9999) . '.' . $files->getClientOriginalExtension();
                        $files->move($destinationPath, $attachment);
                        $insertAchievementInfo->attached_doc = url('public/attachments/job_seeker/' . $attachment);
                    }
                    $insertAchievementInfo->save();
                }

                $jobSeeker = JobSeeker::with('workExperienceInfo')->with('skillInfo')->with('achievementInfo')->with('educationInfo')->where('profile_pid', $id)->first();

                DB::commit();
                return (new ApiCommonResponseResource($jobSeeker, "Job Seeker information updated", 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }


    // Get job seeker info by id
    public function getJobSeekerById($id)
    {
        try {
            $jobSeeker = JobSeeker::with('workExperienceInfo')
                ->with('skillInfo')
                ->with('achievementInfo')
                ->with('educationInfo')
                ->where('profile_pid', $id)
                ->orWhere('user_pid', $id)
                ->first();

            if (!$jobSeeker) {
                return (new ErrorResource("Job Seeker not found", 404))->response()->setStatusCode(404);
            }

            return (new ApiCommonResponseResource($jobSeeker, "Job Seeker fetched successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    public function jobPostStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_pid'          => 'required',
            'jobtitle'          => 'required|string|max:255',
            'provider_name'     => 'required|string|max:255',
            'workplace_type'    => 'required|in:on-site,remote,hybrid',
            'job_location'      => 'required',
            'job_type'          => 'required|in:Full time,Part Time,Contract,Temporary,Internship',
            'validdate'         => 'required',
            'jobdescription'    => 'required|string',
            'banner'            => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {


                DB::beginTransaction();
                // handle Student information.
                $insertJobPost = new JobPost();
                $insertJobPost->jobprovider_pid = $request->user_pid;
                $insertJobPost->jobtitle = $request->jobtitle;
                $insertJobPost->provider_name =  $request->provider_name;
                $insertJobPost->workplace_type =  $request->workplace_type;
                $insertJobPost->job_location =  $request->job_location;
                $insertJobPost->job_type =  $request->job_type;
                $insertJobPost->validdate =  $request->validdate;
                $insertJobPost->jobdescription =  $request->jobdescription;
                $insertJobPost->employmenttype =  $request->job_type;

                $file_path = 'attachments/job_banner/' . date('Ym') . '/';
                $createDirectory = public_path($file_path);
                if (!File::exists($createDirectory)) {
                    File::makeDirectory($createDirectory, 0777, true, true);
                    File::chmod($createDirectory, 0777);
                }
                if ($request->hasFile('banner')) {
                    $fileSlug = Str::slug($request->jobtitle);
                    $file = $request->file('banner');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move($createDirectory, $fileName);
                    $filePath = $file_path . $fileName;
                }

                $insertJobPost->file_url =  $filePath;
                $insertJobPost->save();
                $insertJobPost->jobpost_pid = JobPost::where('jobpost_id', $insertJobPost->jobpost_id)->pluck('jobpost_pid')->first();

                DB::commit();
                return (new ApiCommonResponseResource($insertJobPost, "Job post created successfully.", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    // Job post update
    public function updateJobPost(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_pid'          => 'required',
            'jobtitle'          => 'required|string|max:255',
            'provider_name'     => 'required|string|max:255',
            'workplace_type'    => 'required|in:on-site,remote,hybrid',
            'job_location'      => 'required',
            'job_type'          => 'required|in:Full time,Part Time,Contract,Temporary,Internship',
            'validdate'         => 'required',
            'jobdescription'    => 'required|string',
            'banner'            => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                DB::beginTransaction();

                $jobPost = JobPost::where('jobpost_pid', $id)->first();

                if (!$jobPost) {
                    return (new ErrorResource("Job post not found", 404))->response()->setStatusCode(404);
                }

                $jobPost->jobprovider_pid = $request->user_pid;
                $jobPost->jobtitle = $request->jobtitle;
                $jobPost->provider_name = $request->provider_name;
                $jobPost->workplace_type = $request->workplace_type;
                $jobPost->job_location = $request->job_location;
                $jobPost->job_type = $request->job_type;
                $jobPost->validdate = $request->validdate;
                $jobPost->jobdescription = $request->jobdescription;
                $jobPost->employmenttype = $request->job_type;

                $file_path = 'attachments/job_banner/' . date('Ym') . '/';
                $createDirectory = public_path($file_path);
                if (!File::exists($createDirectory)) {
                    File::makeDirectory($createDirectory, 0777, true, true);
                    File::chmod($createDirectory, 0777);
                }
                if ($request->hasFile('banner')) {
                    $fileSlug = Str::slug($request->jobtitle);
                    $file = $request->file('banner');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move($createDirectory, $fileName);
                    $filePath = $file_path . $fileName;
                    $jobPost->file_url =  $filePath;
                }
                $jobPost->update();

                DB::commit();
                return (new ApiCommonResponseResource($jobPost, "Job post updated successfully.", 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }


    // Delete job post
    public function deleteJobPost($id)
    {
        try {
            $jobPost = JobPost::where('jobpost_pid', $id)->first();

            if (!$jobPost) {
                return (new ErrorResource("Job post not found", 404))->response()->setStatusCode(404);
            }

            $jobPost->delete();

            return (new ApiCommonResponseResource(null, "Job post deleted successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    // Get job post by provider ID
    public function getJobsByProviderId($jobprovider_pid)
    {
        try {
            $jobs = JobPost::where('jobprovider_pid', $jobprovider_pid)
                ->orderBy('cre_date', 'desc')
                ->get(['jobpost_pid', 'jobtitle', 'workplace_type', 'job_location', 'job_type', 'validdate', 'jobdescription', 'jobprovider_pid', 'provider_name']);

            if ($jobs->isEmpty()) {
                return (new ErrorResource("No jobs found for this provider", 404))->response()->setStatusCode(404);
            } else {
                foreach ($jobs as $job) {

                    $jobProvider = DB::table('job_provider')->select('company_type')->where('jobprovider_pid', $job->jobprovider_pid)->first();

                    if ($jobProvider) {
                        $job->company_type = $jobProvider->company_type;
                    }
                }
            }

            return (new ApiCommonResponseResource($jobs, "Jobs fetched successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    public function getLatestJobs()
    {
        $jobs = JobPost::select('jobpost_pid', 'jobtitle', 'workplace_type', 'job_location', 'job_type', 'provider_name', 'jobdescription', 'jobprovider_pid', 'file_url')
            ->orderBy('cre_date', 'desc')
            ->take(6)
            ->get();

        foreach ($jobs as $job) {
            $jobProvider = DB::table('job_provider')->select('company_type')->where('jobprovider_pid', $job->jobprovider_pid)->first();

            if ($jobProvider) {
                $job->company_type = $jobProvider->company_type;
            }

            $job->file_url != '' ? $job->file_url = asset('public/' . $job->file_url) : null;
        }

        return (new ApiCommonResponseResource($jobs, "Latest jobs fetched successfully.", 200))->response()->setStatusCode(200);
    }

    public function getAllJobs()
    {
        $jobs = JobPost::select('jobpost_pid', 'jobtitle', 'workplace_type', 'job_location', 'job_type', 'provider_name', 'jobdescription', 'jobprovider_pid', 'file_url')
            ->orderBy('cre_date', 'desc')
            ->paginate(10);

        foreach ($jobs as $job) {
            $jobProvider = DB::table('job_provider')->select('company_type')->where('jobprovider_pid', $job->jobprovider_pid)->first();

            if ($jobProvider) {
                $job->company_type = $jobProvider->company_type;
            }

            $job->file_url != '' ? $job->file_url = asset('public/' . $job->file_url) : null;
        }

        return (new JobCollection($jobs, "All jobs fetched successfully.", 200))->response()->setStatusCode(200);
    }


    public function getJobById($id)
    {
        try {
            $job = JobPost::select('jobpost_id', 'jobpost_pid', 'jobtitle', 'workplace_type', 'job_location', 'job_type', 'validdate', 'jobdescription', 'employmenttype', 'active_status', 'jobprovider_pid', 'file_url')->where('jobpost_pid', $id)
                ->first();

            $job->file_url = $job->file_url ? asset('public/' . $job->file_url) : null;

            if (!$job) {
                return (new ErrorResource("Job not found", 404))->response()->setStatusCode(404);
            } else {
                $jobProvider = JobProvider::select('provider_name', 'designation', 'address_line', 'company_type', 'websites_name')->where('jobprovider_pid', $job->jobprovider_pid)->first();
                $job->provider_name = $jobProvider->provider_name;
                $job->designation = $jobProvider->designation;
                $job->address_line = $jobProvider->address_line;
                $job->company_type = $jobProvider->company_type;
                $job->websites_name = $jobProvider->websites_name;
            }

            return (new ApiCommonResponseResource($job, "Job fetched successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    public function searchJobsAndTasks(Request $request)
    {
        $searchTerm = $request->input('search_term');

        $jobs = JobPost::whereRaw('LOWER(jobtitle) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
            ->orWhereRaw('LOWER(provider_name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
            ->get(['jobpost_pid', 'jobtitle', 'provider_name', 'workplace_type', 'job_location', 'job_type', 'validdate', 'jobdescription', 'file_url'])
            ->map(function ($query) {
                $query['file_url'] = $query->file_url ? asset('public/' . $query->file_url) : null;
                return $query;
            });

        $tasks = TaskPost::where('jobtitle', 'LIKE', "%{$searchTerm}%")
            ->get(['taskpost_pid', 'jobtitle', 'duration', 'email', 'jobdescription']);

        $result = [
            'jobs' => $jobs,
            'tasks' => $tasks,
        ];

        return (new ApiCommonResponseResource($result, "Search results fetched successfully.", 200))->response()->setStatusCode(200);
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


    /**
     * Remove the specified educatmap from storage.
     */
    public function destroy_job_edu(string $educatmap_pid)
    {
        $educatmap = SeekerEduInfo::where('educatmap_pid', $educatmap_pid)->first();

        if (!$educatmap) {
            return (new ErrorResource("Job Educational info not found !!", 404))->response()->setStatusCode(404);
        }

        $educatmap->delete();
        return (new ApiCommonResponseResource($educatmap, "Job Educational Info Deleted Successfully", 200))->response()->setStatusCode(200);
    }


    /**
     * Remove the specified job_skill from storage.
     */
    public function destroy_job_skill(string $skillmap_pid)
    {
        $job_skill = JobSeekerSkill::where('skillmap_pid', $skillmap_pid)->first();

        if (!$job_skill) {
            return (new ErrorResource("Job Skill info not found !!", 404))->response()->setStatusCode(404);
        }

        $job_skill->delete();
        return (new ApiCommonResponseResource($job_skill, "Job Skill Info Deleted Successfully", 200))->response()->setStatusCode(200);
    }


    /**
     * Remove the specified job_exp from storage.
     */
    public function destroy_job_exp(string $experiencemap_pid)
    {
        $job_exp = JobSeekerExperience::where('experiencemap_pid', $experiencemap_pid)->first();

        if (!$job_exp) {
            return (new ErrorResource("Job Experience info not found !!", 404))->response()->setStatusCode(404);
        }

        $job_exp->delete();
        return (new ApiCommonResponseResource($job_exp, "Job Experience Info Deleted Successfully", 200))->response()->setStatusCode(200);
    }


    /**
     * Remove the specified job_achi from storage.
     */
    public function destroy_job_achievement(string $achievmentmap_pid)
    {
        $job_achi = JobSeekerAchievement::where('achievmentmap_pid', $achievmentmap_pid)->first();

        if (!$job_achi) {
            return (new ErrorResource("Job Achievement info not found !!", 404))->response()->setStatusCode(404);
        }

        $job_achi->delete();
        return (new ApiCommonResponseResource($job_achi, "Job Achievement Info Deleted Successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * Get All Job Provider info
     * @api Job Provider
     * @author shohag <shohag@atilimited.net>
     * @param  [type]  $id
     * @return void
     */
    public function getJobProvider($need = null)
    {
        try {
            if ($need == null) {
                $jobProvider = JobProvider::all();
            } else {
                $jobProvider = JobProvider::paginate($need);
            }

            if (!$jobProvider) {
                return (new ErrorResource("Job Provider not found", 404))->response()->setStatusCode(404);
            }

            return (new ApiCommonResponseResource($jobProvider, "Job Provider fetched successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something was wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Get All Job Seeker info
     * @api Job Seeker
     * @author shohag <shohag@atilimited.net>
     * @param  [type]  $id
     * @return void
     */
    public function getJobSeeker($need = null)
    {
        try {
            if ($need == null) {
                $jobSeeker = JobSeeker::all();
            } else {
                $jobSeeker = JobSeeker::paginate($need);
            }

            if (!$jobSeeker) {
                return (new ErrorResource("Job Seeker not found", 404))->response()->setStatusCode(404);
            }

            return (new ApiCommonResponseResource($jobSeeker, "Job Seeker fetched successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something was wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
