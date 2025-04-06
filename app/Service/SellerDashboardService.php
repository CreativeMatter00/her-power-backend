<?php

namespace App\Service;

use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\ProductCollection;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Mockery\Expectation;
use PhpParser\Node\Stmt\TryCatch;

class SellerDashboardService
{
    public function sellerDashboardInfo($uid)
    {
        $data = [];
        $sellerInfo =  DB::select("SELECT
    (
        select
            COUNT (er.rating_marks) as seller_total_rating
        FROM
            ec_enterp_rating er
        where
            er.enterpenure_pid = en.enterpenure_pid
    ) as seller_total_rating,
    (
        select
            COALESCE(
                ROUND(
                    SUM(er.rating_marks) / COUNT(er.enterpenure_pid) :: numeric,
                    1
                ),
                0
            ) as seller_avg_rating
        FROM
            ec_enterp_rating er
        where
            er.enterpenure_pid = en.enterpenure_pid
    ) as seller_avg_rating,
    (
        SELECT
            COALESCE(
                ROUND(
                    (
                        COUNT(
                            CASE
                                WHEN er.rating_marks BETWEEN 3
                                AND 5 THEN 1
                            END
                        ) * 100.0
                    ) / NULLIF(COUNT(*), 0),
                    2
                ),
                0
            ) AS positive_rating_percentage
        FROM
            ec_enterp_rating er
        WHERE
            er.enterpenure_pid = en.enterpenure_pid
    ) AS positive_rating_percentage,
    
    (select COUNT(ef.enterpenure_pid) FROM ec_enterp_follower ef where ef.enterpenure_pid = enterpenure_pid) as  total_followers
from
    ec_enterpenure en
where
    en.user_pid = ?", [$uid]);
        $orderList = DB::select("SELECT
om.order_pid,
    om.enterpenure_pid,
    om.customer_pid,
    cs.fname,
    cs.lname,
    om.total_amount,
    od.delivery_status
from
    ec_order_mst om
       left join ec_customer cs on cs.user_pid = om.user_pid
       
       LEFT JOIN ec_order_delivery od on om.order_pid = od.order_pid
where
    om.user_pid = ?", [$uid]);

        $data = [
            'sellerInfo' => $sellerInfo[0],
            'orders' => $orderList,
            'code' => 200,
            'message' => 'Data fatch successfully.',
        ];

        return  $data;
    }


    public function sellerBasicInfos($id)
    {
        $baseURL = asset('/public');


        return DB::select("SELECT en.shop_name,en.fname, en.lname, en.cre_date,'83'as ship_on_time, ( select COUNT (pro.rating_marks) as seller_total_rating FROM ec_rating AS pro where pro.enterpenure_pid = en.enterpenure_pid ) as seller_total_rating, 
        ( SELECT COALESCE( ROUND( (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric, 1 ), 0 ) as seller_rating FROM ec_rating AS pro WHERE pro.enterpenure_pid = en.enterpenure_pid ) as seller_avg_rating, 
        ( SELECT COALESCE( ROUND( ( COUNT( CASE WHEN pro.rating_marks BETWEEN 3 AND 5 THEN 1 END )*100.0 ) / NULLIF(COUNT(*), 0), 2 ), 0 ) AS positive_rating_percentage FROM ec_rating AS pro WHERE pro.enterpenure_pid  = en.enterpenure_pid ) AS positive_rating_percentage,
         ( select COUNT(ef.enterpenure_pid) FROM ec_enterp_follower ef where ef.enterpenure_pid = en.enterpenure_pid ) as total_followers, 
         CONCAT('$baseURL/',af.file_url) as file_url
         
          from ec_enterpenure en LEFT JOIN attached_file af on en.user_pid = af.ref_pid where en.enterpenure_pid = ?", [$id]);
    }

    public function sellerLast16Product($uid)
    {

        try {


            $sellerLastProduct = DB::table('ec_product as a')->select(
                'a.product_pid',
                'a.product_name',
                DB::raw('(
                SELECT COALESCE(ROUND(CAST(SUM(pro.rating_marks) AS numeric) / NULLIF(COUNT(pro.customer_pid), 0), 1), 0)
                FROM ec_rating AS pro
                WHERE pro.product_pid = a.product_pid
            ) AS AVG_RATING'),
                DB::raw('(
                SELECT af.img_thumb
                FROM attached_file AS af
                WHERE af.ref_pid = a.product_pid AND af.img_thumb IS NOT NULL
                LIMIT 1
            ) AS THUMBNAIL_IMG'),

                DB::raw('(
                SELECT varient_pid
                FROM (
                    SELECT ROW_NUMBER() OVER () AS rownum, pv.*
                    FROM ec_productvarient AS pv
                    WHERE pv.product_pid = a.product_pid
                ) AS data1
                WHERE rownum = 1
            ) AS varient_pid'),
                DB::raw('(
                SELECT mrp_primary
                FROM (
                    SELECT ROW_NUMBER() OVER () AS rownum, pv.*
                    FROM ec_productvarient AS pv
                    WHERE pv.product_pid = a.product_pid
                ) AS data1
                WHERE rownum = 1
            ) AS mrp_primary'),
                DB::raw('(
                SELECT disc_pct
                FROM (
                    SELECT ROW_NUMBER() OVER () AS rownum, pv.*
                    FROM ec_productvarient AS pv
                    WHERE pv.product_pid = a.product_pid
                ) AS data1
                WHERE rownum = 1
            ) AS disc_pct'),
                DB::raw('(
                SELECT mrp
                FROM (
                    SELECT ROW_NUMBER() OVER () AS rownum, pv.*
                    FROM ec_productvarient AS pv
                    WHERE pv.product_pid = a.product_pid
                ) AS data1
                WHERE rownum = 1
            ) AS mrp')
            )
                ->where('a.enterpenure_pid', $uid)
                ->where('a.active_status', 1)
                ->orderBy('a.product_id', 'DESC')
                ->limit(16)
                ->get();
            $sellerLastProduct->transform(function ($product) {
                $product->avg_rating = (float) number_format((float)$product->avg_rating, 2, '.', '');
                if (!empty($product->thumbnail_img)) {
                    $product->thumbnail_img = asset('/public/' . $product->thumbnail_img);
                }
                return $product;
            });

            $data['data'] =  $sellerLastProduct;

            return new ApiCommonResponseResource($data, "Data fatched", 200);
        } catch (Expectation $e) {

            return new ErrorResource('Oops! Something went wrong, Please try again', 501);
        }
    }


    public function sellerAllProcut($uid)
    {

        try {
            $data = [];
            $querydata = Product::select('product_pid', 'product_name', 'is_sale')->where('enterpenure_pid', $uid)->orderBy('product_pid', 'DESC')->paginate(20);
            $querydata->getCollection()->transform(function ($product) {
                $averageRating = DB::table('ec_rating as pro')
                    ->select(
                        DB::raw('COALESCE(ROUND(SUM(pro.rating_marks) / COUNT(pro.customer_pid)::numeric, 1), 0) as average_rating')
                    )
                    ->where('pro.product_pid', $product->product_pid)
                    ->value('average_rating');
                $product->avg_rating =  (float)   number_format((float)$averageRating, 1, '.', '');

                $thumbnailQuery = DB::table('attached_file')
                    ->select('img_thumb')
                    ->where('ref_pid', $product->product_pid)
                    ->whereNotNull('img_thumb')
                    ->limit(1);
                $thumbnail = DB::selectOne($thumbnailQuery->toSql(), $thumbnailQuery->getBindings());

                $imgName =  $thumbnail->img_thumb ?? null;
                $product->thumbnail_img = asset('/public/' . $imgName);
                $varient_pid = DB::table('ec_productvarient')
                    ->select('varient_pid')
                    ->where('product_pid', $product->product_pid)
                    ->limit(1);
                $varientPid  = DB::selectOne($varient_pid->toSql(), $varient_pid->getBindings());
                $product->varient_pid = $varientPid->varient_pid ?? null;


                $mrpPirceQuery = DB::table('ec_productvarient')
                    ->select('mrp_primary')
                    ->where('product_pid', $product->product_pid)
                    ->limit(1);
                $MRP_PRICE  = DB::selectOne($mrpPirceQuery->toSql(), $mrpPirceQuery->getBindings());
                $product->mrp_primary = $MRP_PRICE->mrp_primary ?? null;

                $mrpPirceMainQuery = DB::table('ec_productvarient')
                    ->select('mrp')
                    ->where('product_pid', $product->product_pid)
                    ->limit(1);
                $MRP  = DB::selectOne($mrpPirceMainQuery->toSql(), $mrpPirceMainQuery->getBindings());

                $product->mrp = $MRP->mrp ?? null;


                $disc_pct = DB::table('ec_productvarient')
                    ->select('disc_pct')
                    ->where('product_pid', $product->product_pid)
                    ->limit(1);
                $discPct  = DB::selectOne($disc_pct->toSql(), $disc_pct->getBindings());

                $product->disc_pct = $discPct->disc_pct ?? null;

                return $product;
            });


            if ($querydata) {
                $data = [
                    'data' => $querydata,
                    'code' => 200,
                    'message' => 'Data fatch successfully.',
                ];
            } else {
                $data = [
                    'data' => $querydata,
                    'code' => 401,
                    'message' => 'Data Not Found.',
                ];
            }

            return $data;
        } catch (Expectation $e) {

            return new ErrorResource('Oops! Something went wrong, Please try again', 501);
        }
    }


    public function getSellerDashBoradInfo($eid)
    {

        return DB::selectOne("SELECT DISTINCT a.enterpenure_pid, ( SELECT count(b.product_pid) as TODAYS_ORDER FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid AND b.cre_date :: date = CURRENT_TIMESTAMP :: date ) as TODAYS_ORDER, ( SELECT count(b.product_pid) FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid AND DATE_TRUNC('month', b.cre_date) = DATE_TRUNC('month', CURRENT_TIMESTAMP) ) as CURRENT_MONTH_ORDER, ( SELECT count(b.product_pid) FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid ) as ALL_ORDER, ( SELECT count(b.product_pid) FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid AND b.order_status = 1 ) as TOTAL_PENDING_ORDER, ( SELECT count(b.product_pid) FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid AND b.order_status = 2 ) as TOTAL_PROCESSING_ORDER, ( SELECT count(b.product_pid) FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid AND b.order_status = 3 ) as TOTAL_COMPLETE_ORDER, ( SELECT count(b.product_pid) FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid AND b.order_status = 0 ) as TOTAL_CANCEL_ORDER, ( SELECT COALESCE(sum(b.sales_amount), 0) FROM ec_order_chd b WHERE b.enterpenure_pid = a.enterpenure_pid AND b.order_status = 3 ) as TOTAL_SALE FROM ec_order_chd a WHERE a.enterpenure_pid = ?", [$eid]);
    }

    /**
     * orders of a seller
     *
     * @param  type  $eid
     * @return collection
     */
    public function getSellerDashBoradList($eid)
    {
        return DB::select("SELECT DISTINCT a.enterpenure_pid,
        b.order_pid, 
        b.total_amount,
        b.order_date,
        a.order_status as order_status_numb,
        b.customer_pid,
        c.mobile_no,
        c.full_name,
        (c.address_line||','||c.house_number||','||c.area_name||','||c.city_name||','||c.zip_postal_code) as full_address,
        CASE WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = b.order_pid AND a.order_status = 1 )  >= 1 THEN'PENDING'
        WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = b.order_pid AND a.order_status = 2 ) >= 1 THEN'PROCESSING'
        WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = b.order_pid AND a.order_status = 3 ) = ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = b.order_pid ) THEN'COMPLETED'
        ELSE 'CANCELLED'
        END As ORDER_STATUS 
        FROM ec_order_chd a 
        LEFT JOIN ec_order_mst b on a.order_pid = b.order_pid 
        LEFT JOIN ec_customer c on b.customer_pid = c.customer_pid
        WHERE a.enterpenure_pid = ? order BY b.order_date DESC", [$eid]);
    }


    public function orderDetailsQuery($oid)
    {
        $baseURL = asset('/public');

        return DB::select("SELECT
                a.order_pid,
                b.product_name,
                a.product_pid,
                a.quantity,
                a.mrp_price,
                a.delivery_charge,
                a.sales_amount,
                a.order_status as order_status_numb,
                CASE
                    WHEN a.order_status = 1 THEN 'PENDING'
                    WHEN a.order_status = 2 THEN 'PROCESSING'
                    WHEN a.order_status = 3 THEN 'COMPLETED'
                    WHEN a.order_status = 0 THEN 'CANCELED'
                    ELSE 'UNKNOWN ORDER STATUS'
                END AS order_status,
                    CONCAT('$baseURL/',( SELECT af.file_url FROM attached_file af WHERE af.ref_pid = a.product_pid LIMIT 1 ) ) AS file_url 
            FROM
                ec_order_chd a
                LEFT JOIN ec_product b on a.product_pid = b.product_pid
            WHERE
                a.order_pid = ?", [$oid]);
    }

    public function userOrderDetailsQuery($cid)
    {
        $baseURL = asset('/public');

        $data['processingOrders'] = $processingOrders = DB::select("SELECT
                                        DISTINCT
                                            om.order_pid,
                                            om.order_id,   
                                            om.total_amount,
                                            om.order_date,
                                                CASE WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 1 )  >= 1 THEN'PENDING'
                                                WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 2 ) >= 1 THEN'PROCESSING'
                                                WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 3 ) = ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid ) THEN'COMPLETED'
                                                ELSE'UNKNOWN ORDER STATUS'
                                                END As ORDER_STATUS,
                                                CASE WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 1 )  >= 1 THEN 1
                                                WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 2 ) >= 1 THEN 2
                                                WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 3 ) = ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid ) THEN 3
                                                ELSE 2
                                                END As ORDER_STATUS_NUMB 
                                        FROM
                                            ec_order_mst om
                                        LEFT JOIN ec_order_chd oc on om.order_pid = oc.order_pid
                                        where
                                            om.customer_pid = ? AND oc.order_status IN (1,2) ORDER BY  om.order_pid DESC", [$cid]);

        foreach ($processingOrders as &$order) {

            $products = DB::select("SELECT
                                            p.product_name,
                                            oc.product_pid,
                                            oc.quantity
                                        FROM
                                            ec_order_chd oc
                                        LEFT JOIN ec_product p ON oc.product_pid = p.product_pid
                                        WHERE oc.order_status = 1
                                        AND oc.order_pid = ?", [$order->order_pid]);

            $order->products_info = $products;
        }



        $data['previewsOrder'] = $previewsOrder = DB::select("SELECT
                                                            DISTINCT
                                                                om.order_pid,
                                                                om.order_id,   
                                                                om.total_amount,
                                                                om.order_date,
                                                                    CASE WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 1 )  >= 1 THEN'PENDING'
                                                                    WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 2 ) >= 1 THEN'PROCESSING'
                                                                    WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 3 ) = ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid ) THEN'COMPLETED'
                                                                    ELSE'CANCELLED'
                                                                    END As ORDER_STATUS,
                                                                    CASE WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 1 )  >= 1 THEN 1
                                                                    WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 2 ) >= 1 THEN 2
                                                                    WHEN ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid AND a.order_status = 3 ) = ( SELECT COUNT(a.product_pid) FROM ec_order_chd a WHERE a.order_pid = oc.order_pid ) THEN 3
                                                                    ELSE 0
                                                                    END As ORDER_STATUS_NUMB 
                                                            FROM
                                                                ec_order_mst om
                                                            LEFT JOIN ec_order_chd oc on om.order_pid = oc.order_pid
                                                            where
                                                                om.customer_pid = ? AND oc.order_status IN (0,3) ORDER BY  om.order_pid DESC", [$cid]);

        foreach ($previewsOrder as &$order) {

            $products = DB::select(" SELECT
                                    p.product_name,
                                    oc.quantity
                                FROM
                                    ec_order_chd oc
                                LEFT JOIN ec_product p ON oc.product_pid = p.product_pid
                                WHERE
                                    oc.order_pid = ?", [$order->order_pid]);

            $order->products_info = $products;
        }


        return $data;
    }
}
