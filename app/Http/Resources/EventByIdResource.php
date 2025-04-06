<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventByIdResource extends JsonResource
{
    protected $is_exist;
    protected $statusCode;
    protected $message;

    public function __construct($resource, $is_exist = null, string $message, int $statusCode = 200)
    {
        parent::__construct($resource);
        $this->is_exist = $is_exist;
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
        // $array = parent::toArray($request);

        $array = [
            'event_id'                  => $this->event_id,
            'event_pid'                 => $this->event_pid,
            'venue_pid'                 => $this->venue_pid,
            'event_name'                => $this->event_name,
            'event_title'               => $this->event_title,
            'event_desc'                => $this->event_desc,
            'start_date'                => $this->start_date,
            'end_date'                  => $this->end_date,
            'ud_serialno'               => $this->ud_serialno,
            'remarks'                   => $this->remarks,
            'pid_currdate'              => $this->pid_currdate,
            'pid_prefix'                => $this->pid_prefix,
            'cre_date'                  => $this->cre_date,
            'cre_by'                    => $this->cre_by,
            'upd_date'                  => $this->upd_date,
            'upd_by'                    => $this->upd_by,
            'active_status'             => $this->active_status,
            'unit_no'                   => $this->unit_no,
            'banner_image'              => $this->banner_image,
            'thumbnail_image'           => $this->thumbnail_image,
            'from_time'                 => $this->from_time,
            'to_time'                   => $this->to_time,
            'virtual_event'             => $this->virtual_event,
            'vanue_name'                => $this->vanue_name,
            'category_pid'              => $this->category_pid,
            'category'                  => $this->category,
            'tage'                      => $this->tage,
            'ticket_type'               => $this->ticket_type,
            'vanue_area'                => $this->vanue_area,
            'vanue_city'                => $this->vanue_city,
            'zip_code'                  => $this->zip_code,
            'featchered_event'          => $this->featchered_event,
            'org_id'                    => $this->org_id,
            'already_registered'        => $this->is_exist != null ? true : false ,
            'banner_file_url'           => !empty($this->attachments) && isset($this->attachments[0]) && isset($this->attachments[0]['file_url']) ? asset('/public/'.$this->attachments[0]['file_url']) : null,
            'thumbnail_file_url'        => !empty($this->attachments) && isset($this->attachments[1]) && isset($this->attachments[1]['file_url']) ? asset('/public/'.$this->attachments[1]['file_url']) : null,
            'venues'                    => !empty($this->venues) ? (count($this->venues) > 1 ? $this->venues : (isset($this->venues[0]) ? $this->venues[0] : null)) : null,
            'tricket_info'              => !empty($this->tricketInfo) ? (count($this->tricketInfo) > 1 ? $this->tricketInfo : (isset($this->tricketInfo[0]) ? $this->tricketInfo[0] : null)) : null,
            'notification'              => !empty($this->notification) && isset($this->notification[0]) ? $this->notification[0] : null,
            'event_schedule'            => !empty($this->eventSchedule) ? (count($this->eventSchedule) > 1 ? $this->eventSchedule : (isset($this->eventSchedule[0]) ? $this->eventSchedule[0] : null)) : null,
        ];

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
