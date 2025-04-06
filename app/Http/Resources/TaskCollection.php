<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
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
            'tasks' => $this->collection->map(function ($task) {
                return [
                    'taskpost_pid'                  => $task->taskpost_pid,
                    'jobtitle'                 => $task->jobtitle,
                    'jobdescription'                 => $task->jobdescription,
                    'duration'                => $task->duration
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
