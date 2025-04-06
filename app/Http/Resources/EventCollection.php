<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EventCollection extends ResourceCollection
{
    protected $statusCode;
    protected $message;

    public function __construct($resource, string $message, int $statusCode = 200)
    {
        parent::__construct($resource);
        $this->statusCode = $statusCode;
        $this->message = $message;
    }

    public function toArray(Request $request): array
    {
        return [
            'events' => $this->collection->map(function ($event) {
                return [
                    'event_id'                  => $event->event_id,
                    'event_pid'                 => $event->event_pid,
                    'venue_pid'                 => $event->venue_pid,
                    'event_name'                => $event->event_name,
                    'event_title'               => $event->event_title,
                    'event_desc'                => $event->event_desc,
                    'start_date'                => $event->start_date,
                    'end_date'                  => $event->end_date,
                    'ud_serialno'               => $event->ud_serialno,
                    'remarks'                   => $event->remarks,
                    'pid_currdate'              => $event->pid_currdate,
                    'pid_prefix'                => $event->pid_prefix,
                    'cre_date'                  => $event->cre_date,
                    'cre_by'                    => $event->cre_by,
                    'upd_date'                  => $event->upd_date,
                    'upd_by'                    => $event->upd_by,
                    'active_status'             => $event->active_status,
                    'unit_no'                   => $event->unit_no,
                    'banner_image'              => $event->banner_image,
                    'thumbnail_image'           => $event->thumbnail_image,
                    'from_time'                 => $event->from_time,
                    'to_time'                   => $event->to_time,
                    'virtual_event'             => $event->virtual_event,
                    'vanue_name'                => $event->vanue_name,
                    'category_pid'              => $event->category_pid,
                    'category'                  => $event->category,
                    'tage'                      => $event->tage,
                    'ticket_type'               => $event->ticket_type,
                    'vanue_area'                => $event->vanue_area,
                    'vanue_city'                => $event->vanue_city,
                    'zip_code'                  => $event->zip_code,
                    'featchered_event'          => $event->featchered_event,
                    'org_id'                    => $event->org_id,
                    'banner_file_url'           => !empty($event->attachments) && isset($event->attachments[0]) && isset($event->attachments[0]['file_url']) ? asset('/public/'.$event->attachments[0]['file_url']) : null,
                    'thumbnail_file_url'        => !empty($event->attachments) && isset($event->attachments[1]) && isset($event->attachments[1]['file_url']) ? asset('/public/'.$event->attachments[1]['file_url']) : null,
                    'venues'                    => !empty($event->venues) ? (count($event->venues) > 1 ? $event->venues : (isset($event->venues[0]) ? $event->venues[0] : null)) : null,
                    'tricket_info'              => !empty($event->tricketInfo) ? (count($event->tricketInfo) > 1 ? $event->tricketInfo : (isset($event->tricketInfo[0]) ? $event->tricketInfo[0] : null)) : null,
                    'notification'              => !empty($event->notification) && isset($event->notification[0]) ? $event->notification[0] : null,
                    'event_schedule'            => !empty($event->eventSchedule) ? (count($event->eventSchedule) > 1 ? $event->eventSchedule : (isset($event->eventSchedule[0]) ? $event->eventSchedule[0] : null)) : null,
                ];
            })->filter(), // Remove null values from events
        ];
    }

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

    protected function filterNullValues(array $array)
    {
        return array_filter($array, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }
}
