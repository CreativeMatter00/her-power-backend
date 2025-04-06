<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\BranchWiseCourse;
use App\Models\Course;
use App\Models\CourseProvider;
use App\Models\Customer;
use App\Models\EduInfo;
use App\Models\Seller;
use App\Models\Student;
use App\Service\ImageUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
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
    public function store(Request $request, ImageUploadService $imageUploadService)
    {

        $validator = Validator::make($request->all(), [
            'category_pid' => 'required',
            'providor_pid' => 'required',
            'course_type' => 'required',
            'course_title' => 'required',
            'thumbnail' => 'required',
            'image' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle Course  information.

                $insertData = new Course();
                $insertData->category_pid = $request->category_pid;
                $insertData->providor_pid = $request->providor_pid;
                $insertData->course_type = $request->course_type;
                $insertData->course_title = $request->course_title;
                $insertData->description = $request->description;
                $insertData->activation_type = $request->activation_type;
                $insertData->course_price = $request->course_price;
                if ($request->hasFile('thumbnail')) {
                    $directory = 'attachments/course/' . $request->mentor_pid . '/';
                    $createDirectory = public_path($directory);
                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                        File::chmod($createDirectory, 0777);
                    }
                    $fileSlug = Str::slug($request->course_title);
                    $file = $request->file('thumbnail');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move(public_path($directory), $fileName);
                    $filePath = $directory . $fileName;
                    $insertData->thumbnail = $filePath;
                }
                if ($request->hasFile('image')) {

                    $directory = 'attachments/course/' . $request->mentor_pid . '/';
                    $createDirectory = public_path($directory);
                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                        File::chmod($createDirectory, 0777);
                    }
                    $fileSlug = Str::slug($request->course_title);
                    $file = $request->file('image');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move(public_path($directory), $fileName);
                    $filePath = $directory . $fileName;
                    $insertData->image = $filePath;
                }
                $insertData->save();

                $insertData->thumbnail =  asset('/public') . '/' . $insertData->thumbnail;
                $insertData->image =  asset('/public') . '/' . $insertData->image;
                $coursepID = Course::select('course_pid')->where('course_id', $insertData->course_id)->first();
                $branchID = $request->branch_id;
                if ($branchID) {
                    foreach ($branchID as $branch) {
                        $insertBranchCourse =  new BranchWiseCourse();
                        $insertBranchCourse->course_pid = $coursepID->course_pid;
                        $insertBranchCourse->providor_pid = $request->providor_pid;
                        $insertBranchCourse->branch_pid = $branch;
                        $insertBranchCourse->save();
                    }
                }
                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Course created successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Course adding failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        // return $request->all();
        $validator = Validator::make($request->all(), [
            // 'category_pid' => 'required',
            // 'providor_pid' => 'required',
            // 'course_type' => 'required',
            // 'course_title' => 'required',
            // 'thumbnail' => 'required',
            // 'image' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle Course  information.

                $updateData = Course::where('course_pid', $id)->first();
                $request->category_pid ? $updateData->category_pid = $request->category_pid : null;
                $request->providor_pid ? $updateData->providor_pid = $request->providor_pid : null;
                $request->course_type ? $updateData->course_type = $request->course_type : null;
                $request->course_title ? $updateData->course_title = $request->course_title : null;
                $request->description ? $updateData->description = $request->description : null;
                $request->activation_type ? $updateData->activation_type = $request->activation_type : null;
                $request->course_price ? $updateData->course_price = $request->course_price : null;

                if ($request->hasFile('thumbnail')) {
                    $directory = 'attachments/course/' . $request->mentor_pid . '/';
                    $createDirectory = public_path($directory);
                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                        File::chmod($createDirectory, 0777);
                    }
                    $fileSlug = Str::slug($request->course_title);
                    $file = $request->file('thumbnail');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move(public_path($directory), $fileName);
                    $filePath = $directory . $fileName;
                    $updateData->thumbnail = $filePath;
                }

                if ($request->hasFile('image')) {

                    $directory = 'attachments/course/' . $request->mentor_pid . '/';
                    $createDirectory = public_path($directory);
                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                        File::chmod($createDirectory, 0777);
                    }
                    $fileSlug = Str::slug($request->course_title);
                    $file = $request->file('image');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move(public_path($directory), $fileName);
                    $filePath = $directory . $fileName;
                    $updateData->image = $filePath;
                }
                $updateData->update();

                $updateData->thumbnail =  asset('/public') . '/' . $updateData->thumbnail;
                $updateData->image =  asset('/public') . '/' . $updateData->image;
                $coursepID = Course::select('course_pid')->where('course_id', $updateData->course_id)->first();
                $branch_info = json_decode($request->branch_info);
                if ($branch_info) {
                    foreach ($branch_info as $branch) {
                        $updateBranchCourse = BranchWiseCourse::where('branchcourse_pid', $branch->branchcourse_pid)->first();
                        $updateBranchCourse->course_pid = $coursepID->course_pid;
                        $updateBranchCourse->providor_pid = $request->providor_pid;
                        $branch->branch_pid ? $updateBranchCourse->branch_pid = $branch->branch_pid : null;
                        $updateBranchCourse->update();
                    }
                }
                DB::commit();
                return (new ApiCommonResponseResource($updateData, "Course Updated successfully", 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Course updating failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $course = Course::where('course_pid', $id)->first();

        if ($course) {
            $course->update([
                'active_status' => 0
            ]);
            return (new ApiCommonResponseResource($course, "Course Deleted successfully", 200))->response()->setStatusCode(200);
        } else {
            return (new ErrorResource("No Course Found !!", 404))->response()->setStatusCode(404);
        }
    }


    /**
     * @param string $course_id
     *
     * @return [object]
     */
    public function get_course_by_student(string $course_id)
    {
        $course_by_student = Course::with('students_enroll')->where('course_pid', $course_id)->first();

        if (!$course_by_student) {
            return (new ErrorResource("No Course Found !!", 404))->response()->setStatusCode(404);
        }

        foreach ($course_by_student->students_enroll as $student) {
            $student_info = Student::where('student_pid', $student->student_pid)->first();
            $user_info = Customer::where('user_pid', $student_info->ref_user_pid)->first();
            if (!$user_info) {
                $user_info = Seller::where('user_pid', $student_info->ref_user_pid)->first();
            }
            $edu_info = EduInfo::where('ref_student_pid', $student_info->student_pid)->get();
            $student->full_name = $student_info->full_name;
            $student->mobile_no = $student_info->mobile_no;
            $student->email     = $student_info->email_id;
            $student->gender    = $student_info->gender;
            $student->dob       = $student_info->dob;
            $student->address   = $user_info->house_number . ', ' .
                $user_info->street_name . ', ' .
                $user_info->area_name . ', ' .
                $user_info->city_name . '-' .
                $user_info->zip_postal_code . '.';
            $student->edu_info = $edu_info;
        }

        return (new ApiCommonResponseResource($course_by_student, "Data fatch successfully", 200))->response()->setStatusCode(200);
    }


    public function provider_details(string $provider_pid)
    {
        $provider_details = CourseProvider::with('attachments')->where('providor_pid', $provider_pid)->first();
        $course = count(Course::where('providor_pid', $provider_pid)->get());

        if (!$provider_details) {
            return (new ErrorResource("No Data Found !!", 404))->response()->setStatusCode(404);
        }

        $profile_url = count($provider_details->attachments) > 0 ? asset('public/' . $provider_details->attachments[0]->file_url) : null;
        $provider_details->profile_url = $profile_url;
        $provider_details->total_course = $course;

        $provider_details = $provider_details->only([
            'providor_id',
            'providor_pid',
            'providor_name',
            'ref_user_pid',
            'profile_url',
            'total_course'
        ]);

        return (new ApiCommonResponseResource($provider_details, "Data fatch successfully", 200))->response()->setStatusCode(200);
    }
}
