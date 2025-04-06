<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\DeleteResource;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Models\Wishlist;
use Exception;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validation part
        $validator = Validator::make($request->all(), [
            'customer_pid'         => 'required',
            'product_pid'        => 'required',
            'varient_pid'  => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                $ifExists = Wishlist::select('customer_pid')->where([
                    ['customer_pid', '=', $request->customer_pid],
                    ['product_pid', '=', $request->product_pid]
                ])->count();
                if (!$ifExists > 0) {
                    DB::beginTransaction();

                    $insertWishListProduct = new Wishlist();
                    $insertWishListProduct->customer_pid  = $request->customer_pid;
                    $insertWishListProduct->product_pid  = $request->product_pid;
                    $insertWishListProduct->varient_pid  = $request->varient_pid;
                    $insertWishListProduct->save();

                    DB::commit();
                    return (new ApiCommonResponseResource($insertWishListProduct, "Product Added To wish List", 201))->response()->setStatusCode(201);
                } else {
                    DB::rollBack();
                    return (new ErrorResource("Product already exists", 409))->response()->setStatusCode(409);
                }
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $baseURL = asset('/public/');
            $wishListProducts = DB::select("SELECT 
                pd.product_pid,
                pd.product_name, 
                wl.varient_pid,
            ( SELECT COALESCE( ROUND( (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric, 1 ), 0 ) FROM ec_rating AS pro WHERE pro.product_pid = wl.product_pid ) AS AVG_RATING,
                    pv.mrp_primary, 
                    pv.disc_pct, 
                    pv.mrp, 
                    CONCAT('$baseURL/',af.img_wishlist)as img_wishlist
                    FROM ec_wishlist wl 
                    left join ec_product pd on wl.product_pid = pd.product_pid
                    left join attached_file af on wl.product_pid = af.ref_pid
                    and af.img_thumb IS NOT NULL left join ec_productvarient pv on wl.varient_pid = pv.varient_pid WHERE wl.customer_pid =?", [$id]);


            return (new ApiCommonResponseResource($wishListProducts, "Data Fetch Successfully", 201))->response()->setStatusCode(201);
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

        try {
            $ustomerIds = explode(',', $id);
            $deleteWishListProduct = Wishlist::where('customer_pid', $ustomerIds[0])->where('product_pid', $ustomerIds[1])->first();
            if (!empty($deleteWishListProduct)) {
                $deleteWishListProduct->delete();
                return (new DeleteResource("Successfully Deleted", 200))->response()->setStatusCode(200);
            } else {
                return (new DeleteResource("Data not found !", 404))->response()->setStatusCode(404);
            }
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
