<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FrontendNewsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstFileUrl = $this->attachments->first() ? asset($this->attachments->first()->file_url) : null;
        return [
            'news_pid' => $this->news_pid,
            'news_title' => $this->news_title,
            'publish_date' => $this->publish_date,
            'file_url' =>  asset('/public/'.$firstFileUrl),
        ];
    }
}
