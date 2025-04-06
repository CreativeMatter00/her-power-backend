<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartCollection;
use App\Http\Resources\CartResource;
use App\Http\Resources\DeleteResource;
use App\Http\Resources\ErrorResource;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Service\CartService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        $messages = [
            'unique' => 'This Product Is Already in the Cart',
        ];
        $validator = Validator::make($request->all(), [
            // 'cart_products' => 'required|array',
            // 'cart_products.*.product_pid' => 'required|unique:ec_cartlist,product_pid',
            // 'cart_products.*.varient_pid' => 'required',
            // 'cart_products.*.quantity' => 'required',
        ], $messages);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {
                $products = $request->input('cart_products');

                $customerID = "";
                DB::beginTransaction();
                foreach ($products as $cartProduct) {

                    $customerID = $cartProduct['customer_pid'];
                    $productPrice = ProductVariant::select('mrp')->where('varient_pid', $cartProduct['varient_pid'])->first();

                    $insertCartInfo = new Cart();
                    $insertCartInfo->customer_pid = $cartProduct['customer_pid'];
                    $insertCartInfo->product_pid = $cartProduct['product_pid'];
                    $insertCartInfo->varient_pid = $cartProduct['varient_pid'];
                    $insertCartInfo->qty = $cartProduct['quantity'];
                    $insertCartInfo->total_price = $productPrice->mrp * $cartProduct['quantity'];
                    $insertCartInfo->save();
                }




                $cartProduct = CartService::getCartItemByCustomerId($customerID);




                // $cart_pid = Cart::where('cart_id', $insertCartInfo->cart_id)->pluck('cart_pid')->first();
                // $totalIncart = Cart::where('customer_pid', $request->customer_pid)->where('order_done', 'N')->count('product_pid');
                // $insertCartInfo->cart_pid = $cart_pid;
                // $insertCartInfo->total_in_cart = $totalIncart;
                DB::commit();
                return (new CartResource($cartProduct, "Product added to cart successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Add to card failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, CartService $cartService)
    {

        $cartItem = $cartService->cartDetailsByCustomerId($id);

        if ($cartItem) {
            return new CartCollection($cartItem);
        } else {
            return (new ErrorResource("No cart item found", 404))->response()->setStatusCode(404);
        }
    }
    /**
     * Update the specified resource from storage.
     */
    public function updateCartItem(Request $request, string $customerId, string $productId, CartService $cartService)
    {

        $updateCart = Cart::where('customer_pid', $customerId)->where('product_pid', $productId)->update(['qty' => $request->qty]);
        if ($updateCart) {
            $cartItem = $cartService->cartDetailsByCustomerId($customerId);
            return new CartCollection($cartItem);
        } else {
            return (new ErrorResource("Item Update Failed", 422))->response()->setStatusCode(422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteCartItem(string $cid, string $pid)
    {
        $deleteCartItem = Cart::where('customer_pid', $cid)->where('product_pid', $pid)->delete();
        if ($deleteCartItem) {
            return (new DeleteResource("Item Deleted successfully", 200))->response()->setStatusCode(200);;
        } else {
            return (new ErrorResource("No cart item found", 404))->response()->setStatusCode(404);
        }
    }


    public function calculationCartItems(Request $request)
    {

        $products = $request->input('cart_products');
        if ($products) {
            $allProductDetails = [];

            foreach ($products as $product) {
              $productsDetails =  CartService::getCartItemCalculation($product);
                $allProductDetails[] = $productsDetails[0];
            }
            return (new CartResource($allProductDetails, "Data Fetched", 200))->response()->setStatusCode(200);
        } else {
            return (new ErrorResource("No cart item found", 404))->response()->setStatusCode(404);
        }
    }
}
