<?php

namespace App\Service;

use App\Models\Attachment;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;


class ProductService
{
    /**
     * Get Product Information with rating and review.
     */
    public function productTitileRatingPrice()
    {

        $data = [];
        $querydata = DB::select("SELECT
                                        a.product_pid,
                                        a.product_name,
                                        a.is_sale,
                                        (
                                            SELECT
                                                COALESCE(
                                                    ROUND(
                                                        (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric,
                                                        1
                                                    ),
                                                    0
                                                )
                                            FROM
                                                ec_rating AS pro
                                            WHERE
                                                pro.product_pid = a.product_pid
                                        ) AS AVG_RATING,
                                        (
                                            select
                                                af.img_thumb
                                            from
                                                attached_file as af
                                            where
                                                af.ref_pid = a.product_pid
                                                and af.img_thumb IS NOT NULL
                                            limit
                                                1
                                        ) as THUMBNAIL_IMG,
                                        (
                                            SELECT
                                                varient_pid
                                            FROM
                                                (
                                                    SELECT
                                                        ROW_NUMBER() OVER () AS rownum,
                                                        pv.*
                                                    FROM
                                                        ec_productvarient AS pv
                                                    WHERE
                                                        pv.product_pid = a.product_pid
                                                ) AS data1
                                            where
                                                rownum = 1
                                        ) as varient_pid,
                                        (
                                            SELECT
                                                mrp_primary
                                            FROM
                                                (
                                                    SELECT
                                                        ROW_NUMBER() OVER () AS rownum,
                                                        pv.*
                                                    FROM
                                                        ec_productvarient AS pv
                                                    WHERE
                                                        pv.product_pid = a.product_pid
                                                ) AS data1
                                            where
                                                rownum = 1
                                        ) as mrp_primary,
                                        (
                                            SELECT
                                                disc_pct
                                            FROM
                                                (
                                                    SELECT
                                                        ROW_NUMBER() OVER () AS rownum,
                                                        pv.*
                                                    FROM
                                                        ec_productvarient AS pv
                                                    WHERE
                                                        pv.product_pid = a.product_pid
                                                ) AS data1
                                            where
                                                rownum = 1
                                        ) as disc_pct,
                                        (
                                            SELECT
                                                mrp
                                            FROM
                                                (
                                                    SELECT
                                                        ROW_NUMBER() OVER () AS rownum,
                                                        pv.*
                                                    FROM
                                                        ec_productvarient AS pv
                                                    WHERE
                                                        pv.product_pid = a.product_pid
                                                ) AS data1
                                            where
                                                rownum = 1
                                        ) as mrp
                                    FROM
                                        ec_product a
                                    where
                                        a.active_status = 1
                                    ORDER BY a.product_pid ASC
                                    LIMIT 16");

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
    }

    public function getProductDetailsByid($productidAndCustomerID)
    {

        $exploedData = explode(",", $productidAndCustomerID);
        $productid = $exploedData[0];
        $customerID = isset($exploedData[1]) ? $exploedData[1] : null;

        $data = [];
        $querydata = Product::select('product_pid', 'product_name', 'brand_name', 'model_name', 'is_sale', 'description', 'category_pid', 'enterpenure_pid', 'stockout_life', 're_stock_level')
            ->with([
                'attachments',
                'productvariants',
                'entrepreneurs:enterpenure_pid,user_pid,fname,lname,shop_name,cre_date',
                'reviewratings' => function ($query) {
                    $query->orderBy('rating_id', 'desc')->limit(3);
                }
            ])
            ->where('product_pid', $productid)
            ->where('active_status', 1)
            ->first();
        if ($querydata) {
            // check for review eligiblity
            if ($customerID != null) {
                $eligibleForReview = DB::table('ec_order_mst as a')
                    ->leftJoin('ec_order_chd as b', 'a.order_pid', '=', 'b.order_pid')
                    ->select(DB::raw('CASE WHEN b.order_status = 3 THEN TRUE ELSE FALSE END as eligible_for_review'))
                    ->where('a.customer_pid', $customerID)
                    ->where('b.product_pid', $productid)
                    ->first();
                // reviewing column modification 
                $querydata->eligible_for_review =  !empty($eligibleForReview) ? $eligibleForReview->eligible_for_review : false;
            } else {
                $querydata->eligible_for_review = false;
            }
            // rating query start
            $averageRating = DB::table('ec_rating as pro')
                ->select(
                    DB::raw('COALESCE(ROUND(SUM(pro.rating_marks) / COUNT(pro.customer_pid)::numeric, 1), 0) as average_rating')
                )
                ->where('pro.product_pid', $productid)
                ->value('average_rating');

            $querydata->avg_rating =  (float)   number_format((float)$averageRating, 1, '.', '');
            $totalRating = DB::table('ec_rating as pro')
                ->select(
                    DB::raw('count(pro.rating_marks) as total_rating')
                )
                ->where('pro.product_pid', $productid)
                ->value('total_rating');
            $querydata->total_rating =  (float)   number_format((float)$totalRating, 1, '.', '');
            $ratingSummary = DB::table('ec_rating as rat')->select(

                DB::raw('(select count(rati.rating_marks) from ec_rating rati where rati.product_pid = rat.product_pid and  rati.rating_marks = 1 ) AS total_1_star'),
                DB::raw('(select count(rati.rating_marks) from ec_rating rati where rati.product_pid = rat.product_pid and  rati.rating_marks = 2 ) AS total_2_star'),
                DB::raw('(select count(rati.rating_marks) from ec_rating rati where rati.product_pid = rat.product_pid and  rati.rating_marks = 3 ) AS total_3_star'),
                DB::raw('(select count(rati.rating_marks) from ec_rating rati where rati.product_pid = rat.product_pid and  rati.rating_marks = 4 ) AS total_4_star'),
                DB::raw('(select count(rati.rating_marks) from ec_rating rati where rati.product_pid = rat.product_pid and  rati.rating_marks = 5 ) AS total_5_star'),
            )->where('rat.product_pid',  $productid)->limit(1)->get();
            $ratingSummary = $ratingSummary->first();
            $ratingSummary = [
                'total_1_star' => (int) ($ratingSummary ?  $ratingSummary->total_1_star : 0),
                'total_2_star' => (int) ($ratingSummary ? $ratingSummary->total_2_star : 0),
                'total_3_star' => (int) ($ratingSummary ? $ratingSummary->total_3_star : 0),
                'total_4_star' => (int) ($ratingSummary ? $ratingSummary->total_4_star : 0),
                'total_5_star' => (int) ($ratingSummary ? $ratingSummary->total_5_star : 0),
            ];
            $querydata->rating_summary = $ratingSummary;
            // rating query end


            $similerProducts = DB::table('ec_product as a')->select(
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
                ->where('a.category_pid', $querydata->category_pid)
                ->whereNotIn('a.product_pid', [$productid])
                ->where('a.active_status', 1)
                ->orderBy('a.product_pid', 'DESC')
                ->limit(4)
                ->get();
            $similerProducts->transform(function ($product) {
                $product->avg_rating = (float) number_format((float)$product->avg_rating, 2, '.', '');
                if (!empty($product->thumbnail_img)) {
                    $product->thumbnail_img = asset('/public/' . $product->thumbnail_img);
                }
                return $product;
            });
            $querydata->you_may_also_like =  $similerProducts;
            // attachment column modification 
            $querydata->attachments->transform(function ($attachment) {
                $attachment->file_url =  asset('/public/' . $attachment->file_url);
                return $attachment;
            });
            // seller column modification
            if ($querydata->entrepreneurs) {
                $enterpenure_pid = $querydata->entrepreneurs->enterpenure_pid;
                $sellerRating = DB::select("SELECT
                                            COALESCE(
                                                ROUND(
                                                    (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric,
                                                    1
                                                ),
                                                0
                                            ) as seller_rating
                                        FROM
                                            ec_rating AS pro
                                        WHERE
                                            pro.enterpenure_pid = '$enterpenure_pid' ");
                $querydata->entrepreneurs->cre_date = date("Y-m-d", strtotime($querydata->entrepreneurs->cre_date));
                $querydata->entrepreneurs->seller_avg_rating = $sellerRating[0]->seller_rating;
            }

            $querydata->reviewratings->transform(function ($reviewrating) {
                $cutomerName = Customer::select('fname', 'lname')->where('customer_pid', $reviewrating->customer_pid)->first();
                $reviewrating->customer_name =  $cutomerName->fname . " " . $cutomerName->lname;
                $baseURl = asset('/public');
                $attachments = Attachment::select(DB::raw("CONCAT('$baseURl/', file_url) as full_file_url"))
                    ->where('ref_pid', $reviewrating->rating_pid)
                    ->get();
                $reviewrating->attachments = $attachments->toArray();

                return $reviewrating;
            });

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
    }

    public function getPopularProduct()
    {

        $data = [];
        $querydata = DB::select("SELECT a.product_pid, a.product_name, is_sale, ( SELECT COALESCE( ROUND( (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric, 1 ), 0 ) FROM ec_rating AS pro WHERE pro.product_pid = a.product_pid ) AS AVG_RATING, ( select af.img_thumb from attached_file as af where af.ref_pid = a.product_pid and af.img_thumb IS NOT NULL limit 1 ) as THUMBNAIL_IMG, ( SELECT varient_pid FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as varient_pid, ( SELECT mrp_primary FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp_primary, ( SELECT disc_pct FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as disc_pct, ( SELECT mrp FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp FROM ec_product a where a.active_status = 1 limit 8");

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
    }

    public function getNewProduct()
    {

        $data = [];
        $querydata = DB::select("SELECT a.product_pid, a.product_name, is_sale, ( SELECT COALESCE( ROUND( (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric, 1 ), 0 ) FROM ec_rating AS pro WHERE pro.product_pid = a.product_pid ) AS AVG_RATING, ( select af.img_thumb from attached_file as af where af.ref_pid = a.product_pid and af.img_thumb IS NOT NULL limit 1 ) as THUMBNAIL_IMG, ( SELECT varient_pid FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as varient_pid, ( SELECT mrp_primary FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp_primary, ( SELECT disc_pct FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as disc_pct, ( SELECT mrp FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp FROM ec_product a where a.active_status = 1 ORDER BY a.product_pid DESC limit 8");

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
    }

    public function getPoroductByCategoryId(string $category_id)
    {

        $data = [];
        $querydata = DB::select("SELECT a.product_pid,
         a.product_name, 
         ( SELECT COALESCE( ROUND( (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric, 1 ), 0 ) FROM ec_rating AS pro WHERE pro.product_pid = a.product_pid ) AS AVG_RATING,
        ( select af.img_thumb from attached_file as af where af.ref_pid = a.product_pid and af.img_thumb IS NOT NULL limit 1 ) as THUMBNAIL_IMG, 
        ( SELECT varient_pid FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as varient_pid,

        ( SELECT mrp_primary FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp_primary,
        ( SELECT disc_pct FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as disc_pct, 
        ( SELECT mrp FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp
         FROM ec_product a where a.active_status = 1 AND a.category_pid = ? ", [$category_id]);

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
    }

    public function getAllProduct()
    {

        $data = [];
        $querydata = Product::select('product_pid', 'product_name', 'is_sale')->orderBy('product_pid', 'ASC')->paginate(16);
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
    }

    public function getPopulerProduct()
    {

        $data = [];
        $querydata = Product::select('product_pid', 'product_name', 'is_sale')->paginate(8);
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
    }

    public function newProductPeginate()
    {
        $data = [];
        $querydata = Product::select('product_pid', 'product_name', 'is_sale')->where('active_status', 1)->orderBy('product_pid', 'DESC')->paginate(20);
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
    }

    public function productByCategory($cid)
    {
        $data = [];
        $querydata = Product::select('product_pid', 'product_name', 'is_sale')->where('category_pid', $cid)->where('active_status', 1)->orderBy('product_pid', 'DESC')->paginate(20);
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
    }

    // product filter
    public function productFilterForCustomer($request)
    {

        $data = [];
        $baseURL = asset('/public');
        $orderBy = "";
        $query = "";
        $product_name = $request->query('productName');
        $filterType =  $request->query('type');
        $priceRange =  explode("-", $request->query('range'));
        $category =  $request->query('category');
        $rating =  $request->query('rating');
        $priceRange = array_pad($priceRange, 2, null);
        $priceRangeFrom = $priceRange[0];
        $priceRangeTo = $priceRange[1];
        $productNameSearch = "";
        $categorySearch = "";
        $addRatingFilter = "";
        $priceRangesearch = "";

        $con_oper = 'WHERE'; //contitional operator
        if (!empty($product_name)) {

            $productNameSearch = "$con_oper UPPER (DATA1.product_name) LIKE '%" . strtoupper(trim($product_name)) . "%'";
            $con_oper = 'AND';
        }
        $query .= $productNameSearch;
        if (!empty($category)) {
            $categorySearch = " $con_oper DATA1.category_pid = '$category'";
            $con_oper = 'AND';
        }
        $query .= $categorySearch;

        if (!empty($rating)) {
            $addRatingFilter  = " $con_oper (DATA1.AVG_RATING >= $rating OR DATA1.AVG_RATING < $rating)";
            $con_oper = 'AND';
        }
        $query .= $addRatingFilter;

        if ($priceRange) {

            if ($priceRangeFrom > 0  &   $priceRangeTo > 0) {
                $priceRangesearch = " AND DATA1.mrp_primary BETWEEN $priceRangeFrom AND  $priceRangeTo";
            } elseif ($priceRangeFrom > 0) {

                $priceRangesearch = " AND DATA1.mrp_primary >= $priceRangeFrom";
            } elseif ($priceRangeTo > 0) {
                $priceRangesearch = " AND DATA1.mrp_primary <=  $priceRangeTo";
            } else {
                $priceRangesearch = "";
            }
        }
        $query .= $priceRangesearch;


        if ($filterType == 'popular') {
            $orderBy = " ORDER BY DATA1.AVG_RATING DESC, DATA1.total_sale DESC";
        } elseif ($filterType == 'lowToHigh') {
            $orderBy = " ORDER BY DATA1.AVG_RATING DESC, DATA1.mrp_primary ASC";
        } else {
            $orderBy = " ORDER BY DATA1.AVG_RATING DESC, DATA1.mrp_primary DESC";
        }
        $query .= $orderBy;


        $querydata = DB::select("SELECT * FROM ( SELECT a.product_pid, a.product_name, a.is_sale, a.category_pid,
        (SELECT count(chd.orderchd_id) as totalSale FROM ec_order_chd chd where chd.product_pid = a.product_pid and chd.order_status = 3) as total_sale, 
        ( SELECT COALESCE( ROUND( (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric, 1 ), 0 ) FROM ec_rating AS pro WHERE pro.product_pid = a.product_pid ) AS AVG_RATING, 
        CONCAT('$baseURL/',( SELECT af.img_thumb from attached_file as af where af.ref_pid = a.product_pid and af.img_thumb IS NOT NULL limit 1 ) ) as THUMBNAIL_IMG, 
        ( SELECT varient_pid FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as varient_pid, 
        ( SELECT mrp_primary FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp_primary,
        ( SELECT disc_pct FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as disc_pct,
        ( SELECT mrp FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp 
        FROM ec_product a where a.active_status = 1 ) 
        DATA1 $query");

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
    }

    public function productFilterForSeller($request)
    {
        $data = [];
        $baseURL = asset('/public');
        $orderBy = "";
        $query = "";
        $product_name = $request->query('productName');
        $filterType =  $request->query('type');
        $category =  $request->query('category');
        $rating =  $request->query('rating');
        $enterpenure_pid =  $request->query('enterpenure_pid');
        $priceRange =  explode("-", $request->query('range'));

        $priceRange = array_pad($priceRange, 2, null);
        $priceRangeFrom = $priceRange[0];
        $priceRangeTo = $priceRange[1];
        $productNameSearch = "";
        $categorySearch = "";
        $addRatingFilter = "";
        $priceRangesearch = "";

        if (!empty($product_name)) {

            $productNameSearch = " WHERE UPPER (DATA1.product_name) LIKE '%" . strtoupper(trim($product_name)) . "%'";
        }
        $query .= $productNameSearch;
        if (!empty($category)) {
            $categorySearch = " AND DATA1.category_pid = '$category'";
        }
        $query .= $categorySearch;

        if (!empty($rating)) {

            $addRatingFilter  = " AND (DATA1.AVG_RATING >= $rating OR DATA1.AVG_RATING < $rating)";
        }
        $query .= $addRatingFilter;

        if ($priceRange) {

            if ($priceRangeFrom > 0  &   $priceRangeTo > 0) {
                $priceRangesearch = " AND DATA1.mrp_primary BETWEEN $priceRangeFrom AND  $priceRangeTo";
            } elseif ($priceRangeFrom > 0) {

                $priceRangesearch = " AND DATA1.mrp_primary >= $priceRangeFrom";
            } elseif ($priceRangeTo > 0) {
                $priceRangesearch = " AND DATA1.mrp_primary <=  $priceRangeTo";
            } else {
                $priceRangesearch = "";
            }
        }
        $query .= $priceRangesearch;


        if ($filterType == 'popular') {
            $orderBy = " ORDER BY DATA1.AVG_RATING DESC, DATA1.total_sale DESC";
        } elseif ($filterType == 'lowToHigh') {
            $orderBy = " ORDER BY DATA1.AVG_RATING DESC, DATA1.mrp_primary ASC";
        } else {
            $orderBy = " ORDER BY DATA1.AVG_RATING DESC, DATA1.mrp_primary DESC";
        }
        $query .= $orderBy;


        $querydata = DB::select("SELECT * FROM ( SELECT a.product_pid, a.product_name, a.is_sale, a.category_pid,
        (SELECT count(chd.orderchd_id) as totalSale FROM ec_order_chd chd where chd.product_pid = a.product_pid and chd.order_status = 3) as total_sale, 
        ( SELECT COALESCE( ROUND( (SUM(pro.rating_marks) / COUNT(pro.customer_pid)) :: numeric, 1 ), 0 ) FROM ec_rating AS pro WHERE pro.product_pid = a.product_pid ) AS AVG_RATING, 
        CONCAT('$baseURL/',( SELECT af.img_thumb from attached_file as af where af.ref_pid = a.product_pid and af.img_thumb IS NOT NULL limit 1 ) ) as THUMBNAIL_IMG, 
        ( SELECT varient_pid FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as varient_pid, 
        ( SELECT mrp_primary FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp_primary,
        ( SELECT disc_pct FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as disc_pct,
        ( SELECT mrp FROM ( SELECT ROW_NUMBER() OVER () AS rownum, pv.*FROM ec_productvarient AS pv WHERE pv.product_pid = a.product_pid ) AS data1 where rownum = 1 ) as mrp 
        FROM ec_product a where a.active_status = 1 AND a.enterpenure_pid = ? ) 
        DATA1 $query", [$enterpenure_pid]);

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
    }
}
