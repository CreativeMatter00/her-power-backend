<?php

namespace App\Service;

use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use Exception;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public static function customerOrderCount($cid)
    {



        try {

            // // all order 

            // $allOrders = DB::select("SELECT COUNT(DISTINCT om.order_pid) AS all_orders
            //                 FROM ec_order_mst om
            //                 JOIN ec_order_chd oc ON om.order_pid = oc.order_pid
            //                 WHERE oc.order_status IN (0,1,2,3) and om.customer_pid = ?", [$cid]);
            // $data['all_order'] = $allOrders[0]->all_orders;
            // // active_order
            // $activeOrder = DB::select("SELECT COUNT(DISTINCT om.order_pid) AS active_order
            //                 FROM ec_order_mst om
            //                 JOIN ec_order_chd oc ON om.order_pid = oc.order_pid
            //                 WHERE oc.order_status IN (1,2) and om.customer_pid = ?", [$cid]);
            // $data['active_order'] = $activeOrder[0]->active_order;
            // // preview's order
            // $previewsOrder = DB::select("SELECT COUNT(DISTINCT om.order_pid) AS previews_order
            //                 FROM ec_order_mst om
            //                 JOIN ec_order_chd oc ON om.order_pid = oc.order_pid
            //                 WHERE oc.order_status IN (0,3) and om.customer_pid = ?", [$cid]);
            // $data['previews_order'] = $previewsOrder[0]->previews_order;

            $data = DB::select("WITH order_status_summary AS (
                                            SELECT 
                                                om.order_pid,
                                                COUNT(CASE WHEN oc.order_status IN (1, 2) THEN 1 END) AS active_count,
                                                COUNT(CASE WHEN oc.order_status IN (0, 3) THEN 1 END) AS previous_count
                                            FROM 
                                                public.ec_order_mst om
                                            JOIN 
                                                public.ec_order_chd oc ON om.order_pid = oc.order_pid
                                            GROUP BY 
                                                om.order_pid
                                        )
                                        SELECT 
                                            COUNT(om.order_pid) AS all_orders,  -- Total orders
                                            COUNT(CASE WHEN oss.active_count > 0 THEN 1 END) AS active_orders,  -- Orders with at least one active child (order_status 1 or 2)
                                            COUNT(CASE WHEN oss.active_count = 0 AND oss.previous_count > 0 THEN 1 END) AS previous_orders  -- Orders where all children have status 0 or 3
                                        FROM 
                                            public.ec_order_mst om
                                        JOIN 
                                            order_status_summary oss ON om.order_pid = oss.order_pid
                                            
                                            where om.customer_pid = ?", [$cid]);

            $data =  $data[0];

            return new ApiCommonResponseResource((array) $data, "data fetched", 200);
        } catch (Exception $e) {

            return new ErrorResource("Order not found", 404);
        }
    }
}
