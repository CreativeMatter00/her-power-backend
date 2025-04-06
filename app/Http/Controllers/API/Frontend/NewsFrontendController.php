<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\FrontendNewsResource;
use App\Http\Resources\FrontendResourceTitleId;
use App\Models\News;


class NewsFrontendController extends Controller
{

    public function getNewsData()
    {
        $newsList = News::with('attachments')->select('news_pid', 'news_title','publish_date')->where('active_status', 1)->orderBy('news_id', 'desc')->take(15)->get();
        if ($newsList) {
            return new FrontendResourceTitleId($newsList);
        } else {
            return (new ErrorResource("Data not Found", 404))->response()->setStatusCode(404);
        }
    }


    public function getNewsByPId($pid)
    {
        $getNewsByid = News::with('attachments')->where("news_pid", $pid)->where('active_status', 1)->first();
        if ($getNewsByid) {
            return new FrontendNewsResource($getNewsByid);
        } else {
            return (new ErrorResource("Data not Found", 404))->response()->setStatusCode(404);
        }
    }
}
