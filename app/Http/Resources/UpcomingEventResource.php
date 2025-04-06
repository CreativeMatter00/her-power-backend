<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpcomingEventResource extends JsonResource
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
                'venues' => isset($event->venues) ? $event->venues : null,
                'tricketInfo' => isset($event->tricketInfo) ? $event->tricketInfo : null,
                'notification' => isset($event->notification) ? $event->notification : null,
                'event_schedule' => isset($event->eventSchedule) ? $event->eventSchedule : null,
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
