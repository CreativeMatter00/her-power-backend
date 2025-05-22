<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\Customer;
use App\Models\Entrepreneur;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getUserInfo($uid)
    {

        $basePath = asset('/public/');

        $is_cust = Customer::where('user_pid', $uid)->exists();
        if (!$is_cust) {
            $getUserInfo = DB::select("SELECT 
                                en.enterpenure_id,
                                en.enterpenure_pid,
                                en.user_pid,
                                en.salutation,
                                en.fname,
                                en.lname,
                                en.full_name,
                                en.father_name,
                                en.mother_name,
                                en.gender,
                                en.dob,
                                en.mobile_no,
                                en.skill,
                                en.last_education,
                                en.shop_name,
                                en.business_name,
                                en.store_name,
                                en.product_category,
                                en.address_line,
                                en.location_pid,
                                en.house_number,
                                en.street_name,
                                en.area_name,
                                en.city_name,
                                en.zip_postal_code,
                                en.bank_name,
                                en.bank_code,
                                en.account_holder_name,
                                en.account_number,
                                CASE 
                                    WHEN af.file_url != '' THEN CONCAT('$basePath/', af.file_url) 
                                    ELSE NULL 
                                END as profile_photo,
                                CASE 
                                    WHEN en.nidimage_front_side != '' THEN CONCAT('$basePath/', en.nidimage_front_side) 
                                    ELSE NULL 
                                END as nidimage_front_side,
                                CASE 
                                    WHEN en.nidimage_back_side != '' THEN CONCAT('$basePath/', en.nidimage_back_side) 
                                    ELSE NULL 
                                END as nidimage_back_side,
                                CASE 
                                    WHEN en.tin_certificate_image != '' THEN CONCAT('$basePath/', en.tin_certificate_image) 
                                    ELSE NULL 
                                END as tin_certificate_image,
                                CASE 
                                    WHEN en.signature_image != '' THEN CONCAT('$basePath/', en.signature_image) 
                                    ELSE NULL 
                                END as signature_image,
                                CASE 
                                    WHEN en.trade_license_image != '' THEN CONCAT('$basePath/', en.trade_license_image) 
                                    ELSE NULL 
                                END as trade_license_image,
                                CASE 
                                    WHEN en.tax_id_image != '' THEN CONCAT('$basePath/', en.tax_id_image) 
                                    ELSE NULL 
                                END as tax_id_image,
                                CASE 
                                    WHEN en.vat_id_image != '' THEN CONCAT('$basePath/', en.vat_id_image) 
                                    ELSE NULL 
                                END as vat_id_image,
                                en.trade_licence,
                                en.tax_id,
                                en.vat_id,
                                en.bin_id,
                                en.account_type,
                                en.sell_other_websites,
                                en.sell_other_ecommerce,
                                en.own_ecommerce_site,
                                en.product_from,
                                en.annual_turnover,
                                en.number_product_sell,
                                en.user_name,
                                en.email,
                                en.email_verified_at,
                                en.remember_token,
                                en.ref_pid,
                                en.customer_pid,
                                en.ud_serialno,
                                en.remarks,
                                en.pid_currdate,
                                en.pid_prefix,
                                en.cre_date,
                                en.cre_by,
                                en.upd_date,
                                en.upd_by,
                                en.active_status,
                                en.unit_no,
                                u.user_pid,
                                u.email,
                                u.name
                                FROM ec_enterpenure en
                                LEFT JOIN users as u on en.user_pid = u.user_pid
                                LEFT JOIN attached_file af on en.user_pid = af.ref_pid
                                WHERE en.user_pid = ?", [$uid]);
        } else {
            $getUserInfo = DB::select("SELECT
                                        u.user_pid,
                                        c.customer_pid,
                                        c.fname,
                                        c.lname,
                                        u.email,
                                        c.mobile_no,
                                        u.name,
                                        c.address_line as customer_address,
                                        c.house_number as customer_house,
                                        c.area_name as customer_area_name,
                                        c.city_name as customer_city_name,
                                        c.zip_postal_code as customer_zip_postal_code,
                                        CASE 
                                            WHEN af.file_url != '' THEN CONCAT('$basePath/', af.file_url) 
                                            ELSE NULL 
                                        END as profile_photo,
                                        CAST(
                                            (
                                                SELECT
                                                    COUNT(od.delivery_status)
                                                FROM
                                                    ec_order_mst as om
                                                    LEFT JOIN ec_order_delivery as od on om.order_pid = od.order_pid
                                                WHERE
                                                    om.user_pid = c.user_pid
                                                    AND od.delivery_status = 'P'
                                            ) AS INTEGER
                                        ) as active_order,
                                        CAST(
                                            (
                                                SELECT
                                                    COUNT(od.delivery_status)
                                                FROM
                                                    ec_order_mst as om
                                                    LEFT JOIN ec_order_delivery as od on om.order_pid = od.order_pid
                                                WHERE
                                                    om.user_pid = c.user_pid
                                                    AND od.delivery_status = 'D'
                                            ) AS INTEGER
                                        ) as previous_order
                                    from
                                        ec_customer as c
                                        LEFT JOIN users as u on c.user_pid = u.user_pid
                                        LEFT JOIN attached_file af on c.user_pid = af.ref_pid
                                    where
                                        c.user_pid = ?", [$uid]);
        }
        $userInfo = $getUserInfo ? $getUserInfo[0] : null;

        return response()->json($userInfo);
    }

    public function updateUserInfo(Request $request, $id)
    {
        // validation
        $validator = Validator::make($request->all(), [
            // 
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->getMessageBag(), 400))->response()->setStatusCode(400);
        } else {
            try {
                $updateData = [];

                if ($request->has('fname')) {
                    $updateData['fname'] = $request->fname;
                }

                if ($request->has('lname')) {
                    $updateData['lname'] = $request->lname;
                }

                if ($request->has('mobile_no')) {
                    $updateData['mobile_no'] = $request->mobile_no;
                }

                if ($request->has('customer_address')) {
                    $updateData['address_line'] = $request->customer_address;
                }

                if ($request->has('customer_city_name')) {
                    $updateData['city_name'] = $request->customer_city_name;
                }

                if ($request->has('customer_area_name')) {
                    $updateData['area_name'] = $request->customer_area_name;
                }

                if ($request->has('customer_zip_postal_code')) {
                    $updateData['zip_postal_code'] = $request->customer_zip_postal_code;
                }

                DB::beginTransaction();
                Customer::where('user_pid', $id)->update($updateData);

                $user_data = Customer::where('user_pid', $id)->get();
                DB::commit();
                return response()->json([
                    'data' => $user_data,
                    'msg' => 'User Information update successfully',
                    'code' => 200,
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    public function changeUserPassword(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required',
            'old_password' => 'required',
        ]);

        if ($validated) {

            $getOldPassword = DB::table('users')->select('password')->where('user_pid', $request->user_pid)->first();


            $checkOldPassword = Hash::check($request->old_password, $getOldPassword->password);

            if ($checkOldPassword) {

                $userPwUpdate = User::where('user_pid', $request->user_pid)->update(
                    [
                        "password" => Hash::make($request->password),
                    ]
                );

                if ($userPwUpdate) {

                    return response()->json([
                        'msg' => 'User Password update successfully',
                        'code' => 200,
                    ]);
                } else {
                    return (new ErrorResource('User Password update failed', 501))->response()->setStatusCode(501);
                }
            } else {
                return (new ErrorResource('Old password not matched.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * get all users function
     * @author shohag <shohag@atilimited.com>
     * @param  [type]  $need
     * @return void
     */
    public function getAlluser($need = null)
    {
        if ($need == null) {
            return $users = Customer::with('user')->get()->map(function ($query) {
                $data = array_merge($query->toArray(), $query->user->toArray());
                unset($data['user']);
                return $data;
            })->values();
        } else {
            $users = Customer::with('user')->paginate($need);
            $modifiedUsers = $users->getCollection()->map(function ($query) {
                $data = array_merge($query->toArray(), $query->user->toArray());
                unset($data['user']);
                return $data;
            });
            $users->setCollection($modifiedUsers);
        }

        if (empty($users)) {
            return (new ErrorResource('Sorry! Users not found.', 400))->response()->setStatusCode(400);
        }

        return (new ApiCommonResponseResource($users, "User fatch successfully", 201))->response()->setStatusCode(201);
    }

    /**
     * get all seller function
     * @author shohag <shohag@atilimited.net>
     * @return void
     */
    public function getAllseller()
    {
        $basePath = asset('/public/');

        $seller_info = DB::select("SELECT 
                                en.enterpenure_id,
                                en.enterpenure_pid,
                                en.user_pid,
                                en.salutation,
                                en.fname,
                                en.lname,
                                en.full_name,
                                en.father_name,
                                en.mother_name,
                                en.gender,
                                en.dob,
                                en.mobile_no,
                                en.skill,
                                en.last_education,
                                en.shop_name,
                                en.business_name,
                                en.store_name,
                                en.product_category,
                                en.address_line,
                                en.location_pid,
                                en.house_number,
                                en.street_name,
                                en.area_name,
                                en.city_name,
                                en.zip_postal_code,
                                en.bank_name,
                                en.bank_code,
                                en.account_holder_name,
                                en.account_number,
                                CASE 
                                    WHEN af.file_url != '' THEN CONCAT('$basePath/', af.file_url) 
                                    ELSE NULL 
                                END as profile_photo,
                                CASE 
                                    WHEN en.nidimage_front_side != '' THEN CONCAT('$basePath/', en.nidimage_front_side) 
                                    ELSE NULL 
                                END as nidimage_front_side,
                                CASE 
                                    WHEN en.nidimage_back_side != '' THEN CONCAT('$basePath/', en.nidimage_back_side) 
                                    ELSE NULL 
                                END as nidimage_back_side,
                                CASE 
                                    WHEN en.tin_certificate_image != '' THEN CONCAT('$basePath/', en.tin_certificate_image) 
                                    ELSE NULL 
                                END as tin_certificate_image,
                                CASE 
                                    WHEN en.signature_image != '' THEN CONCAT('$basePath/', en.signature_image) 
                                    ELSE NULL 
                                END as signature_image,
                                CASE 
                                    WHEN en.trade_license_image != '' THEN CONCAT('$basePath/', en.trade_license_image) 
                                    ELSE NULL 
                                END as trade_license_image,
                                CASE 
                                    WHEN en.tax_id_image != '' THEN CONCAT('$basePath/', en.tax_id_image) 
                                    ELSE NULL 
                                END as tax_id_image,
                                CASE 
                                    WHEN en.vat_id_image != '' THEN CONCAT('$basePath/', en.vat_id_image) 
                                    ELSE NULL 
                                END as vat_id_image,
                                en.trade_licence,
                                en.tax_id,
                                en.vat_id,
                                en.bin_id,
                                en.account_type,
                                en.sell_other_websites,
                                en.sell_other_ecommerce,
                                en.own_ecommerce_site,
                                en.product_from,
                                en.annual_turnover,
                                en.number_product_sell,
                                en.user_name,
                                en.email,
                                en.email_verified_at,
                                en.remember_token,
                                en.ref_pid,
                                en.customer_pid,
                                en.ud_serialno,
                                en.remarks,
                                en.pid_currdate,
                                en.pid_prefix,
                                en.cre_date,
                                en.cre_by,
                                en.upd_date,
                                en.upd_by,
                                en.active_status,
                                en.unit_no,
                                en.approve_flag,
                                en.approve_by,
                                en.approve_date,
                                u.user_pid,
                                u.email,
                                u.name
                                FROM ec_enterpenure en
                                LEFT JOIN users as u on en.user_pid = u.user_pid
                                LEFT JOIN attached_file af on en.user_pid = af.ref_pid");

        if (empty($seller_info)) {
            return (new ErrorResource('Sorry! Seller not found.', 404))->response()->setStatusCode(404);
        }
        return (new ApiCommonResponseResource($seller_info, "Seller fatch successfully", 200))->response()->setStatusCode(200);
    }
}
