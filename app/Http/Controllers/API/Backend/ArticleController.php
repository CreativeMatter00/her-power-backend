<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ErrorResource;
use App\Models\Article;
use App\Models\Attachment;
use App\Models\Document;
use App\Service\AttachmentService;
use App\Service\ImageUploadService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    /**
     * @api Article get homepage data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 11/01/2025
     */
    public function homepage()
    {
        $data = Article::with('documents')->where('post_type', 'Article')->where('active_status', 1)->orderBy('cre_date', 'desc')->take(6)->get();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Article not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnWithBannerAndThumbnail($data, 'Article');
        return $result;
    }

    /**
     * @api Article get all data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 11/01/2025
     */
    public function allArticle()
    {
        $data = Article::with('documents')->where('post_type', 'Article')->where('active_status', 1)->orderBy('cre_date', 'desc')->paginate(12);

        if (empty($data)) {
            return (new ErrorResource('Sorry! Article not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnWithBannerAndThumbnail($data, 'Article');
        return $result;
    }

    /**
     * @api Article get by id
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 11/01/2025
     */
    public function getById(string $id)
    {
        $data = Article::with('documents')->where('post_pid', $id)->where('active_status', 1)->orderBy('cre_date', 'desc')->first();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Article not found.', 400))->response()->setStatusCode(400);
        }

        return (new ArticleResource($data, "Article fetch successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * @api Article data store
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 11/01/2025
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
                $insertData = new Article();
                $insertData->user_pid = $request->user_pid;
                $insertData->title = $request->title;
                $insertData->description = $request->description;
                // $insertData->post_content = $request->post_content;
                $insertData->post_type = 'Article';
                // $insertData->post_tag = $request->post_tag;
                // $insertData->file_path = $request->file_path;
                // $insertData->publicationdate = $request->publicationdate;
                // $insertData->resourse_marks = $request->resourse_marks;
                // $insertData->ud_serialno = $request->ud_serialno;
                // $insertData->remarks = $request->remarks;
                // $insertData->cre_by = Auth::user()->user_pid;                     @important
                $insertData->active_status = $request->active_status;
                // $insertData->unit_no = $request->unit_no;
                $insertData->save();

                // take reference
                $post_pid = Article::where('post_id', $insertData->post_id)->pluck('post_pid')->first();

                // banner images upload process
                $banner_directory = 'attachments/article_banner/' . now()->format('Ymd') . '/';
                $storeBanImage = ImageUploadService::bannerImage($request, $request->title, $banner_directory, 'banner', $post_pid, "article_banner");
                if ($storeBanImage != 200) {
                    return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Article Image Upload');
                }

                // thumnail images upload process
                $thumbnail_directory = 'attachments/article_thumbnail/' . now()->format('Ymd') . '/';
                $storeThumImage = ImageUploadService::thumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $post_pid, "article_thumbnail");
                if ($storeThumImage != 200) {
                    return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Event Image Upload');
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Article added successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Article adding failed. Please try again.', 501))->response()->setStatusCode(501);
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
     * @api BlogPost data store
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public function update(Request $request, string $id)
    {
        // empty check
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = Article::where('active_status', 1)->where('post_pid', $id)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // validation
        $validation = Validator::make($request->all(), [
            // 'title'         => 'required',
            // 'user_pid'      => 'required',
            // 'category_pid'  => 'required',
            // 'description'   => 'required'
        ]);

        // validation check
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // store process
                $updateData = Article::where('post_pid', $id)->first();
                $request->user_pid ? $updateData->user_pid = $request->user_pid : null;
                $request->title ? $updateData->title = $request->title : null;
                $request->description ? $updateData->description = $request->description : null;
                // $updateData->post_content = $request->post_content;
                // $updateData->post_tag = $request->post_tag;
                // $updateData->file_path = $request->file_path;
                // $updateData->publicationdate = $request->publicationdate;
                // $updateData->resourse_marks = $request->resourse_marks;
                // $updateData->ud_serialno = $request->ud_serialno;
                // $updateData->remarks = $request->remarks;
                $updateData->upd_date = Carbon::now();
                // $updateData->upd_by = Auth::user()->user_pid;                     @important
                $updateData->active_status = $request->active_status;
                // $updateData->unit_no = $request->unit_no;
                $updateData->update();

                // banner images upload process
                if (!empty($request->banner)) {
                    Document::where('ref_pid', $id)->where('ref_object_name', 'article_banner')->delete();
                    $banner_directory = 'attachments/article_banner/' . now()->format('Ymd') . '/';
                    $storeBanImage = ImageUploadService::bannerImage($request, $request->title, $banner_directory, 'banner', $id, "article_banner");
                    if ($storeBanImage != 200) {
                        return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Article Image Upload');
                    }
                }

                // thumnail images upload process
                if (!empty($request->thumbnail)) {
                    Document::where('ref_pid', $id)->where('ref_object_name', 'article_thumbnail')->delete();
                    $thumbnail_directory = 'attachments/article_thumbnail/' . now()->format('Ymd') . '/';
                    $storeThumImage = ImageUploadService::thumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $id, "article_thumbnail");
                    if ($storeThumImage != 200) {
                        return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Event Image Upload');
                    }
                }

                DB::commit();
                return (new ApiCommonResponseResource($updateData, "Article updated successfully", 202))->response()->setStatusCode(202);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Article adding failed. Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * @api Article data Delete by pid
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
        $is_exist = Article::where('user_pid', $user_pid)->where('post_pid', $post_pid)->where('post_type', 'Article')->where('active_status', 1)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // update status
        $is_exist->update(['active_status' => 0]);

        return (new ApiCommonResponseResource($is_exist, "Article Deleted Successfully", 200))->response()->setStatusCode(200);
    }
}
