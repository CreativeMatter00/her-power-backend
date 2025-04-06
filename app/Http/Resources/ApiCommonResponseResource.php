<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiCommonResponseResource extends JsonResource
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
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $array = parent::toArray($request);
        return $this->filterNullValues($array);
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        $meta = [
            'message' => $this->message,
            'status' => true,
            'http_status' => $this->statusCode,
        ];

        return [
            'meta' => $this->filterNullValues($meta),
        ];
    }

    /**
     * Filter out null, empty strings, and empty arrays from an array.
     *
     * @param  array  $array
     * @return array
     */
    protected function filterNullValues(array $array)
    {
        return array_filter($array, function ($value) {
            if (is_array($value)) {
                return !empty($this->filterNullValues($value));
            }
            return !is_null($value) && $value !== '';
        });
    }
}
