<?php

namespace App\Service;

use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use Exception;

class AttachmentService
{
    /**
     * Use Only Banner and Thumbnail images array to object conversion
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     * @param  collection  $data
     * @param  string  $subject
     * @return collection
     */
    public static function returnWithBannerAndThumbnail($data, string $subject)
    {
        try {
            foreach ($data as $item) {
                $item->documents->each(function ($attachment) use (&$banner_file_url, &$thumbnail_file_url) {
                    $banner_file_url = $attachment->file_url;
                    $thumbnail_file_url = $attachment->file_url;
                });

                $item->banner_file_url = isset($banner_file_url) ? asset('/public/' . $banner_file_url) : null;
                $item->thumbnail_file_url = isset($thumbnail_file_url) ? asset('/public/' . $thumbnail_file_url) : null;
                unset($item->documents);
            }
            return (new ApiCommonResponseResource($data, $subject . " fetch successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Use Only Resource Library Thumbnail and Video array to object conversion
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 18/01/2025
     * @param  collection  $data
     * @param  string  $subject
     * @return collection
     */
    public static function returnWithThumbnailAndVideo($data, string $subject)
    {
        try {
            foreach ($data as $item) {
                $item->documents->each(function ($attachment) use (&$thumbnail, &$video, $item) {
                    $thumbnail = $attachment->where('ref_pid', $item->post_pid)->where('ref_object_name', 'resource_video_thumbnail')->pluck('file_url')->first();
                    $video = $attachment->where('ref_pid', $item->post_pid)->where('ref_object_name', 'resource_video')->pluck('file_url')->first();
                });

                $item->thumbnail_url = isset($thumbnail) ? asset('/public/' . $thumbnail) : null;
                $item->video_url = isset($video) ? asset('/public/' . $video) : null;
                unset($item->documents);
            }
            return (new ApiCommonResponseResource($data, $subject . " fetch successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Use Only Success Stories Thumbnail and Video array to object conversion
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 19/02/2025
     * @param  collection  $data
     * @param  string  $subject
     * @return collection
     */
    public static function returnStoryWithThumbnailAndVideo($data, string $subject)
    {
        try {
            foreach ($data as $item) {
                $item->documents->each(function ($attachment) use (&$thumbnail, &$video, $item) {
                    $thumbnail = $attachment->where('ref_pid', $item->story_pid)->where('ref_object_name', 'story_thumbnail')->pluck('file_url')->first();
                    $video = $attachment->where('ref_pid', $item->story_pid)->where('ref_object_name', 'story_video')->pluck('file_url')->first();
                });

                $item->thumbnail_url = isset($thumbnail) ? asset('/public/' . $thumbnail) : null;
                $item->video_url = isset($video) ? asset('/public/' . $video) : null;
                unset($item->documents);
            }
            return (new ApiCommonResponseResource($data, $subject . " fetch successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }

    /**
     * Use Only Challenge Banner and Thumbnail images array to object conversion
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 22/02/2025
     * @param  collection  $data
     * @param  string  $subject
     * @return collection
     */
    public static function returnWithChallengeBannerAndThumbnail($data, string $subject)
    {
        try {
            foreach ($data as $item) {
                $item->documents->each(function ($attachment) use (&$banner_file_url, &$thumbnail_file_url, $item) {
                    $banner_file_url = $attachment->where('ref_pid', $item->cpost_pid)->where('ref_object_name', 'challenge_post_banner')->pluck('file_url')->first();
                    $thumbnail_file_url = $attachment->where('ref_pid', $item->cpost_pid)->where('ref_object_name', 'challenge_post_thumbnail')->pluck('file_url')->first();
                });

                $item->banner_file_url = isset($banner_file_url) ? asset('/public/' . $banner_file_url) : null;
                $item->thumbnail_file_url = isset($thumbnail_file_url) ? asset('/public/' . $thumbnail_file_url) : null;
                unset($item->documents);
            }
            return (new ApiCommonResponseResource($data, $subject . " fetch successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
