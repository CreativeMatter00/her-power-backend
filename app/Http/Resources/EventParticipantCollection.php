<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EventParticipantCollection extends ResourceCollection
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
        return $this->resource->map(function ($event) {
            return [
                "participant_id"            => $event->participant_id,
                "participant_pid"           => $event->participant_pid,
                "event_pid"                 => $event->event_pid,
                "ticket_pid"                => $event->ticket_pid,
                "participant_name"          => $event->participant_name,
                "participant_email"         => $event->participant_email,
                "phone_no"                  => $event->phone_no,
                "participant_address"       => $event->participant_address,
                "cre_date"                  => $event->cre_date,
                "active_status"             => $event->active_status,
                "user_pid"                  => $event->user_pid,
                "event_info"                => $event->event[0],
                'banner_file_url'           => !empty($event->attachments) && isset($event->attachments[0]) && isset($event->attachments[0]['file_url']) ? asset('/public/' . $event->attachments[0]['file_url']) : null,
                'thumbnail_file_url'        => !empty($event->attachments) && isset($event->attachments[1]) && isset($event->attachments[1]['file_url']) ? asset('/public/' . $event->attachments[1]['file_url']) : null,
                'venues'                    => !empty($event->venues) ? (count($event->venues) > 1 ? $event->venues : (isset($event->venues[0]) ? $event->venues[0] : null)) : null,
                'tricket_info'              => !empty($event->tricketInfo) ? (count($event->tricketInfo) > 1 ? $event->tricketInfo : (isset($event->tricketInfo[0]) ? $event->tricketInfo[0] : null)) : null,
                'notification'              => !empty($event->notification) && isset($event->notification[0]) ? $event->notification[0] : null,
                'event_schedule'            => !empty($event->eventSchedule) ? (count($event->eventSchedule) > 1 ? $event->eventSchedule : (isset($event->eventSchedule[0]) ? $event->eventSchedule[0] : null)) : null,
            ];
        })->filter()->values()->toArray();
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
        return array_filter($array, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }
}
