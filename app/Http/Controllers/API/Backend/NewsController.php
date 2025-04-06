<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\DeleteResource;
use App\Http\Resources\NewsCollection;
use App\Models\News;
use App\Http\Resources\NewsResource;
use App\Models\Attachment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Admin>News",
 *     description="Operations related to News"
 * )
 */

class NewsController extends Controller
{

    public function index()
    {
        $newsList = News::with('attachments')
            ->orderBy('ud_serialno', 'asc')
            ->paginate(15);
        if ($newsList) {
            return new NewsCollection($newsList);
        } else {
            return (new ErrorResource("Data not Found", 404))->response()->setStatusCode(404);
        }
    }


    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'news_title' => 'required|unique:ec_news,news_title',
            'news_content' => 'required',
            'effectivefrom' => 'required',
            'effectiveto' => 'required',
            'news_author' => 'required',
            'ud_serialno' => 'required',
            'attachments.*' => 'file|max:2048', // Adjust max file size as needed
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                $directory = 'attachments/news/' . now()->format('Ymd') . '/';
                $createDirectory = public_path('attachments/news/' . now()->format('Ymd') . '/');
                DB::beginTransaction();
                $insertNews = new News();
                $insertNews->news_title = $request->news_title;
                $insertNews->news_content = $request->news_content;
                $insertNews->effectivefrom = Carbon::createFromFormat('d/m/Y', $request->effectivefrom)->format('Y-m-d');
                $insertNews->effectiveto =  Carbon::createFromFormat('d/m/Y', $request->effectiveto)->format('Y-m-d');
                $insertNews->news_author = $request->news_author;
                $insertNews->ud_serialno = $request->ud_serialno;
                $insertNews->attached_url = $directory;
                $insertNews->remarks = $request->remarks;
                // $insertNews->cre_by = Auth::user()->user_pid;
                $insertNews->save();
                $news_pid = News::where('news_id', $insertNews->news_id)->pluck('news_pid')->first();
                $insertNews->news_pid = $news_pid;
                // Handle file uploads
                if ($request->hasFile('attachments')) {

                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                    }

                    $fileSlug = Str::slug($request->news_title);
                    foreach ($request->file('attachments') as $file) {
                        $extension = $file->getClientOriginalExtension();
                        $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                        $file->move(public_path($directory), $fileName);
                        $filePath = $directory . $fileName;
                        $attachment = new Attachment();
                        $attachment->ref_object_name = "news";
                        $attachment->ref_pid = $insertNews->news_pid;
                        $attachment->file_extantion  = $extension;
                        $attachment->file_url = $filePath;
                        // $attachment->cre_by = Auth::user()->user_pid;
                        $attachment->save();
                    }
                }
                DB::commit();

                return (new NewsResource($insertNews, 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }



    public function show(string $id)
    {
        $editNews = News::with('attachments')->where('news_pid', $id)->first();
        if (!$editNews) {
            return (new ErrorResource("News not found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new NewsResource($editNews, 200))->response()->setStatusCode(200);
        }
    }


    public function update(Request $request, string $id)
    {
        $directory = 'attachments/news/' . now()->format('Ymd') . '/';
        $createDirectory = public_path('attachments/news/' . now()->format('Ymd') . '/');


        try {
            DB::beginTransaction();
            $updateNews = News::where('news_pid', $id)->first();
            $updateNews->news_title = $request->news_title;
            $updateNews->news_content = $request->news_content;
            $updateNews->effectivefrom = Carbon::createFromFormat('d/m/Y', $request->effectivefrom)->format('Y-m-d');
            $updateNews->effectiveto =  Carbon::createFromFormat('d/m/Y', $request->effectiveto)->format('Y-m-d');
            $updateNews->news_author = $request->news_author;
            $updateNews->ud_serialno = $request->ud_serialno;
            $updateNews->remarks = $request->remarks;
            // $updateNews->upd_by = Auth::user()->user_pid;
            $updateNews->upd_date = date('Y-m-d H:i:s');
            $updateNews->update();

            if ($request->hasFile('attachments')) {
                Attachment::where('ref_pid', $id)->delete();
                if (!File::exists($createDirectory)) {
                    File::makeDirectory($createDirectory, 0777, true, true);
                }

                $fileSlug = Str::slug($request->news_title);
                foreach ($request->file('attachments') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move(public_path($directory), $fileName);
                    $filePath = $directory . $fileName;
                    $attachment = new Attachment();
                    $attachment->ref_object_name = "news";
                    $attachment->ref_pid = $id;
                    $attachment->file_extantion  = $extension;
                    $attachment->file_url = $filePath;
                    // $attachment->cre_by = Auth::user()->user_pid;
                    $attachment->save();
                }
            }
            DB::commit();
            return (new NewsResource($updateNews, 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }

        // $validator = Validator::make($request->all(), [
        //     'news_title' => 'required|unique:ec_news,news_title',
        //     'news_content' => 'required',
        //     'effectivefrom' => 'required',
        //     'effectiveto' => 'required',
        // ]);
        // if ($validator->fails()) {
        //     return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        // } else {
        //     try {
        //         DB::beginTransaction();
        //         $updateNews = News::where('news_pid', $id)->first();
        //         $updateNews->news_title = $request->news_title;
        //         $updateNews->news_content = $request->news_content;
        //         $updateNews->effectivefrom = Carbon::createFromFormat('d/m/Y', $request->effectivefrom)->format('Y-m-d');
        //         $updateNews->effectiveto =  Carbon::createFromFormat('d/m/Y', $request->effectiveto)->format('Y-m-d');
        //         $updateNews->news_author = $request->news_author;
        //         $updateNews->ud_serialno = $request->ud_serialno;
        //         $updateNews->remarks = $request->remarks;
        //         $updateNews->upd_by = Auth::user()->user_pid;
        //         $updateNews->upd_date = date('Y-m-d H:i:s');
        //         $updateNews->update();

        //         if ($request->hasFile('attachments')) {
        //             Attachment::where('ref_pid',$id)->delete();
        //             if (!File::exists($createDirectory)) {
        //                 File::makeDirectory($createDirectory, 0777, true, true);
        //             }

        //             $fileSlug = Str::slug($request->news_title);
        //             foreach ($request->file('attachments') as $file) {
        //                 $extension = $file->getClientOriginalExtension();
        //                 $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
        //                 $file->move(public_path($directory), $fileName);
        //                 $filePath = $directory . $fileName;
        //                 $attachment = new Attachment();
        //                 $attachment->ref_object_name = "news";
        //                 $attachment->ref_pid = $id;
        //                 $attachment->file_extantion  = $extension;
        //                 $attachment->file_url = $filePath;
        //                 $attachment->cre_by = Auth::user()->user_pid;
        //                 $attachment->save();
        //             }
        //         }
        //         DB::commit();
        //         return (new NewsResource($updateNews, 200))->response()->setStatusCode(200);
        //     } catch (Exception $e) {
        //         DB::rollBack();
        //         return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        //     }
        // }
    }


    public function destroy(string $id)
    {
        try {
            $deleteNews = News::where('news_pid', $id)->first();
            if (!empty($deleteNews)) {
                $deleteNews->delete();
                return (new DeleteResource("News deleted Successfully !", 200))->response()->setStatusCode(200);
            } else {
                return (new ErrorResource("Data not found!!", 404))->response()->setStatusCode(404);
            }
        } catch (Exception $e) {
        }
    }

    public function moveToArchive() {}
}
