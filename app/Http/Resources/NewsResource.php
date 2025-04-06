<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    protected $statusCode;

    public function __construct($resource, $statusCode = 200)
    {
        parent::__construct($resource);
        $this->statusCode = $statusCode;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'news_id' => $this->news_id,
            'news_pid' => $this->news_pid,
            'news_title' => $this->news_title,
            'news_content' => $this->news_content,
            'publish_date' => $this->publish_date,
            'effectivefrom' => $this->effectivefrom,
            'effectiveto' => $this->effectiveto,
            'news_author' => $this->news_author,
            'attached_url' => $this->attached_url,
            'ud_serialno' => $this->ud_serialno,
            'remarks' => $this->remarks,
            'pid_currdate' => $this->pid_currdate,
            'pid_prefix' => $this->pid_prefix,
            'cre_date' => $this->cre_date,
            'cre_by' => $this->cre_by,
            'upd_date' => $this->upd_date,
            'upd_by' => $this->upd_by,
            'active_status' => $this->active_status,
            'unit_no' => $this->unit_no,
            'attachments' => $this->attachments->map(function ($attachment) {
                return [
                    'attached_id' => $attachment->attached_id,
                    'attached_pid' => $attachment->attached_pid,
                    'ref_object_name' => $attachment->ref_object_name,
                    'ref_object_code' => $attachment->ref_object_code,
                    'ref_pid' => $attachment->ref_pid,
                    'file_type' => $attachment->file_type,
                    'file_url' => asset('/public/'.$attachment->file_url),
                    'file_extantion' => $attachment->file_extantion,
                    'remarks' => $attachment->remarks,
                    'pid_currdate' => $attachment->pid_currdate,
                    'pid_prefix' => $attachment->pid_prefix,
                    'cre_date' => $attachment->cre_date,
                    'cre_by' => $attachment->cre_by,
                    'upd_date' => $attachment->upd_date,
                    'upd_by' => $attachment->upd_by,
                    'active_status' => $attachment->active_status,
                    'unit_no' => $attachment->unit_no,
                ];
            }),
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
                'status' => true,
                'http_status' => $this->statusCode,
            ],
        ];
    }
}
