<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\CommonResourceWithoutNullFilter;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EntrepreneurResource;
use App\Models\Entrepreneur;
use App\Service\ImageUploadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;


class EntrepreneurController extends Controller
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
        $validator = Validator::make($request->all(), [
            'mobile_no' => 'required|unique:ec_enterpenure,mobile_no',
            'fname' => 'required',
            'father_name' => 'required',
            'mother_name' => 'required',
            'ud_serialno' => 'required',
            // 'dob' => 'required',
            'user_pid' => 'required',
            'shop_name'  => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                $insertEntrepreneur = new Entrepreneur();
                $insertEntrepreneur->fname = $request->fname;
                $insertEntrepreneur->lname = $request->lname;
                $insertEntrepreneur->father_name = $request->father_name;
                $insertEntrepreneur->mother_name = $request->mother_name;
                $insertEntrepreneur->gender = $request->gender;
                $insertEntrepreneur->user_pid = $request->user_pid;
                $insertEntrepreneur->mobile_no = $request->mobile_no;
                $insertEntrepreneur->address_line = $request->address_line;
                $insertEntrepreneur->shop_name = $request->shop_name;
                $insertEntrepreneur->ud_serialno = $request->ud_serialno;
                // $insertEntrepreneur->dob = Carbon::createFromFormat('d/m/Y',$request->dob)->format('Y-m-d'); 
                $insertEntrepreneur->cre_by = Auth::user()->user_pid;
                $insertEntrepreneur->save();
                $enterpenure_pid = Entrepreneur::where('enterpenure_id', $insertEntrepreneur->enterpenure_id)->pluck('enterpenure_pid')->first();
                $insertEntrepreneur->enterpenure_pid = $enterpenure_pid;
                DB::commit();
                return (new EntrepreneurResource($insertEntrepreneur, 201))->response()->setStatusCode(201);
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

    }



    public function updateSellerInfoUpdate(Request $request, ImageUploadService $imageUploadService, $eid)
    {

        $updateData = [];
        $enterpenure_pid = $eid;

        if ($request->has('fname')) {
            $updateData['fname'] = $request->fname;
        }

        if ($request->has('lname')) {
            $updateData['lname'] = $request->lname;
        }

        if ($request->has('shop_name')) {
            $updateData['shop_name'] = $request->shop_name;
        }

        if ($request->has('mobile_no')) {
            $updateData['mobile_no'] = $request->mobile_no;
        }

        if ($request->has('business_name')) {
            $updateData['business_name'] = $request->business_name;
        }

        if ($request->has('product_category')) {
            $updateData['product_category'] = $request->product_category;
        }

        if ($request->has('seller_address')) {
            $updateData['address_line'] = $request->seller_address;
        }

        if ($request->has('seller_area_name')) {
            $updateData['area_name'] = $request->seller_area_name;
        }

        if ($request->has('seller_city_name')) {
            $updateData['city_name'] = $request->seller_city_name;
        }

        if ($request->has('seller_zip_postal_code')) {
            $updateData['zip_postal_code'] = $request->seller_zip_postal_code;
        }

        if ($request->has('bank_name')) {
            $updateData['bank_name'] = $request->bank_name;
        }

        if ($request->has('bank_code')) {
            $updateData['bank_code'] = $request->bank_code;
        }

        if ($request->has('account_holder_name')) {
            $updateData['account_holder_name'] = $request->account_holder_name;
        }

        if ($request->has('account_number')) {
            $updateData['account_number'] = $request->account_number;
        }

        if ($request->has('account_type')) {
            $updateData['account_type'] = $request->account_type;
        }

        if ($request->has('sell_other_websites')) {
            $updateData['sell_other_websites'] = $request->sell_other_websites;
        }

        if ($request->has('sell_other_ecommerce')) {
            $updateData['sell_other_ecommerce'] = $request->sell_other_ecommerce;
        }

        if ($request->has('own_ecommerce_site')) {
            $updateData['own_ecommerce_site'] = $request->own_ecommerce_site;
        }

        if ($request->has('product_from')) {
            $updateData['product_from'] = $request->product_from;
        }

        if ($request->has('annual_turnover')) {
            $updateData['annual_turnover'] = $request->annual_turnover;
        }

        if ($request->has('number_product_sell')) {
            $updateData['number_product_sell'] = $request->number_product_sell;
        }
        if ($request->has('number_product_sell')) {
            $updateData['number_product_sell'] = $request->number_product_sell;
        }

        if ($request->has('nidimage_front_side')) {
            $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
            $updateData['nidimage_front_side'] =  $imageUploadService->uploadFileAndReturnPath($request->nidimage_front_side, $enterpenure_pid, $directory);
        }

        if ($request->has('nidimage_back_side')) {
            $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
            $updateData['nidimage_back_side'] =  $imageUploadService->uploadFileAndReturnPath($request->nidimage_back_side, $enterpenure_pid, $directory);
        }

        if ($request->has('tin_certificate_image')) {
            $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
            $updateData['tin_certificate_image'] =  $imageUploadService->uploadFileAndReturnPath($request->tin_certificate_image, $enterpenure_pid, $directory);
        }
        if ($request->has('signature_image')) {
            $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
            $updateData['signature_image'] =  $imageUploadService->uploadFileAndReturnPath($request->signature_image, $enterpenure_pid, $directory);
        }
        if ($request->has('trade_license_image')) {
            $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
            $updateData['trade_license_image'] =  $imageUploadService->uploadFileAndReturnPath($request->trade_license_image, $enterpenure_pid, $directory);
        }

        if ($request->has('vat_id_image')) {
            $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
            $updateData['vat_id_image'] =  $imageUploadService->uploadFileAndReturnPath($request->vat_id_image, $enterpenure_pid, $directory);
        }
        if ($request->has('tax_id_image')) {
            $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
            $updateData['tax_id_image'] =  $imageUploadService->uploadFileAndReturnPath($request->tax_id_image, $enterpenure_pid, $directory);
        }

        $updateData['upd_date'] = Carbon::now();
        DB::beginTransaction();
        $updateStatus = Entrepreneur::where('enterpenure_pid', $enterpenure_pid)->update($updateData);
        DB::commit();
        $sellerInfo = Entrepreneur::where('enterpenure_pid', $enterpenure_pid)->first();
        if ($updateStatus) {
            return new ApiCommonResponseResource($sellerInfo, "Update Successfully", 200);
        } else {
            return (new ErrorResource('Seller not found.', 404))->response()->setStatusCode(404);
        }
    }
}
