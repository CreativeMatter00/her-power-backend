<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UpcomingEventsCollection extends ResourceCollection
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
        // Use the resource resource directly and transform it
        return $this->resource->map(function ($event) {
            $banner_file_url = null;
            $thumbnail_file_url = null;

            // Check for attachments
            foreach ($event->attachments as $attachment) {
                if (strpos($attachment->file_url, 'event_banner') !== false) {
                    $banner_file_url = $attachment->file_url;
                } elseif (strpos($attachment->file_url, 'event_thumbnail') !== false) {
                    $thumbnail_file_url = $attachment->file_url;
                }
            }

            return [
                'event_pid' => $event->event_pid,
                'event_title' => $event->event_title,
                'event_desc' => $event->event_desc,
                'banner_file_url' => asset('/public/' . $banner_file_url),
                'thumbnail_file_url' => asset('/public/' . $thumbnail_file_url),
            ];
        })->all(); // Convert the resource to an array
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
