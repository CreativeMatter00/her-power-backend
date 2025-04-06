<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceLibraryVideoResource extends JsonResource
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
        $array = [
            'post_id'           => $this->post_id,
            'post_pid'          => $this->post_pid,
            'user_pid'          => $this->user_pid,
            'title'             => $this->title,
            'description'       => $this->description,
            'post_content'      => $this->post_content,
            'post_type'         => $this->post_type,
            'post_tag'          => $this->post_tag,
            'file_path'         => $this->file_path,
            'publicationdate'   => $this->publicationdate,
            'resourse_marks'    => $this->resourse_marks,
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
            'thumbnail_url'      => !empty($this->documents->where('ref_object_name', 'resource_video_thumbnail')->first()->file_url) ? asset('/public/' . $this->documents->where('ref_object_name', 'resource_video_thumbnail')->first()->file_url) : null,
            'video_url'         => !empty($this->documents->where('ref_object_name', 'resource_video')->first()->file_url) ? asset('/public/' . $this->documents->where('ref_object_name', 'resource_video')->first()->file_url) : null,
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
