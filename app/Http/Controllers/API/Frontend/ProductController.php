<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\FrontendProductCollection;
use App\Http\Resources\FrontendProductResource;
use App\Http\Resources\ProductResource;
use App\Service\ProductService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use ReflectionFunctionAbstract;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductService $productService)
    {

        $products = $productService->productTitileRatingPrice();
        return (new FrontendProductCollection(collect($products['data']), $products['message'], $products['code']));
    }

    public function allProductPeginate(ProductService $productService)
    {
        $products = $productService->getAllProduct();
        return response()->json($products, 200);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id, ProductService $productService)
    {
        $products = $productService->getProductDetailsByid($id);
        return response()->json($products, 200);
    }

    public function popularProduct(ProductService $productService)
    {
        $products = $productService->getPopularProduct();
        return (new FrontendProductCollection(collect($products['data']), $products['message'], $products['code']));
    }

    public function populerProductPeginate(ProductService $productService)
    {
        $products = $productService->getPopulerProduct();
        return response()->json($products, 200);
    }

    public function newProduct(ProductService $productService)
    {
        $products = $productService->getNewProduct();
        return (new FrontendProductCollection(collect($products['data']), $products['message'], $products['code']));
    }

    public function newProductPeginate(ProductService $productService)
    {
        $products = $productService->newProductPeginate();
        return response()->json($products, 200);
    }
    public function productByCategoryid($cid, ProductService $productService)
    {
        $products = $productService->productByCategory($cid);
        return response()->json($products, 200);
    }

    public function productFilter(Request $request, ProductService $productService)
    {

        $products = $productService->productFilterForCustomer($request);


        return response()->json($products, 200);
    }


    public function productFilterSeller(Request $request, ProductService $productService)
    {

        $products = $productService->productFilterForSeller($request);


        return response()->json($products, 200);
    }
}
