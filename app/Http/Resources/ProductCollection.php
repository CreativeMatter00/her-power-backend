<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
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
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->transform(function ($product) {

            $attachments = null;
            $productvariants = null;

            if ($product->attachments) {
                $attachments = $product->attachments->map(function ($attachment) {
                    return $this->filterNullValues([
                        'attached_id' => $attachment->attached_id,
                        'attached_pid' => $attachment->attached_pid,
                        'ref_pid' => $attachment->ref_pid,
                        'main_image' => asset('/public/' . $attachment->file_url),
                        'img_thumb' => asset('/public/' . $attachment->img_thumb),
                        'img_cart' => asset('/public/' . $attachment->img_cart),
                        'img_wishlist' => asset('/public/' . $attachment->img_wishlist),
                        'file_extantion' => $attachment->file_extantion,
                        'active_status' => $attachment->active_status,
                    ]);
                });
            }

            if ($product->productvariants) {
                $productvariants = $product->productvariants->map(function ($productvariants) use (&$total_stock) {
                    $total_stock += $productvariants->stock_available;
                    return $this->filterNullValues([
                        'varient_pid'  => $productvariants->varient_pid,
                        'varient_name' => $productvariants->varient_name,
                        'varient_value' => $productvariants->varient_value,
                        'varient_desc' => $productvariants->varient_desc,
                        'mrp_primary' => $productvariants->mrp_primary,
                        'disc_pct' => $productvariants->disc_pct,
                        'mrp' => $productvariants->mrp,
                        'stock_available' => $productvariants->stock_available,
                    ]);
                });
            }

            return $this->filterNullValues([
                'product_pid' => $product->product_pid,
                'product_name' => $product->product_name,
                'category_pid' => $product->category_pid,
                'enterpenure_pid' => $product->enterpenure_pid,
                'uom_no' => $product->uom_no,
                'brand_name' => $product->brand_name,
                'model_name' => $product->model_name,
                'stock_available' => $total_stock,
                'variant' => $productvariants,
                'attachments' => $attachments,
            ]);
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
            'meta' => $this->filterNullValues([
                'total' => $this->resource->total(),
                'count' => $this->resource->count(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'total_pages' => $this->resource->lastPage(),
                'message' => $this->message,
                'status' => true,
                'http_status' => $this->statusCode,
            ]),
            'links' => $this->filterNullValues([
                'self' => $this->resource->url($this->resource->currentPage()),
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ]),
        ];
    }

    /**
     * Filter out null or empty values from an array.
     *
     * @param  array  $array
     * @return array
     */
    protected function filterNullValues(array $array)
    {
        return  $array;
        // return array_filter($array, function ($value) {
        //     return !is_null($value) && $value !== '';
        // });
    }
}
