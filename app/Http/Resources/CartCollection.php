<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CartCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function($cartItem) {
            return [
                'product_pid' => $cartItem->product_pid,
                'product_name' => $cartItem->product_name,
                'img_cart' => asset($cartItem->img_cart),
                'qty' => $cartItem->qty,
                'mrp_primary' => $cartItem->mrp_primary,
                'total_price' => $cartItem->total_price,
               
            ];
        })->all();
    }
}
