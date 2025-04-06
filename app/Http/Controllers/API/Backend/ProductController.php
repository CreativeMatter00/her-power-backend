<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Attachment;
use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Service\ImageUploadService;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $enterpenure_id = $request->query('entrepId');


        $products = Product::with('attachments', 'productvariants')
        ->where('enterpenure_pid', $enterpenure_id)
        ->orderBy('ud_serialno', 'asc')
        ->paginate(15);

     
    if (!$products) {
        return (new ErrorResource("No Product Found !!", 404))->response()->setStatusCode(404);
    } else {
        return (new ProductCollection($products, "Product fetch successfully", 200))->response()->setStatusCode(200);
    }
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
            'product_name' => 'required',
            'category_pid' => 'required',
            'description' => 'required',
            'ud_serialno' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle product information.
                $insertProduct = new Product();
                $insertProduct->product_name = $request->product_name;
                $insertProduct->category_pid = $request->category_pid;
                $insertProduct->enterpenure_pid = $request->enterpenure_pid;
                $insertProduct->uom_no = $request->uom_no;
                $insertProduct->brand_name = $request->brand_name;
                $insertProduct->model_name = $request->model_name;
                $insertProduct->ud_serialno = $request->ud_serialno;
                $insertProduct->description = $request->description;
                // $insertProduct->cre_by = Auth::user()->user_pid;
                // $insertProduct->origin = Auth::user()->origin;
                $insertProduct->save();
                $product_pid = Product::where('product_id', $insertProduct->product_id)->pluck('product_pid')->first();
                $insertProduct->product_pid = $product_pid;

                // handle product variant  information.

                $productName =  $request->varient_name;
                $stock_available =  $request->stock_available;
                $varient_desc =  $request->varient_desc;
                $mrp_primary =  $request->mrp_primary;
                $disc_pct =  $request->disc_pct;
                $productMrp =  $request->mrp;

                for ($i = 0; $i < count($productName); $i++) {

                    $productVariant = new ProductVariant();
                    $productVariant->product_pid = $product_pid;
                    $productVariant->varient_name = $productName[$i];
                    $productVariant->varient_desc =  $varient_desc[$i];
                    $productVariant->mrp_primary =  $mrp_primary[$i];
                    $productVariant->disc_pct =  $disc_pct[$i];
                    $productVariant->mrp =  $productMrp[$i];
                    $productVariant->stock_available =  $stock_available[$i];
                    // $productVariant->cre_by =  Auth::user()->user_pid;
                    $productVariant->save();
                }
                // handle multiple file upload

                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $key => $file) {
                        $storeImage  = $imageUploadService->storeMultipleImage($product_pid, $key, $file);
                        if ($storeImage != 200) {
                            return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                            break;
                        }
                    }
                }

                DB::commit();
                return (new ProductResource($insertProduct, "Product created successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }


    public function updateProductData(Request $request,ImageUploadService $imageUploadService, $id )
    {
        
        try {
            $updateData = [];

            if ($request->has('product_name')) {
                $updateData['product_name'] = $request->product_name;
            }

            if ($request->has('category_pid')) {
                $updateData['category_pid'] = $request->category_pid;
            }

            if ($request->has('uom_no')) {
                $updateData['uom_no'] = $request->uom_no;
            }

            if ($request->has('brand_name')) {
                $updateData['brand_name'] = $request->brand_name;
            }

            if ($request->has('model_name')) {
                $updateData['model_name'] = $request->model_name;
            }

            if ($request->has('ud_serialno')) {
                $updateData['ud_serialno'] = $request->ud_serialno;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            if ($request->has('active_status')) {
                $updateData['active_status'] = $request->active_status;
            }

            if ($request->has('is_sale')) {
                $updateData['is_sale'] = $request->is_sale;
            }

            if ($request->has('re_stock_level')) {
                $updateData['re_stock_level'] = $request->re_stock_level;
            }
            if ($request->has('stockout_life')) {
                $updateData['stockout_life'] = $request->re_stock_level;
            }
            DB::beginTransaction();
            Product::where('product_pid', $id)->update($updateData);
            if ($request->hasFile('attachments')) {
                 Attachment::where('ref_pid',$id)->where('ref_object_name','product')->delete();
                foreach ($request->file('attachments') as $key => $file) {
                    $storeImage  = $imageUploadService->storeMultipleImage($id, $key, $file);
                    if ($storeImage != 200) {
                        return (new ErrorResource($storeImage, 501))->response()->setStatusCode(501);
                        break;
                    }
                }
            }

            DB::commit();
            return response()->json([
                'data' => '',
                'msg' => 'Product updated successfully',
                'code' => 200,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
