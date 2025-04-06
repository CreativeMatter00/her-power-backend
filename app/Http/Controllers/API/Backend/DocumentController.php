<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\DocumentCollection;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\ErrorResource;
use App\Models\Article;
use App\Models\Document;
use App\Service\AttachmentService;
use App\Service\PdfUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    /**
     * @api Document data store
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 12/01/2025
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
            'document'      => 'required',
        ]);


        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                $insertData = new Article();
                $insertData->user_pid = $request->user_pid;
                $insertData->title = $request->title;
                $insertData->post_type = 'Document';
                // $insertData->cre_by = Auth::user()->user_pid;                     @important
                $insertData->active_status = $request->active_status;
                $insertData->save();

                // take reference
                $post_pid = Article::where('post_id', $insertData->post_id)->pluck('post_pid')->first();

                // Document upload process
                $banner_directory = 'attachments/article_document/' . now()->format('Ymd') . '/';

                $storeBanImage = PdfUploadService::pdfUpload($request, $request->title, $banner_directory, 'document', $post_pid, "article_document");
                if ($storeBanImage != 200) {
                    return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Document Image Upload');
                }

                DB::commit();
                return (new ApiCommonResponseResource($insertData, "Document added successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Document adding failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * @api Document data store
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 12/01/2025
     */
    public function update(Request $request, string $id)
    {
        // empty Check
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
            // 'user_pid'      => 'required',
            // 'title'         => 'required',
            // 'document'      => 'required',
        ]);


        // validation handle
        if ($validation->fails()) {
            return (new ErrorResource($validation->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                $updateData = Article::where('post_pid', $id)->first();
                $request->user_pid ? $updateData->user_pid = $request->user_pid : null;
                $request->title ? $updateData->title = $request->title : null;
                // $updateData->cre_by = Auth::user()->user_pid;                     @important
                $updateData->active_status = $request->active_status;
                $updateData->save();

                // take reference
                $post_pid = Article::where('post_pid', $id)->pluck('post_pid')->first();

                // Document upload process
                if ($request->hasFile('document')) {
                    Document::where('ref_pid', $id)->delete();
                    $banner_directory = 'attachments/article_document/' . now()->format('Ymd') . '/';
                    $storeBanImage = PdfUploadService::pdfUpload($request, $request->title, $banner_directory, 'document', $post_pid, "article_document");
                    if ($storeBanImage != 200) {
                        return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Document Image Upload');
                    }
                }

                DB::commit();
                return (new ApiCommonResponseResource($updateData, "Document updated successfully", 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Document updating failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * @api get all documents data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 12/01/2025
     */
    public function allDocuments()
    {
        $data = Article::with('documents')->where('active_status', 1)->where('post_type', 'Document')->orderBy('cre_date', 'desc')->paginate(12);

        if (empty($data)) {
            return (new ErrorResource('Sorry! Documents not found.', 400))->response()->setStatusCode(400);
        }

        // return (new ApiCommonResponseResource($data, "Document fatch successfully", 200))->response()->setStatusCode(200);
        return (new DocumentCollection($data, "Document fatch successfully", 200))->response()->setStatusCode(200);

    }

    /**
     * @api get all documents data
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 12/01/2025
     */
    public function documentsHomepage()
    {
        $data = Article::with('documents')->where('active_status', 1)->where('post_type', 'Document')->orderBy('cre_date', 'desc')->take(6)->get();

        if (empty($data)) {
            return (new ErrorResource('Sorry! Documents not found.', 400))->response()->setStatusCode(400);
        }

        return (new DocumentResource($data, "Document fatch successfully", 200))->response()->setStatusCode(200);
    }

    /**
     * @api Document data Delete by pid
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 20/01/2025
     */
    public function destroy(string $post_pid = null)
    {
        // check param
        if (empty($post_pid)) {
            return (new ErrorResource('Sorry, Specification Needed for this request. The Requested data was not found!', 400))->response()->setStatusCode(400);
        }

        // exist or not
        $is_exist = Article::where('post_pid', $post_pid)->where('post_type', 'Document')->where('active_status', 1)->first();
        if (empty($is_exist)) {
            return (new ErrorResource('Sorry, The Requested data was not found!', 404))->response()->setStatusCode(404);
        }

        // update status
        $is_exist->update(['active_status' => 0]);

        return (new ApiCommonResponseResource($is_exist, "Document Deleted Successfully", 200))->response()->setStatusCode(200);
    }
}
