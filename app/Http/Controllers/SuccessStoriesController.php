<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessStoryResource;
use App\Models\StoriesAttachment;
use App\Models\SuccessStories;
use App\Service\AttachmentService;
use App\Service\ImageUploadService;
use App\Service\VideoUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SuccessStoriesController extends Controller
{
    /**
     * @api success stories index
     * @author shohag <shohag@atilimited.net>
     * @since 18.02.2025
     * @return collection
     */
    public function index()
    {
        $data = SuccessStories::with('documents')
            ->where('active_status', 1)
            ->orderBy('cre_date', 'desc')
            ->paginate(12);

        if (empty($data)) {
            return (new ErrorResource('Sorry! Stories not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnStoryWithThumbnailAndVideo($data, 'Stories');
        return $result;
    }

    /**
     * Story Store Method
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 19/02/2025
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
            'title'         => 'required|unique:success_stories,title|max:100',
            'thumbnail'     => 'required',
            'video'         => 'required',
        ]);

        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {


            try {
                DB::beginTransaction();
                $insertData = new SuccessStories();
                $insertData->user_pid = $request->user_pid;
                $insertData->title = $request->title;
                // $insertData->cre_by = Auth::user()->user_pid;                     @important
                $insertData->active_status = $request->active_status ?? 1;
                $insertData->save();

                // take reference
                $story_pid = SuccessStories::where('story_id', $insertData->story_id)->pluck('story_pid')->first();

                // thumbnail upload process
                $thumbnail_directory = 'attachments/story_thumbnail/' . now()->format('Ymd') . '/';

                $storeImage = ImageUploadService::storyThumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $story_pid, "story_thumbnail");
                if ($storeImage != 200) {
                    return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                    abort(501, 'Somthing wrong with Thumbnail Upload');
                }

                // Video upload process
                $video_directory = 'attachments/story_video/' . now()->format('Ymd') . '/';
                $storeImage = VideoUploadService::storyVideoUpload($request, $request->title, $video_directory, 'video', $story_pid, "story_video");
                if ($storeImage != 200) {
                    return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                    abort(501, 'Somthing wrong with Video Upload');
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Story added successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Story update Method
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 18/02/2025
     */
    public function update(Request $request, string $story_pid)
    {
        // empty Check
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $exist_story = SuccessStories::where('story_pid', $story_pid)->where('active_status', 1)->first();
        if (!$exist_story) {
            return (new ErrorResource('Sorry, Story data not found!', 404))->response()->setStatusCode(404);
        }

        // validation
        $validation = Validator::make($request->all(), [
            // 
        ]);

        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {

            try {
                DB::beginTransaction();
                $updateData = $exist_story;
                $updateData->user_pid = $request->user_pid;
                $updateData->title = $request->title;
                // $updateData->cre_by = Auth::user()->user_pid;                     @important
                $updateData->active_status = $request->active_status ?? 1;
                $updateData->update();

                // take reference
                $story_pid = SuccessStories::where('story_id', $updateData->story_id)->pluck('story_pid')->first();

                if (request()->hasFile('thumbnail')) {
                    StoriesAttachment::where('ref_object_name', 'story_thumbnail')->where('ref_pid', $story_pid)->first()->delete();
                    // thumbnail upload process
                    $thumbnail_directory = 'attachments/story_thumbnail/' . now()->format('Ymd') . '/';
                    $storeImage = ImageUploadService::storyThumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $story_pid, "story_thumbnail");
                    if ($storeImage != 200) {
                        return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                        abort(501, 'Somthing wrong with Thumbnail Upload');
                    }
                }

                if (request()->hasFile('video')) {
                    StoriesAttachment::where('ref_object_name', 'story_video')->where('ref_pid', $story_pid)->delete();
                    // Video upload process
                    $video_directory = 'attachments/story_video/' . now()->format('Ymd') . '/';
                    $storeImage = VideoUploadService::storyVideoUpload($request, $request->title, $video_directory, 'video', $story_pid, "story_video");
                    if ($storeImage != 200) {
                        return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                        abort(501, 'Somthing wrong with Video Upload');
                    }
                }

                DB::commit();
                return (new ApiCommonResponseResource($updateData, "Story updated successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * @api get success stories by story pid
     * @author shohag <shohag@atilimited.net>
     * @since 19.02.2025
     * @return collection
     */
    public function show(string $story_pid): object
    {
        $data = $data = SuccessStories::with('documents')
            ->where('story_pid', $story_pid)
            ->where('active_status', 1)
            ->first();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Stories not found.', 400))->response()->setStatusCode(400);
        };

        return (new SuccessStoryResource($data, 'Story fatch successfully!', 200))->response()->setStatusCode(200);
    }

    /**
     * @api get success stories for homepage
     * @author shohag <shohag@atilimited.net>
     * @since 19.02.2025
     * @return collection
     */
    public function homePage()
    {
        $data = SuccessStories::with('documents')
            ->where('active_status', 1)
            ->orderBy('cre_date', 'desc')
            ->take(6)
            ->get();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Stories not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnStoryWithThumbnailAndVideo($data, 'Stories');
        return $result;
    }

    /**
     * @api Story data Delete by pid
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 19/02/2025
     */
    public function destroy(string $story_pid)
    {
        // check param
        if (empty($story_pid)) {
            return (new ErrorResource('Sorry, Specification Needed for this request. The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = SuccessStories::where('story_pid', $story_pid)->where('active_status', 1)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // update status
        $is_exist->update(['active_status' => 0]);

        return (new ApiCommonResponseResource($is_exist, "Story Deleted Successfully", 200))->response()->setStatusCode(200);
    }
}
