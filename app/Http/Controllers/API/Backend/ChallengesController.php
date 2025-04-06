<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ChallengePostResource;
use App\Http\Resources\ErrorResource;
use App\Models\Challenge;
use App\Models\ChallengePostAttachment;
use App\Service\AttachmentService;
use App\Service\ImageUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChallengesController extends Controller
{
    /**
     * @api Challenge Post get all data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 22/02/2025
     */
    public function index()
    {
        $data = Challenge::with('documents')->where('active_status', 1)->orderBy('cre_date', 'desc')->paginate(12);

        if (empty($data)) {
            return (new ErrorResource('Sorry! Challenge Post not found.', 400))->response()->setStatusCode(400);
        }

        return AttachmentService::returnWithChallengeBannerAndThumbnail($data, 'Challenge Post');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @api challenge post data store
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 22/02/2025
     */
    public function store(Request $request)
    {
        // empty Check
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        // validation
        $validation = Validator::make($request->all(), [
            'user_pid'      => 'required',
            'title'         => 'required',
            'description'   => 'required',
            'banner'        => 'required',
            'thumbnail'     => 'required'
        ]);


        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // store process
                $insertData = new Challenge();
                $insertData->user_pid = $request->user_pid;
                $insertData->title = $request->title;
                $insertData->description = $request->description;
                // $insertData->file_path = $request->file_path;
                // $insertData->ud_serialno = $request->ud_serialno;
                // $insertData->remarks = $request->remarks;
                // $insertData->cre_by = Auth::user()->user_pid;                     @important
                $insertData->active_status = $request->active_status ?? 1;
                // $insertData->unit_no = $request->unit_no;
                $insertData->save();

                // take reference
                $cpost_pid = Challenge::where('cpost_id', $insertData->cpost_id)->pluck('cpost_pid')->first();

                // banner images upload process
                $banner_directory = 'attachments/challenge_post_banner/' . now()->format('Ymd') . '/';
                $storeBanImage = ImageUploadService::challengesBannerImage($request, $request->title, $banner_directory, 'banner', $cpost_pid, "challenge_post_banner");
                if ($storeBanImage != 200) {
                    return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with banner image upload');
                }

                // thumnail images upload process
                $thumbnail_directory = 'attachments/challenge_post_thumbnail/' . now()->format('Ymd') . '/';
                $storeThumImage = ImageUploadService::challengesThumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $cpost_pid, "challenge_post_thumbnail");
                if ($storeThumImage != 200) {
                    return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with thumbnail image upload');
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Challenge post added successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Challenge Post adding failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (empty($id)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        $data = Challenge::with('documents')->where('cpost_pid', $id)->where('active_status', 1)->first();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Challenge Post data not found.', 400))->response()->setStatusCode(400);
        }

        return (new ChallengePostResource($data, "Challenge Post data fetch successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * @api challenge post data update
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 22/02/2025
     */
    public function update(Request $request, string $cpost_pid)
    {
        // empty Check
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $exist_story = Challenge::where('cpost_pid', $cpost_pid)->where('active_status', 1)->first();
        if (!$exist_story) {
            return (new ErrorResource('Sorry, Challenge Post data not found!', 404))->response()->setStatusCode(404);
        }

        // validation
        $validation = Validator::make($request->all(), []);

        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // store process
                $updateData = $exist_story;
                $updateData->user_pid = $request->user_pid;
                $updateData->title = $request->title;
                $updateData->description = $request->description;
                // $updateData->file_path = $request->file_path;
                // $updateData->ud_serialno = $request->ud_serialno;
                // $updateData->remarks = $request->remarks;
                // $updateData->cre_by = Auth::user()->user_pid;                     @important
                $updateData->active_status = $request->active_status ?? 1;
                // $updateData->unit_no = $request->unit_no;
                $updateData->save();

                // take reference
                $cpost_pid = Challenge::where('cpost_id', $updateData->cpost_id)->pluck('cpost_pid')->first();

                if (request()->hasFile('banner')) {
                    ChallengePostAttachment::where('ref_object_name', 'challenge_post_banner')->where('ref_pid', $cpost_pid)->first()->delete();
                    // banner images upload process
                    $banner_directory = 'attachments/challenge_post_banner/' . now()->format('Ymd') . '/';
                    $storeBanImage = ImageUploadService::challengesBannerImage($request, $request->title, $banner_directory, 'banner', $cpost_pid, "challenge_post_banner");
                    if ($storeBanImage != 200) {
                        return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with banner image upload');
                    }
                }

                if (request()->hasFile('thumbnail')) {
                    ChallengePostAttachment::where('ref_object_name', 'challenge_post_thumbnail')->where('ref_pid', $cpost_pid)->first()->delete();
                    // thumnail images upload process
                    $thumbnail_directory = 'attachments/challenge_post_thumbnail/' . now()->format('Ymd') . '/';
                    $storeThumImage = ImageUploadService::challengesThumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $cpost_pid, "challenge_post_thumbnail");
                    if ($storeThumImage != 200) {
                        return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with thumbnail image upload');
                    }
                }

                DB::commit();
                return (new ApiCommonResponseResource($updateData, "Challenge post updated successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Challenge Post update request failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $cpost_pid)
    {
        // check param
        if (empty($cpost_pid)) {
            return (new ErrorResource('Sorry, Specification Needed for this request. The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = Challenge::where('active_status', 1)->where('cpost_pid', $cpost_pid)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // update status
        $is_exist->update(['active_status' => 0]);

        return (new ApiCommonResponseResource($is_exist, "Challenge Post Deleted Successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * @api Challenge Post get homepage data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public function homepage()
    {
        $data = Challenge::with('documents')->where('active_status', 1)->orderBy('cre_date', 'desc')->take(9)->get();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Challenge Post not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnWithChallengeBannerAndThumbnail($data, 'Challenge Post');
        return $result;
    }
}
