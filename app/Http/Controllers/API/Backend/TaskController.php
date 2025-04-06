<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
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
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
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

  


    public function taskStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_pid' => 'required',
            'jobtitle' => 'required|string|max:255',
            'duration' => 'required|string|max:255',
            'email' => 'required|email',
            'jobdescription' => 'required',
            // 'remarks' => 'required',

        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                // handle Student information.
                $insertTaskPost = new TaskPost();
                $insertTaskPost->jobprovider_pid = $request->user_pid;
                $insertTaskPost->jobtitle = $request->jobtitle;
                $insertTaskPost->duration =  $request->duration;
                $insertTaskPost->email =  $request->email;
                $insertTaskPost->jobdescription =  $request->jobdescription;
                $insertTaskPost->remarks =  $request->remarks;
                $insertTaskPost->save();
                $insertTaskPost->taskpost_pid = DB::table('job_taskposts')->where('taskpost_id', $insertTaskPost->taskpost_id)->pluck('taskpost_pid')->first();
               

                DB::commit();
                return (new ApiCommonResponseResource($insertTaskPost, "Task created successfully.", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    // update task
    public function updateTask(Request $request, $taskpost_pid)
    {
        $validator = Validator::make($request->all(), [
            'user_pid' => 'required',
            'jobtitle' => 'sometimes|required|string|max:255',
            'duration' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email',
            'jobdescription' => 'sometimes|required',
            'remarks' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                DB::beginTransaction();

                $taskPost = TaskPost::where('taskpost_pid', $taskpost_pid)->first();

                if (!$taskPost) {
                    return (new ErrorResource("Task not found.", 404))->response()->setStatusCode(404);
                }

                $taskPost->jobprovider_pid = $request->get('user_pid', $taskPost->jobprovider_pid);
                $taskPost->jobtitle = $request->get('jobtitle', $taskPost->jobtitle);
                $taskPost->duration = $request->get('duration', $taskPost->duration);
                $taskPost->email = $request->get('email', $taskPost->email);
                $taskPost->jobdescription = $request->get('jobdescription', $taskPost->jobdescription);
                $taskPost->remarks = $request->get('remarks', $taskPost->remarks);
                $taskPost->save();

                DB::commit();
                return (new ApiCommonResponseResource($taskPost, "Task updated successfully.", 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    // Delete task by ID.
    public function deleteTask($taskpost_pid)
    {
        try {
            DB::beginTransaction();

            $taskPost = TaskPost::where('taskpost_pid', $taskpost_pid)->first();

            if (!$taskPost) {
                return (new ErrorResource("Task not found.", 404))->response()->setStatusCode(404);
            }

            $taskPost->delete();

            DB::commit();
            return (new ApiCommonResponseResource(null, "Task deleted successfully.", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    /**
     * Fetch the latest 4 tasks.
     */
    public function getLatestTasks()
    {
        $tasks = TaskPost::select('taskpost_pid', 'jobtitle', 'jobdescription', 'duration')
            ->orderBy('cre_date', 'desc')
            ->take(4)
            ->get();

        return (new ApiCommonResponseResource($tasks, "Latest tasks fetched successfully.", 200))->response()->setStatusCode(200);
    }

    /**
     * Fetch all tasks with pagination.
     */
    public function getAllTasks(Request $request)
    {
        $tasks = TaskPost::select('taskpost_pid', 'jobtitle', 'jobdescription', 'duration')
            ->orderBy('cre_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return (new TaskCollection($tasks, "Latest tasks fetched successfully.", 200))->response()->setStatusCode(200);
    }

    /**
     * Get task by ID.
     */
    public function getTaskById($id)
    {
        $task = TaskPost::where('taskpost_pid', $id)->first();

        if (!$task) {
            return (new ErrorResource("Task not found.", 404))->response()->setStatusCode(404);
        }

        return (new ApiCommonResponseResource($task, "Task fetched successfully.", 200))->response()->setStatusCode(200);
    }

    // Get task by job provider ID.
    public function getTasksByJobProviderId($jobProviderId)
    {
        $tasks = TaskPost::where('jobprovider_pid', $jobProviderId)
            ->orderBy('cre_date', 'desc')
            ->get();

        if ($tasks->isEmpty()) {
            return (new ErrorResource("No tasks found for this job provider.", 404))->response()->setStatusCode(404);
        }

        return (new ApiCommonResponseResource($tasks, "Tasks fetched successfully.", 200))->response()->setStatusCode(200);
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
