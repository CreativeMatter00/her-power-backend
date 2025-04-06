<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class JobCollection extends ResourceCollection
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
            'jobs' => $this->collection->map(function ($job) {
                return [
                    'jobpost_pid'              => $job->jobpost_pid,
                    'jobtitle'                 => $job->jobtitle,
                    'workplace_type'           => $job->workplace_type,
                    'job_location'             => $job->job_location,
                    'job_type'                 => $job->job_type,
                    'provider_name'            => $job->provider_name,
                    'jobdescription'           => $job->jobdescription,
                    'jobprovider_pid'          => $job->jobprovider_pid,
                    'company_type'             => $job->company_type,
                    'file_url'                 => $job->file_url
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
