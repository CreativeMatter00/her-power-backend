<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\CommonResourceWithoutNullFilter;
use App\Http\Resources\ErrorResource;
use App\Models\Entrepreneur;
use App\Service\CommonService;
use App\Service\CustomerService;
use App\Service\SellerDashboardService;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function getSellerInfoById($uid, SellerDashboardService $sellerDashboardService)
    {
        $sellerInfo = $sellerDashboardService->sellerDashboardInfo($uid);
        return  response()->json($sellerInfo);
    }
    public function sellerBasicInfo($uid, SellerDashboardService $sellerDashboardService)
    {
        $sellerInfo = $sellerDashboardService->sellerBasicInfos($uid);
        if ($sellerInfo) {

            $sellerInfo[0]->cre_date = date("Y-m-d", strtotime($sellerInfo[0]->cre_date));
        }

        $data =  $sellerInfo ?  $sellerInfo[0] : '';
        return  response()->json($data);
    }
    public function getSellerLast16Product($uid, SellerDashboardService $sellerDashboardService)
    {

        return $sellerDashboardService->sellerLast16Product($uid);
    }
    public function sellerAllProduct($uid, SellerDashboardService $sellerDashboardService)
    {
        return $sellerDashboardService->sellerAllProcut($uid);
    }
    public function getSellerDashboardOrderInfo($eid, SellerDashboardService $sellerDashboardService)
    {
        $data['data'] = $sellerDashboardService->getSellerDashBoradInfo($eid);
        return $data;
    }
    public function getSellerDashboardOrderList($eid, SellerDashboardService $sellerDashboardService)
    {
        $data['data'] = $sellerDashboardService->getSellerDashBoradList($eid);
        return $data;
    }
    public function getSellerDashboardOrderDetails($oid, SellerDashboardService $sellerDashboardService)
    {
        $data['data'] = $sellerDashboardService->orderDetailsQuery($oid);
        return $data;
    }

    public function getSellerProfileDatails($eid)
    {
        $sellerInfo = Entrepreneur::where('enterpenure_pid', $eid)->first();
        if ($sellerInfo) {
            return new CommonResourceWithoutNullFilter($sellerInfo, "Data Fetched", 200);
        } else {
            return (new ErrorResource('Seller not found.', 404))->response()->setStatusCode(404);
        }
    }

    public function getUserOrderDetails($cid, SellerDashboardService $sellerDashboardService)
    {
        $datas =  $sellerDashboardService->userOrderDetailsQuery($cid);

        return new ApiCommonResponseResource($datas, "Data Fetched", 200);
    }

    public function customerOrderCounter($cid)
    {
    return CustomerService::customerOrderCount($cid);
    }
}
