<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FrontendProductCollection extends ResourceCollection
{

    protected $statusCode;
    protected $message;

    public function __construct($resource, string $message, int $statusCode = 200)
    {
        parent::__construct($resource);
        $this->statusCode = $statusCode;
        $this->message = $message;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->transform(function ($product) {
            return [
                'product_pid' => $product->product_pid,
                'product_name' => $product->product_name,
                'is_sale' => $product->is_sale,
                'avg_rating' => $product->avg_rating,
                'thumbnail_img' => $product->thumbnail_img ? asset('/public/' . $product->thumbnail_img) : null,
                'varient_pid' => $product->varient_pid,
                'mrp_primary' => $product->mrp_primary,
                'mrp' => $product->mrp,
            ];
        })->toArray();
    }
    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'message' => $this->message,
                'status' => true,
                'http_status' => $this->statusCode,
            ],
        ];
    }
}
