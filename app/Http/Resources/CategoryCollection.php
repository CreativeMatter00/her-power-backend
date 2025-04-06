<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $filteredCollection = $this->collection->skip(1);
        return $this->collection->transform(function ($category) {
            $firstFileUrl = $category->attachments->first() ? asset('/public/'.$category->attachments->first()->file_url) : null;
            return [
              
                'category_pid' => $category->category_pid,
                'category_name' => $category->category_name,
                'category_desc' => $category->category_desc,
                'active_status' => $category->active_status,
                'file_url' => $firstFileUrl,
            ]; })->toArray();
    }

    // /**
    //  * Get additional data that should be returned with the resource array.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return array
    //  */
    // public function with($request): array
    // {
    //     return [
    //         'meta' => [
    //             'total' => $this->resource->total(),
    //             'count' => $this->resource->count(),
    //             'per_page' => $this->resource->perPage(),
    //             'current_page' => $this->resource->currentPage(),
    //             'total_pages' => $this->resource->lastPage(),
    //         ],
    //         'links' => [
    //             'self' => $this->resource->url($this->resource->currentPage()),
    //             'first' => $this->resource->url(1),
    //             'last' => $this->resource->url($this->resource->lastPage()),
    //             'prev' => $this->resource->previousPageUrl(),
    //             'next' => $this->resource->nextPageUrl(),
    //         ],
    //     ];
    // }
}
