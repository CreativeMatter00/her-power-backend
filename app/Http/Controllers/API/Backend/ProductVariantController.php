<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\DeleteResource;
use App\Http\Resources\ErrorResource;
use App\Models\ProductVariant;
use App\Service\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request, $pid)
    {

        try {

            DB::beginTransaction();
            $productVariant = new ProductVariant();
            $productVariant->product_pid = $pid;
            $productVariant->varient_name =  $request->varient_name;
            $productVariant->varient_value =   $request->varient_value;
            $productVariant->varient_desc =   $request->varient_desc;
            $productVariant->mrp_primary = $request->mrp_primary;
            $productVariant->disc_pct = $request->disc_pct;
            $productVariant->mrp =   $request->mrp;
            $productVariant->stock_available = $request->stock_available;
            // $productVariant->cre_by =  Auth::user()->user_pid;
            $productVariant->save();
            DB::commit();

            return new ApiCommonResponseResource($productVariant, "Variant saved successfully", 200);
        } catch (Exception $e) {
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $productVariant = ProductVariant::where('product_pid', $id)->orderBy('varient_id','DESC')->get();

            return new ApiCommonResponseResource($productVariant, "Data fetched", 200);
        } catch (Exception $e) {
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $vid)
    {

        try {

            DB::beginTransaction();
            $productVariant = ProductVariant::where('varient_pid', $vid)->first();

            if (isset($request->varient_name)) {
                $productVariant->varient_name = $request->varient_name;
            }

            if (isset($request->varient_value)) {
                $productVariant->varient_value = $request->varient_value;
            }


            if (isset($request->varient_desc)) {
                $productVariant->varient_desc = $request->varient_desc;
            }

            if (isset($request->mrp_primary)) {
                $productVariant->mrp_primary = $request->mrp_primary;
            }

            if (isset($request->disc_pct)) {
                $productVariant->disc_pct = $request->disc_pct;
            }

            if (isset($request->mrp)) {
                $productVariant->mrp = $request->mrp;
            }


            if (isset($request->stock_available)) {
                $productVariant->stock_available = $request->stock_available;
            }
            // $productVariant->cre_by =  Auth::user()->user_pid;
            $productVariant->update();

            DB::commit();

            return new ApiCommonResponseResource($productVariant, "Variant update successfully", 200);
        } catch (Exception $e) {
            DB::rollBack();
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleteData = ProductVariant::where('varient_pid', $id)->delete();
        if ($deleteData) {

            return new DeleteResource("Variant Deleted", 204);
        } else {
            return (new ErrorResource("Variant not deleted", 501))->response()->setStatusCode(501);
        }
    }
}
