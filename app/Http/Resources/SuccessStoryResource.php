<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuccessStoryResource extends JsonResource
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
        $thumbnail = $this->documents->where('ref_object_name', 'story_thumbnail')->where('ref_pid', $this->story_pid)->pluck('file_url')->first() ? 
                    asset('public/'.$this->documents->where('ref_object_name', 'story_thumbnail')->where('ref_pid', $this->story_pid)->pluck('file_url')->first()) : null;
        $video = $this->documents->where('ref_object_name', 'story_video')->where('ref_pid', $this->story_pid)->pluck('file_url')->first() ? asset('public/'. $this->documents->where('ref_object_name', 'story_video')->where('ref_pid', $this->story_pid)->pluck('file_url')->first()) : null;
        $array = [
            'story_id'          => $this->story_id,
            'story_pid'         => $this->story_pid,
            'user_pid'          => $this->user_pid,
            'title'             => $this->title,
            'file_path'         => $this->file_path,
            'ud_serialno'       => $this->ud_serialno,
            'remarks'           => $this->remarks,
            'pid_currdate'      => $this->pid_currdate,
            'pid_prefix'        => $this->pid_prefix,
            'cre_date'          => $this->cre_date,
            'cre_by'            => $this->cre_by,
            'upd_date'          => $this->upd_date,
            'upd_by'            => $this->upd_by,
            'active_status'     => $this->active_status,
            'unit_no'           => $this->unit_no,
            'thumbnail_url'     => $thumbnail,
            'video_url'         => $video,
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
