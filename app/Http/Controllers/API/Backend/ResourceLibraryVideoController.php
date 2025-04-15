<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\ResourceLibraryVideoResource;
use App\Models\Document;
use App\Models\ResourceLibraryVideo;
use App\Service\AttachmentService;
use App\Service\ImageUploadService;
use App\Service\VideoUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResourceLibraryVideoController extends Controller
{
    /**
     * Resource Library Video Store Method
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 18/01/2025
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
            'thumbnail'      => 'required',
            'video'         => 'required',
        ]);

        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {

            try {
                DB::beginTransaction();
                $insertData = new ResourceLibraryVideo();
                $insertData->user_pid = $request->user_pid;
                $insertData->title = $request->title;
                $insertData->description = $request->description;
                $insertData->post_type = 'Video';
                // $insertData->cre_by = Auth::user()->user_pid;                     @important
                $insertData->active_status = $request->active_status ?? 1;
                $insertData->save();

                // take reference
                $post_pid = ResourceLibraryVideo::where('post_id', $insertData->post_id)->pluck('post_pid')->first();

                // thumbnail upload process
                $thumbnail_directory = 'attachments/resource_video_thumbnail/' . now()->format('Ymd') . '/';

                $storeImage = ImageUploadService::thumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $post_pid, "resource_video_thumbnail");
                if ($storeImage != 200) {
                    return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Video Image Upload');
                }

                // Video upload process
                $video_directory = 'attachments/resource_video/' . now()->format('Ymd') . '/';
                $storeImage = VideoUploadService::videoUpload($request, $request->title, $video_directory, 'video', $post_pid, "resource_video");
                if ($storeImage != 200) {
                    return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Video Image Upload');
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Video added successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Resource Library Video Update Method
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 18/01/2024
     */
    public function update(Request $request, string $id)
    {
        // empty Check
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = ResourceLibraryVideo::where('post_pid', $id)->where('post_type', 'Video')->where('active_status', 1)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // validation
        $validation = Validator::make($request->all(), [
            // 'user_pid'      => 'required',
            // 'title'         => 'required',
            // 'thumbnail'      => 'required',
            // 'video'         => 'required',
        ]);

        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                $updateData = ResourceLibraryVideo::where('post_pid', $id)->where('post_type', 'Video')->where('active_status', 1)->first();
                $request->user_pid ? $updateData->user_pid = $request->user_pid : null;
                $request->title ? $updateData->title = $request->title : null;
                $request->description ? $updateData->description = $request->description : null;
                // $updateData->cre_by = Auth::user()->user_pid;                     @important
                $updateData->active_status = $request->active_status ?? 1;
                $updateData->save();

                // take reference
                $post_pid = ResourceLibraryVideo::where('post_id', $updateData->post_id)->pluck('post_pid')->first();

                if (!empty($request->thumbnail)) {
                    Document::where('ref_pid', $id)->where('ref_object_name', 'resource_video_thumbnail')->delete();

                    // thumbnail upload process
                    $thumbnail_directory = 'attachments/resource_video_thumbnail/' . now()->format('Ymd') . '/';
                    $storeImage = ImageUploadService::thumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $post_pid, "resource_video_thumbnail");
                    if ($storeImage != 200) {
                        return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Video Image Upload');
                    }
                }

                if (!empty($request->video)) {
                    Document::where('ref_pid', $id)->where('ref_object_name', 'resource_video')->delete();

                    // Video upload process
                    $thumbnail_directory = 'attachments/resource_video/' . now()->format('Ymd') . '/';
                    $storeImage = VideoUploadService::videoUpload($request, $request->title, $thumbnail_directory, 'video', $post_pid, "resource_video");
                    if ($storeImage != 200) {
                        return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Video Image Upload');
                    }
                }

                DB::commit();
                return (new ApiCommonResponseResource($updateData, "Video updated successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Resource video list for all videos
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 18/01/2024
     */
    public function allVideos()
    {
        $data = ResourceLibraryVideo::with('documents')
            ->where('post_type', 'Video')
            ->where('active_status', 1)
            ->orderBy('cre_date', 'desc')
            ->paginate(12);

        if (empty($data)) {
            return (new ErrorResource('Sorry! Video not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnWithThumbnailAndVideo($data, 'Video');
        return $result;
    }

    /**
     * Resource video list for homepage
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 18/01/2024
     */
    public function homepage()
    {
        $data = ResourceLibraryVideo::with('documents')
            ->where('post_type', 'Video')
            ->where('active_status', 1)
            ->orderBy('cre_date', 'desc')
            ->take(6)
            ->get();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Video not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnWithThumbnailAndVideo($data, 'Video');
        return $result;
    }

    /**
     * @api Video data Delete by pid
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 20/01/2025
     */
    public function destroy(string $post_pid = '', string $user_pid = '')
    {
        // check param
        if (empty($post_pid) || empty($user_pid)) {
            return (new ErrorResource('Sorry, Specification Needed for this request. The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = ResourceLibraryVideo::where('user_pid', $user_pid)->where('post_pid', $post_pid)->where('post_type', 'Video')->where('active_status', 1)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // update status
        $is_exist->update(['active_status' => 0]);

        return (new ApiCommonResponseResource($is_exist, "Video Deleted Successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * @api video get by id
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 22/01/2025
     */
    public function getById(string $id)
    {
        // check param
        if (empty($id)) {
            return (new ErrorResource('Sorry, Specification Needed for this request. The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        $data = ResourceLibraryVideo::with('documents')->where('post_pid', $id)->where('post_type', 'Video')->where('active_status', 1)->first();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Video not found.', 404))->response()->setStatusCode(404);
        }

        return (new ResourceLibraryVideoResource($data, "Video fetch successfully", 200))->response()->setStatusCode(200);
    }
}
