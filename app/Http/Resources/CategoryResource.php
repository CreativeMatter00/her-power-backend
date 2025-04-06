<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'category_pid' => $this->category_pid,
            'category_name' => $this->category_name,
            'short_name' => $this->short_name,
            'category_desc' => $this->category_desc,
            'parent_category_pid' => $this->parent_category_pid,
            'ud_serialno' => $this->ud_serialno, // Ensure this line is present
            'remarks' => $this->remarks,
            'active_status' => $this->active_status,
            'pid_currdate' => $this->pid_currdate,
            'pid_prefix' => $this->pid_prefix,
            'cre_date' => $this->cre_date,
            'cre_by' => $this->cre_by,
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
