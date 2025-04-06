<?php

namespace App\Service;

use App\Models\Entrepreneur;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SellerService
{
    public static function updateSellerInfo($request, $id)
    {

        $updateData = [];

        if ($request->has('fname')) {
            $updateData['fname'] = $request->fname;
        }

        if ($request->has('lname')) {
            $updateData['lname'] = $request->lname;
        }

        if ($request->has('shop_name')) {
            $updateData['shop_name'] = $request->shop_name;
        }

        if ($request->has('business_name')) {
            $updateData['business_name'] = $request->business_name;
        }

        if ($request->has('product_category')) {
            $updateData['product_category'] = $request->product_category;
        }

        if ($request->has('address_line')) {
            $updateData['address_line'] = $request->seller_address;
        }

        if ($request->has('area_name')) {
            $updateData['area_name'] = $request->seller_area_name;
        }

        if ($request->has('city_name')) {
            $updateData['city_name'] = $request->seller_city_name;
        }

        if ($request->has('zip_postal_code')) {
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


        $updateData['upd_date'] = Carbon::now();

        DB::beginTransaction();
        $updateStatus = Entrepreneur::where('enterpenure_pid', $id)->update($updateData);
        DB::commit();
    }
}
