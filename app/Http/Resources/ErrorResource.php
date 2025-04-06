<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    protected $statusCode;
    public $resource;
    public $status;

    public function __construct($resource, $statusCode = 200, $status =false)
    {
        $this->statusCode = $statusCode;
        $this->resource = $resource;
        $this->status = $status;
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
                'status' => $this->status,
                'http_status' => $this->statusCode,
            ],
        ];
    }
}
