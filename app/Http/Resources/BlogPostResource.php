<?php

namespace App\Http\Resources;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
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
        $total_comment = BlogPost::with(['comments' => function ($query) {
            $query->where('parent_comment_pid', null)->where('active_status', 1);
        }])->where('bpost_pid', $this->bpost_pid)
            ->where('active_status', 1)->where('approve_flag', 'Y')->get()[0]->comments->count();

        $array = [
            'bpost_id'          => $this->bpost_id,
            'bpost_pid'         => $this->bpost_pid,
            'category_pid'      => $this->category_pid,
            'user_pid'          => $this->user_pid,
            'title'             => $this->title,
            'description'       => $this->description,
            'post_content'      => $this->post_content,
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
            'banner_name'       => !empty($this->documents->where('ref_object_name', 'blog_banner')->first()->ref_object_name) ? $this->documents->where('ref_object_name', 'blog_banner')->first()->ref_object_name : null,
            'banner_file_url'   => !empty($this->documents->where('ref_object_name', 'blog_banner')->first()->file_url) ? asset('/public/' . $this->documents->where('ref_object_name', 'blog_banner')->first()->file_url) : null,
            'thumbnail_name'    => !empty($this->documents->where('ref_object_name', 'blog_thumbnail')->first()->ref_object_name) ? $this->documents->where('ref_object_name', 'blog_thumbnail')->first()->ref_object_name : null,
            'thumbnail_file_url'=> !empty($this->documents->where('ref_object_name', 'blog_thumbnail')->first()->file_url) ? asset('/public/' . $this->documents->where('ref_object_name', 'blog_thumbnail')->first()->file_url) : null,
            'total_comments'    => $total_comment,
            'comments'          => $this->comments
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
