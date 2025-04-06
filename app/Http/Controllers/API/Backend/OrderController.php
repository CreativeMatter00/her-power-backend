<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderChd;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;


class OrderController extends Controller
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
    public function store(Request $request)
    {

        try {


            DB::beginTransaction();

            // creating new order 

            $insertOrder = new Order();
            $insertOrder->customer_pid = $request->customer_pid;
            $insertOrder->order_date = date("Y-m-d H:i:s");
            $insertOrder->total_amount = $request->total_amount;
            $insertOrder->shiping_location = $request->shiping_location;
            $insertOrder->save();
            $orderPid = DB::table('ec_order_mst')->select('order_pid')->where('order_id', $insertOrder->order_id)->first()->order_pid;
            $customerInfo = Customer::with('user')->where('customer_pid', $request->customer_pid)->get();

            foreach ($customerInfo as $customer) {
                $insertOrder->fname = $customer->fname; // From Customer
                $insertOrder->lname =  $customer->lname; // From Customer
                $insertOrder->mobile_no = $customer->mobile_no; // From Customer
                // Access related User data
                if ($customer->user) {
                    $insertOrder->email =  $customer->user->email; // From User
                }
            }

            foreach ($request->orders as $orderData) {

                $orderChd = new OrderChd();
                $orderChd->order_pid = $orderPid;
                $orderChd->product_pid = $orderData["product_pid"];
                $orderChd->quantity = $orderData["quantity"];
                $orderChd->enterpenure_pid = $orderData["enterpenure_pid"];
                $orderChd->varient_pid = $orderData["varient_pid"];
                $orderChd->mrp_price = $orderData["mrp_price"];
                $orderChd->disc_pct = $orderData["disc_pct"];
                $orderChd->disc_amt = $orderData["disc_amt"];
                $orderChd->vat_pct = $orderData["vat_pct"];
                $orderChd->vat_amt = $orderData["vat_amt"];
                $orderChd->delivery_charge = $orderData["delivery_charge"];
                $orderChd->order_status = 1;
                $orderChd->save();

                $productDetails = Product::where('product_pid', $orderData["product_pid"])->first();
                $availableStock = (int) $productDetails->stock_available;
                $orderQty  = (int) $orderData["quantity"];
                $finalQty =  $availableStock - $orderQty;
                $finalQty =  ($finalQty < 0) ? 0 :  $finalQty;
                Product::where('product_pid', $orderData["product_pid"])->update(['stock_available' => $finalQty]);
            }
            $insertOrder->order_pid = $orderPid;
            DB::commit();
            return (new ApiCommonResponseResource($insertOrder, 201))->response()->setStatusCode(201);
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $url = $this->baseURL;
        $updateSttaus = OrderChd::where('order_pid', $id)->where('product_pid', $request->product_pid)->update(
            [
                "order_status" => $request->order_status,
            ]
        );

        $orderUpdateData = DB::selectOne("SELECT a.product_pid, 
        b.product_name, 
        a.quantity,
        a.mrp_price, 
        a.sales_amount, 
        a.order_status,
        CONCAT('$url/',( SELECT af.file_url FROM attached_file af WHERE af.ref_pid = a.product_pid LIMIT 1 ) ) AS file_url 
        FROM ec_order_chd a LEFT JOIN ec_product b ON a.product_pid = b.product_pid where a.order_pid = ? and a.product_pid = ?", [$id, $request->product_pid]);


        if ($updateSttaus) {

            return new ApiCommonResponseResource((array) $orderUpdateData, "Data Fetched", 200);
        } else {
            return (new ErrorResource('Order status change failed', 501))->response()->setStatusCode(501);
        }
    }

    public function cancelOrder(Request $request)
    {


        try {

            $updateSttaus = OrderChd::where('order_pid', $request->order_pid)->where('product_pid', $request->product_pid)->update(
                [
                    "order_status" => 0,
                ]
            );

            $data = [];
            if ($updateSttaus) {
                return new ApiCommonResponseResource($data, "Order Canceled", 200);
            }
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    public function cancelWholeOrder(Request $request)
    {

        try {

            $updateSttaus = OrderChd::where('order_pid', $request->order_pid)->update(
                [
                    "order_status" => 0,
                ]
            );

            $data = [];
            if ($updateSttaus) {
                return new ApiCommonResponseResource($data, "Order Canceled", 200);
            }
        } catch (Exception $e) {

            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
