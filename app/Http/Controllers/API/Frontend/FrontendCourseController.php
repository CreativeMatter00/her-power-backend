<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\CourseCollection;
use App\Http\Resources\ErrorResource;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentEnroll;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrontendCourseController extends Controller
{
    public function getCourse(Request $request)
    {
        try {

            $queryParam = $request->query('courseType');
            $serverUrl = asset('/public');

            $query = Course::query()
                ->select(
                    'trn_course.course_pid',
                    'trn_course.course_type',
                    'trn_course.course_title',
                    'trn_course.thumbnail',
                    'trn_mentor.mentor_pid',
                    'trn_mentor.full_name',
                    'trn_providor.providor_pid',
                    'trn_providor.providor_name',
                    'trn_providor.second_name',
                    'trn_providor.mobile_no',
                    'trn_providor.email_id',
                    'trn_providor.website_address',
                    'trn_providor.address_line',
                    'trn_providor.trade_licence',
                    'trn_providor.vat_reg_id',
                    'trn_providor.tax_reg_id',
                    'trn_providor.tin_number'
                )
                ->leftJoin('trn_mentor', 'trn_course.mentor_pid', '=', 'trn_mentor.mentor_pid')
                ->leftJoin('trn_providor', 'trn_course.providor_pid', '=', 'trn_providor.providor_pid')
                ->where('trn_course.active_status', 1)
                ->orderBy('trn_course.course_id', 'desc');

            // condition
            if ($queryParam) {
                $query->where('trn_course.course_type', ucfirst($queryParam));
            }

            // pagination
            $getCourseData = $query->paginate(12);

            $getCourseData->getCollection()->transform(function ($item) use ($serverUrl) {
                $item->thumbnail = $serverUrl . '/' . $item->thumbnail;
                return $item;
            });

            return (new CourseCollection($getCourseData, "Data fetched successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }

    public function getCourseDetails($courseidAndStudentId)
    {




        try {
            $exploedData = explode(",", $courseidAndStudentId);
            $courseid = $exploedData[0];
            $studentid = isset($exploedData[1]) ? $exploedData[1] : null;
            $serverUrl = asset('/public');

            $getCourseData = DB::selectOne("SELECT
                                                    course_pid,
                                                    course_type,
                                                    course_title,
                                                    description,
                                                    course_price,
                                                    mentor_pid,
                                                    full_name,
                                                    providor_pid,
                                                    providor_name,
                                                    second_name,
                                                    mobile_no,
                                                    email_id,
                                                    website_address,
                                                    address_line,
                                                    trade_licence,
                                                    vat_reg_id,
                                                    tax_reg_id,
                                                    tin_number,
                                                    thumbnail,
                                                    banner,
                                                    CASE
                                                        WHEN is_enrolled > 0 THEN 'TRUE'
                                                        ELSE 'FALSE'
                                                    END AS already_enrolled
                                                FROM
                                                    (
                                                        SELECT
                                                            co.course_pid,
                                                            co.course_type,
                                                            co.course_title,
                                                            co.description,
                                                            co.course_price,
                                                            me.mentor_pid,
                                                            me.full_name,
                                                            tp.providor_pid,
                                                            tp.providor_name,
                                                            tp.second_name,
                                                            tp.mobile_no,
                                                            tp.email_id,
                                                            tp.website_address,
                                                            tp.address_line,
                                                            tp.trade_licence,
                                                            tp.vat_reg_id,
                                                            tp.tax_reg_id,
                                                            tp.tin_number,
                                                            CONCAT('$serverUrl/', co.thumbnail) as thumbnail,
                                                            CONCAT('$serverUrl/', co.image) as banner,
                                                            (
                                                                select
                                                                    count(enroll_id) as is_enrolled
                                                                from
                                                                    trn_student_enroll a
                                                                where
                                                                    a.course_pid = ?
                                                                    and a.student_pid = ?
                                                            ) is_enrolled
                                                        FROM
                                                            trn_course co
                                                            LEFT JOIN trn_mentor me on co.mentor_pid = me.mentor_pid
                                                            LEFT JOIN trn_providor tp on co.providor_pid = tp.providor_pid
                                                        WHERE
                                                            co.course_pid = ?
                                                            AND co.active_status = 1) DATA", [$courseid, $studentid, $courseid,]);

            return (new ApiCommonResponseResource((array) $getCourseData, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }

    public function getCourseByMentor($mentorid)
    {

        try {
            $serverUrl = asset('/public');
            $getCourseData = DB::select("SELECT
                                            me.mentor_pid,
                                            me.full_name,
                                            tp.providor_pid,
                                            tp.providor_name,
                                            tp.second_name,
                                            tp.mobile_no,
                                            tp.email_id,
                                            tp.website_address,
                                            tp.address_line,
                                            tp.trade_licence,
                                            tp.vat_reg_id,
                                            tp.tax_reg_id,
                                            tp.tin_number,
                                            co.course_pid,
                                            co.course_type,
                                            co.course_title,
                                            CONCAT('$serverUrl/', co.thumbnail) as thumbnail

                                        FROM
                                            trn_course co
                                            LEFT JOIN trn_mentor me on co.mentor_pid = me.mentor_pid
                                            LEFT JOIN trn_providor tp on co.providor_pid = tp.providor_pid
                                            WHERE co.mentor_pid = ?
                                            AND co.active_status = 1
                                        ORDER BY
                                        co.course_id DESC", [$mentorid]);


            return (new ApiCommonResponseResource((array) $getCourseData, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }


    public function getStudentEnrolledCourse($studentId)
    {


        try {
            $serverUrl = asset('/public');
            $getCourseData = DB::select("SELECT
                                            me.mentor_pid,
                                            me.full_name,
                                            tp.providor_pid,
                                            tp.providor_name,
                                            tp.second_name,
                                            tp.mobile_no,
                                            tp.email_id,
                                            tp.website_address,
                                            tp.address_line,
                                            tp.trade_licence,
                                            tp.vat_reg_id,
                                            tp.tax_reg_id,
                                            tp.tin_number,
                                            co.course_pid,
                                            co.course_type,
                                            co.course_title,
                                            CONCAT('$serverUrl/', co.thumbnail) as thumbnail
                                        FROM
                                            trn_course co
                                            LEFT JOIN trn_mentor me on co.mentor_pid = me.mentor_pid
                                            LEFT JOIN trn_student_enroll se on co.course_pid = se.course_pid
                                            LEFT JOIN trn_providor tp on co.providor_pid = tp.providor_pid
                                            WHERE se.student_pid = ?
                                        ORDER BY
                                            co.course_id DESC", [$studentId]);


            return (new ApiCommonResponseResource((array) $getCourseData, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }

    public function getStudentEnrolled($need = null)
    {
        try {
            
            if ($need == null) {
                $data = Student::whereIn('student_pid', StudentEnroll::select('student_pid')->get())->get();
            }else{
                $data = Student::whereIn('student_pid', StudentEnroll::select('student_pid')->get())->paginate($need);
            }

            return (new ApiCommonResponseResource($data, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oop! Something was wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }

    public function studentCourseEnrollment(Request $request)
    {

        $course_pid = $request->course_pid;
        $student_pid = $request->student_pid;

        $is_exits_course = StudentEnroll::where('course_pid', $course_pid)
            ->where('student_pid', $student_pid)
            ->first();

        if (!$is_exits_course) {
            try {

                DB::beginTransaction();

                // handle Course  information.
                $insertData = new StudentEnroll();
                $insertData->course_pid = $course_pid;
                $insertData->student_pid = $student_pid;
                $insertData->save();

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Enrolled successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        } else {
            return (new ApiCommonResponseResource($is_exits_course, "You are already enrolled in this course!", 409))->response()->setStatusCode(409);
        }
    }


    public function searchCourseMentorOrTittleWise(Request $request)
    {

        try {

            $queryParam = $request->query('searchQuery');
            $query = strtoupper($queryParam);
            $query2 = strtoupper($queryParam);
            $serverUrl = asset('/public');
            $getCourseData = DB::select("SELECT
                                            me.mentor_pid,
                                            me.full_name,
                                            tp.providor_pid,
                                            tp.providor_name,
                                            tp.second_name,
                                            tp.mobile_no,
                                            tp.email_id,
                                            tp.website_address,
                                            tp.address_line,
                                            tp.trade_licence,
                                            tp.vat_reg_id,
                                            tp.tax_reg_id,
                                            tp.tin_number,
                                            co.course_pid,
                                            co.course_type,
                                            co.course_title,
                                            CONCAT('$serverUrl/', co.thumbnail) as thumbnail
                                        FROM
                                            trn_course co
                                            LEFT JOIN trn_mentor me ON co.mentor_pid = me.mentor_pid
                                            LEFT JOIN trn_providor tp on co.providor_pid = tp.providor_pid
                                        WHERE
                                           UPPER(co.course_title) LIKE '%' || ? || '%'

                                           OR UPPER(me.full_name) LIKE '%' || ? || '%'
                                        ORDER BY
                                            co.course_id DESC", [$query, $query2]);


            return (new ApiCommonResponseResource((array) $getCourseData, "Data fetched", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }


    public function course_by_user_pid(string $user_pid)
    {
        try {
            $serverUrl = asset('/public');

            $query = Course::query()
                ->select(
                    'trn_course.course_pid',
                    'trn_course.course_type',
                    'trn_course.course_title',
                    'trn_course.thumbnail',
                    'trn_mentor.mentor_pid',
                    'trn_mentor.full_name',
                    'trn_providor.providor_pid',
                    'trn_providor.providor_name',
                    'trn_providor.second_name',
                    'trn_providor.mobile_no',
                    'trn_providor.email_id',
                    'trn_providor.website_address',
                    'trn_providor.address_line',
                    'trn_providor.trade_licence',
                    'trn_providor.vat_reg_id',
                    'trn_providor.tax_reg_id',
                    'trn_providor.tin_number'
                )
                ->leftJoin('trn_mentor', 'trn_course.mentor_pid', '=', 'trn_mentor.mentor_pid')
                ->leftJoin('trn_providor', 'trn_course.providor_pid', '=', 'trn_providor.providor_pid')
                ->where('trn_course.providor_pid', $user_pid)
                ->where('trn_course.active_status', 1)
                ->orderBy('trn_course.course_id', 'desc')
                ->get();


            $query->transform(function ($item) use ($serverUrl) {
                $item->thumbnail = $serverUrl . '/' . $item->thumbnail;
                return $item;
            });

            return (new ApiCommonResponseResource($query, "Data fetched successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 404))->response()->setStatusCode(404);
        }
    }
}
