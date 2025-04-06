<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NewsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->transform(function ($news) {
            if($news->attachments){
                $attachments = $news->attachments->map(function ($attachment) {
                    return [
                        'attached_id' => $attachment->attached_id,
                        'attached_pid' => $attachment->attached_pid,
                        'ref_object_name' => $attachment->ref_object_name,
                        'ref_object_code' => $attachment->ref_object_code,
                        'ref_pid' => $attachment->ref_pid,
                        'file_type' => $attachment->file_type,
                        'file_url' =>  asset('/public/'.$attachment->file_url),
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
                });

            }
    
            return [
                'news_id' => $news->news_id,
                'news_pid' => $news->news_pid,
                'news_title' => $news->news_title,
                'news_content' => $news->news_content,
                'publish_date' => $news->publish_date,
                'effectivefrom' => $news->effectivefrom,
                'effectiveto' => $news->effectiveto,
                'news_author' => $news->news_author,
                'attached_url' => $news->attached_url,
                'ud_serialno' => $news->ud_serialno,
                'remarks' => $news->remarks,
                'pid_currdate' => $news->pid_currdate,
                'pid_prefix' => $news->pid_prefix,
                'cre_date' => $news->cre_date,
                'cre_by' => $news->cre_by,
                'upd_date' => $news->upd_date,
                'upd_by' => $news->upd_by,
                'active_status' => $news->active_status,
                'unit_no' => $news->unit_no,
                'attachments' => $attachments,
            ];
        })->toArray();
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
                'total' => $this->resource->total(),
                'count' => $this->resource->count(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'total_pages' => $this->resource->lastPage(),
            ],
            'links' => [
                'self' => $this->resource->url($this->resource->currentPage()),
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }
}
