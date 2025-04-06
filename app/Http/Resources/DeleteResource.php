<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeleteResource extends JsonResource
{

    protected $statusCode;
    public $resource;

    public function __construct($resource, $statusCode = 200)
    {
        $this->statusCode = $statusCode;
        $this->resource = $resource;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' =>[],
        ];
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
                'message'=> $this->resource,
                'status' => true,
                'http_status' => $this->statusCode,
            ],
        ];
    }
}
