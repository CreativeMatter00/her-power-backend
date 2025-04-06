<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\DeleteResource;
use App\Models\Attachment;
use App\Models\Category;
use App\Service\ImageUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;



class CategoryController extends Controller
{


    public function index()
    {
        $categoryList = Category::orderBy('ud_serialno', 'asc')->get();
        if ($categoryList) {
            return new CategoryCollection($categoryList);
        } else {
            return (new ErrorResource("Data not Found", 404))->response()->setStatusCode(404);
        }
    }

    public function store(Request $request, ImageUploadService $imageUploadService)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required',
            'short_name' => 'required',
            'ud_serialno' => 'required',
            'category_desc' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                DB::beginTransaction();
                $insertCategory = new Category();
                $insertCategory->category_name = $request->category_name;
                $insertCategory->short_name = $request->short_name;
                $insertCategory->category_desc = $request->category_desc;
                $insertCategory->parent_category_pid = $request->parent_category_pid;
                $insertCategory->ud_serialno = $request->ud_serialno;
                $insertCategory->remarks = $request->remarks;
                // $insertCategory->cre_by = Auth::user()->user_pid;
                $insertCategory->save();
                $category_pid = Category::where('category_id', $insertCategory->category_id)->pluck('category_pid')->first();
                $directory = 'attachments/categories/' . now()->format('Ymd') . '/';
                $createDirectory = public_path('attachments/categories/' . now()->format('Ymd') . '/');
                if (!File::exists($createDirectory)) {
                    File::makeDirectory($createDirectory, 0777, true, true);
                }
                
                $storeCatImage = $imageUploadService->uploadSingleImage($request, $request->category_name, $directory, $category_pid, "categories");

                if ($storeCatImage != 200) {
                    return (new ErrorResource($storeCatImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with category Image Upload');
                }

                DB::commit();

                $insertCategory->category_pid = $category_pid;
                return (new CategoryResource($insertCategory, 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Category adding failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }



    public function show(string $id)
    {
        $editCategory = Category::where('category_pid', $id)->first();
        if (!$editCategory) {
            return (new ErrorResource("Category not found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new CategoryResource($editCategory, 200))->response()->setStatusCode(200);
        }
    }


    public function update(Request $request, string $id,ImageUploadService $imageUploadService)
    {

        $validator = Validator::make($request->all(), [
            'category_name' => 'required',
            'short_name' => 'required',
            'category_desc' => 'required',
            'active_status' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                DB::beginTransaction();
                $updateCategory = Category::where('category_pid', $id)->first();

                $updateCategory->category_name = $request->category_name;
                $updateCategory->short_name = $request->short_name;
                $updateCategory->category_desc = $request->category_desc;
                $updateCategory->parent_category_pid = $request->parent_category_pid;
                $updateCategory->remarks = $request->remarks;
                $updateCategory->active_status = $request->active_status;
                // $updateCategory->upd_by = Auth::user()->user_pid;
                $updateCategory->upd_date = date('Y-m-d H:i:s');
                $updateCategory->update();
                if ($request->hasFile('attachments')) {
                   Attachment::where('ref_pid',$id)->delete();
                    $directory = 'attachments/categories/' . now()->format('Ymd') . '/';
                    $createDirectory = public_path('attachments/categories/' . now()->format('Ymd') . '/');
                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                    }
                    $storeCatImage = $imageUploadService->uploadSingleImage($request, $request->category_name, $directory, $id, "categories");
                    if ($storeCatImage != 200) {
                        return (new ErrorResource($storeCatImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with category Image Upload');
                    }
    
                }

                DB::commit();
                return (new CategoryResource($updateCategory, 200))->response()->setStatusCode(200);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Category update failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }



    public function destroy(string $id)
    {
        try {
            $deleteCategory = Category::where('category_pid', $id)->first();
            if (!empty($deleteCategory)) {
                $deleteCategory->delete();
                return (new DeleteResource("Category deleted Successfully !", 200))->response()->setStatusCode(200);
            } else {
                return (new DeleteResource("Category data not found !", 404))->response()->setStatusCode(404);
            }
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Category delete failed, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
