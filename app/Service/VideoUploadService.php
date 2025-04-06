<?php

namespace App\Service;

use App\Models\Document;
use App\Models\StoriesAttachment;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class VideoUploadService
{
    /**
     * Document Video upload servises
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 18/01/2025
     * @global function
     */
    public static function videoUpload(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
    {
        try {

            $createDirectory = public_path($file_path);
            if (!File::exists($createDirectory)) {
                File::makeDirectory($createDirectory, 0777, true, true);
                File::chmod($createDirectory, 0777);
            }
            if ($request->hasFile($field_name)) {
                $fileSlug = Str::slug($name);
                $file = $request->file($field_name);
                $extension = $file->getClientOriginalExtension();
                $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                $file->move(public_path($file_path), $fileName);
                $filePath = $file_path . $fileName;
                $attachment = new Document();
                $attachment->ref_object_name = $ref_object_name;
                $attachment->ref_pid = $ref_pid;
                $attachment->file_extantion  = $extension;
                $attachment->file_url = $filePath;
                $attachment->save();
            }

            return 200;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Story Video upload servises
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 18/01/2025
     * @global function
     */
    public static function storyVideoUpload(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
    {
        try {

            $createDirectory = public_path($file_path);
            if (!File::exists($createDirectory)) {
                File::makeDirectory($createDirectory, 0777, true, true);
                File::chmod($createDirectory, 0777);
            }
            if ($request->hasFile($field_name)) {
                $fileSlug = Str::slug($name);
                $file = $request->file($field_name);
                $extension = $file->getClientOriginalExtension();
                $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                $file->move(public_path($file_path), $fileName);
                $filePath = $file_path . $fileName;
                $attachment = new StoriesAttachment();
                $attachment->ref_object_name = $ref_object_name;
                $attachment->ref_pid = $ref_pid;
                $attachment->file_extantion  = $extension;
                $attachment->file_url = $filePath;
                $attachment->save();
            }

            return 200;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
