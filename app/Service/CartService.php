<?php

namespace App\Service;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function cartDetailsByCustomerId(string $customerId)
    {

        $result = DB::select("SELECT
                            a.product_pid,
                            a.qty,
                            a.varient_pid,
                            c.mrp_primary,
                            b.product_name,
                            (c.mrp_primary * a.qty) as total_price,
                            (
                            SELECT
                                    img_cart
                                FROM
                                    (
                                        SELECT
                                            ROW_NUMBER() OVER () AS rownum,
                                            img_cart
                                        from
                                            attached_file af
                                        where
                                            af.ref_pid = a.product_pid
                                    ) as attachfile
                                WHERE
                                    rownum = 1
                            ) as img_cart
                        FROM
                            ec_cartlist a
                            LEFT JOIN ec_product b on a.product_pid = b.product_pid
                            LEFT JOIN ec_productvarient c on c.varient_pid = a.varient_pid    
                        where
                            a.customer_pid = ?
                            AND a.order_done = 'N'", [$customerId]);


        return $result;
        if ($result) {
            return $result;
        } else {
            return 404;
        }
    }

    public static function getCartItemByCustomerId($customerId)
    {
        $baseURL = asset('/public/');

        $result = DB::select("SELECT
                                    cl.cart_pid,
                                    pd.product_name,
                                    cl.qty,
                                    cl.total_price,
                                    cl.varient_pid,
                                    pv.mrp_primary,
                                    pv.disc_pct,
                                    pv.mrp,
                                    CONCAT('$baseURL/',af.img_cart)as cart_img
                                    
                                from
                                    ec_cartlist cl
                                    left join ec_product pd on cl.product_pid = pd.product_pid
                                    left join ec_productvarient pv on cl.varient_pid = pv.varient_pid
                                    left join attached_file af on cl.product_pid = af.ref_pid
                                    and af.img_thumb IS NOT NULL
                                where
                                    cl.customer_pid = ?
                                    and cl.order_done = 'N'", [$customerId]);


        return  $result;
    }

    public static function getCartItemCalculation($product)
    {
        $baseURL = asset('/public/');
        $quantity = $product['quantity'];
        $result = DB::select("SELECT
                                    pd.enterpenure_pid,
                                    pd.product_pid,
                                    ? AS varient_pid,
                                    pv.varient_name,
                                    pd.product_name,
                                    pv.mrp_primary,
                                    pv.disc_pct,
                                    pv.mrp,
                                    CAST( ? AS INTEGER) as quantity,
                                    CAST( (pv.mrp * ?) AS INTEGER) as total_price,
                                    CONCAT('$baseURL/',af.img_cart)as cart_img
                                FROM
                                    ec_product pd
                                    left join ec_productvarient pv on pd.product_pid = pv.product_pid
                                    left join attached_file af on pd.product_pid = af.ref_pid
                                    and af.img_thumb IS NOT NULL
                                WHERE
                                    pd.product_pid = ?
                                    AND pv.varient_pid = ? ", [$product['varient_pid'],$quantity,$quantity,$product['product_pid'], $product['varient_pid']]);


        return  $result;
    }
}
