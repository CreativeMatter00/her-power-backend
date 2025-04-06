<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryFrontendResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Service\ProductService;

/**
 * @OA\Tag(
 *     name="Frontend>Category",
 *     description="Operations related to Categories"
 * )
 */


class CategoryFrontendController extends Controller
{


    public function getCategoryData()
    {
        $categoryList = Category::with('attachments')->where('active_status', 1)->orderBy('ud_serialno', 'asc')->paginate(15);
        return new CategoryCollection($categoryList);
    }


    public function getCategoryWiseProducts($cid, ProductService $productService)
    {
        $products = $productService->getPoroductByCategoryId($cid);
        return new ProductResource($products['data'], $products['message'], $products['code']);
    }
}
