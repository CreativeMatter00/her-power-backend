<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\BlogPostResource;
use App\Http\Resources\DocumentCollection;
use App\Http\Resources\ErrorResource;
use App\Models\Article;
use App\Models\BlogPost;
use App\Models\Comments;
use App\Models\Document;
use App\Models\ResourceLibraryVideo;
use App\Service\AttachmentService;
use App\Service\ImageUploadService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BlogPostController extends Controller
{

    /**
     * @api BlogPost data store
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public function store(Request $request)
    {
        // empty Check
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        // validation
        $validation = Validator::make($request->all(), [
            // 'category_pid'  => 'required',
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
                $insertData = new BlogPost();
                $insertData->category_pid = $request->category_pid;
                $insertData->user_pid = $request->user_pid;
                $insertData->title = $request->title;
                $insertData->description = $request->description;
                // $insertData->post_content = $request->post_content;
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
                $bpost_pid = BlogPost::where('bpost_id', $insertData->bpost_id)->pluck('bpost_pid')->first();

                // banner images upload process
                $banner_directory = 'attachments/blog_banner/' . now()->format('Ymd') . '/';
                $storeBanImage = ImageUploadService::bannerImage($request, $request->title, $banner_directory, 'banner', $bpost_pid, "blog_banner");
                if ($storeBanImage != 200) {
                    return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Blog Image Upload');
                }

                // thumnail images upload process
                $thumbnail_directory = 'attachments/blog_thumbnail/' . now()->format('Ymd') . '/';
                $storeThumImage = ImageUploadService::thumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $bpost_pid, "blog_thumbnail");
                if ($storeThumImage != 200) {
                    return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Event Image Upload');
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "BlogPost added successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Blog Post adding failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
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
        $is_exist = BlogPost::where('active_status', 1)->where('bpost_pid', $id)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // validation
        $validation = Validator::make($request->all(), [
            'title'         => 'required',
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
                $updateData = BlogPost::where('bpost_pid', $id)->first();
                $request->category_pid ? $updateData->category_pid = $request->category_pid : null;
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
                $updateData->active_status = $request->active_status ?? 1;
                // $updateData->unit_no = $request->unit_no;
                $updateData->update();

                // banner images upload process
                if (!empty($request->banner)) {
                    Document::where('ref_pid', $id)->where('ref_object_name', 'blog_banner')->delete();
                    $banner_directory = 'attachments/blog_banner/' . now()->format('Ymd') . '/';
                    $storeBanImage = ImageUploadService::bannerImage($request, $request->title, $banner_directory, 'banner', $id, "blog_banner");
                    if ($storeBanImage != 200) {
                        return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Blog Image Upload');
                    }
                }

                // thumnail images upload process
                if (!empty($request->thumbnail)) {
                    Document::where('ref_pid', $id)->where('ref_object_name', 'blog_thumbnail')->delete();
                    $thumbnail_directory = 'attachments/blog_thumbnail/' . now()->format('Ymd') . '/';
                    $storeThumImage = ImageUploadService::thumbnailImage($request, $request->title, $thumbnail_directory, 'thumbnail', $id, "blog_thumbnail");
                    if ($storeThumImage != 200) {
                        return (new ErrorResource($storeThumImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Event Image Upload');
                    }
                }

                DB::commit();
                return (new ApiCommonResponseResource($updateData, "BlogPost updated successfully", 202))->response()->setStatusCode(202);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Blog Post updating failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * @api BlogPost get homepage data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public function homepage()
    {
        $data = BlogPost::with('documents')->where('active_status', 1)->orderBy('cre_date', 'desc')->take(9)->get();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Blog Post not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnWithBannerAndThumbnail($data, 'Blog Post');
        return $result;
    }

    /**
     * @api BlogPost get all blogPost data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public function allBlogs()
    {
        $data = BlogPost::with('documents')->where('active_status', 1)->orderBy('cre_date', 'desc')->paginate(12);

        if (empty($data)) {
            return (new ErrorResource('Sorry! Blog Post not found.', 400))->response()->setStatusCode(400);
        }

        $result = AttachmentService::returnWithBannerAndThumbnail($data, 'Blog Post');
        return $result;
    }

    /**
     * @api BlogPost get all blogPost data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public function getById(string $id, $need = null)
    {
        if (empty($id)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        if ($need == null) {
            $data = BlogPost::with(['documents', 'comments' => function ($query) {
                $query->where('parent_comment_pid', null)
                    ->where('active_status', 1);
            }])->where('bpost_pid', $id)->where('active_status', 1)->first();
        } else {
            $data = BlogPost::with(['documents', 'comments' => function ($query) use ($need) {
                $query->where('parent_comment_pid', null)
                    ->where('active_status', 1)->take($need)->get();
            }])->where('bpost_pid', $id)->where('active_status', 1)->first();
        }


        if (empty($data)) {
            return (new ErrorResource('Sorry! Blog Post not found.', 400))->response()->setStatusCode(400);
        }

        // for replying comments
        foreach ($data['comments'] as $item) {
            $item['total_reply'] = Comments::where('parent_comment_pid', $item->comment_pid)->where('active_status', 1)->count();
            $item['reply'] = Comments::where('parent_comment_pid', $item->comment_pid)->where('active_status', 1)->orderBy('cre_by', 'asc')->first();
        }

        return (new BlogPostResource($data, "BlogPost fetch successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * @api BlogComment data store process
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public function blogCommentStore(Request $request, string $id)
    {
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        $validation = Validator::make($request->all(), [
            // 'resourse_pid'  => 'required',
            'user_pid'      => 'required',
            'comm_text'     => 'required'
        ]);

        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                $insertData = new Comments();
                $insertData->bpost_pid              = $id;
                $insertData->resourse_pid           = $request->resourse_pid;
                $insertData->user_pid               = $request->user_pid;
                $insertData->comm_text              = $request->comm_text;
                $insertData->parent_comment_pid     = $request->parent_comment_pid ?? null;
                $insertData->comm_date              = Carbon::now();
                $insertData->active_status          = $request->active_status;
                // $insertData->cre_by              = Auth::user()->user_pid;        @important
                $insertData->save();

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Comment added successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Comment failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * @api BlogComment data get by commant pid
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 19/01/2025
     */
    public function getblogComment(string $id, $need = null)
    {
        if (empty($id)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        $data['total_comments'] = Comments::where('parent_comment_pid', $id)->where('active_status', 1)->count();

        // fatch needed data
        if ($need == null) {
            $data['data'] = Comments::where('parent_comment_pid', $id)
                ->where('active_status', 1)
                ->orderBy('cre_date', 'asc')
                ->get();
        } else {
            $data['data'] = Comments::where('parent_comment_pid', $id)
                ->where('active_status', 1)
                ->orderBy('cre_date', 'asc')
                ->take($need)
                ->get();
        }

        // empty check
        if (empty($data)) {
            return (new ErrorResource('Sorry, Comment not found!.', 400))->response()->setStatusCode(400);
        }

        // for replying comments
        foreach ($data['data'] as $item) {
            $item['total_reply'] = Comments::where('parent_comment_pid', $item->comment_pid)->where('active_status', 1)->count();
            $item['reply'] = Comments::where('parent_comment_pid', $item->comment_pid)->where('active_status', 1)->orderBy('cre_by', 'asc')->first();
        }

        return (new ApiCommonResponseResource($data, "Comment fatch successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * @api BlogPost data Delete by pid
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 20/01/2025
     */
    public function destroy(string $bpost_pid)
    {
        // check param
        if (empty($bpost_pid)) {
            return (new ErrorResource('Sorry, Specification Needed for this request. The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = BlogPost::where('active_status', 1)->where('bpost_pid', $bpost_pid)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // update status
        $is_exist->update(['active_status' => 0]);

        return (new ApiCommonResponseResource($is_exist, "Blog Post Deleted Successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * @api BlogComment data update process
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 20/01/2025
     */
    public function blogCommentUpdate(Request $request, string $id)
    {
        // return $request;
        if (empty($request->all())) {
            return (new ErrorResource('Please, Enter your form data.', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = Comments::where('comment_pid', $id)->where('active_status', 1)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        $validation = Validator::make($request->all(), [
            // 'resourse_pid'  => 'required',
            'user_pid'      => 'required',
            'comm_text'     => 'required'
        ]);

        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                $updateData = Comments::where('comment_pid', $id)->first();
                // $updateData->bpost_pid              = $id;
                // $updateData->resourse_pid           = $request->resourse_pid;
                $request->user_pid ? $updateData->user_pid = $request->user_pid : null;
                $request->comm_text ? $updateData->comm_text = $request->comm_text : null;
                $request->parent_comment_pid ? $updateData->parent_comment_pid = $request->parent_comment_pid : null;
                $updateData->active_status              = $request->active_status ?? 1;
                // $updateData->upd_by                  = Auth::user()->user_pid;        @important
                $updateData->upd_date                   = Carbon::now();
                // return $updateData;
                $updateData->update();

                DB::commit();
                return (new ApiCommonResponseResource($updateData, "Comment updated successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Comment update failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * @api BlogComment data Delete by pid
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 26/01/2025
     */
    public function blogCommentDelete(string $comment_pid)
    {
        // check param
        if (empty($comment_pid)) {
            return (new ErrorResource('Sorry, Specification Needed for this request. The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = Comments::where('comment_pid', $comment_pid)->where('active_status', 1)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // update status
        $is_exist->update(['active_status' => 0]);

        return (new ApiCommonResponseResource($is_exist, "Comment Deleted Successfully", 200))->response()->setStatusCode(200);
    }

    public function get_vbad_by_user(string $user_pid, int $need = 10)
    {
        $blog = BlogPost::with('documents')->where('user_pid', $user_pid)->where('active_status', 1)->orderBy('cre_date', 'desc')->paginate($need);
        $video = ResourceLibraryVideo::with('documents')->where('user_pid', $user_pid)->where('post_type', 'Video')->where('active_status', 1)->orderBy('cre_date', 'desc')->paginate($need);
        $article = Article::with('documents')->where('user_pid', $user_pid)->where('post_type', 'Article')->where('active_status', 1)->orderBy('cre_date', 'desc')->paginate($need);
        $documents = Article::with('documents')->where('user_pid', $user_pid)->where('active_status', 1)->where('post_type', 'Document')->orderBy('cre_date', 'desc')->paginate($need);

        $blog_result = AttachmentService::returnWithBannerAndThumbnail($blog, 'Blog Post');
        $video_result = AttachmentService::returnWithThumbnailAndVideo($video, 'Video');
        $article_result = AttachmentService::returnWithBannerAndThumbnail($article, 'Article');
        $documents_result = new DocumentCollection($documents, "Document fatch successfully", 200);

        return array(
            'blogs' => $blog_result->original,
            'videos' => $video_result->original,
            'articles' => $article_result->original,
            'documents' => $documents_result,
        );
    }
}
