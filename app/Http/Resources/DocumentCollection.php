<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentCollection extends JsonResource
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
            'current_page' => $this->resource->currentPage(),
            'data' => $this->resource->map(function ($doc) {
                return [
                    'post_id' => $doc->post_id,
                    'post_pid' => $doc->post_pid,
                    'user_pid' => $doc->user_pid,
                    'title' => $doc->title,
                    'description' => $doc->description,
                    'post_content' => $doc->post_content,
                    'post_type' => $doc->post_type,
                    'post_tag' => $doc->post_tag,
                    'file_path' => $doc->file_path,
                    'ud_serialno' => $doc->ud_serialno,
                    'remarks' => $doc->remarks,
                    'pid_currdate' => $doc->pid_currdate,
                    'pid_prefix' => $doc->pid_prefix,
                    'cre_date' => $doc->cre_date,
                    'cre_by' => $doc->cre_by,
                    'upd_date' => $doc->upd_date,
                    'upd_by' => $doc->upd_by,
                    'active_status' => $doc->active_status,
                    'unit_no' => $doc->unit_no,
                    'documents' => $doc->documents->isNotEmpty() ?
                        $doc->documents->map(function ($document) {
                            return [
                                'ref_object_name' => $document->ref_object_name,
                                'file_url' => asset('public/' . $document->file_url),
                            ];
                        }) : null,
                ];
            })->filter(),
            'per_page' => $this->resource->perPage(),
            'total' => $this->resource->total(),
            'last_page' => $this->resource->lastPage(),
            'next_page_url' => $this->resource->nextPageUrl(),
            'prev_page_url' => $this->resource->previousPageUrl(),
            'has_more_pages' => $this->resource->hasMorePages(),
            'is_first_page' => $this->resource->onFirstPage(),
            'meta' => [
                'message' => $this->message,
                'status' => true,
                'http_status' => $this->statusCode,
            ],
        ];
    }
}
