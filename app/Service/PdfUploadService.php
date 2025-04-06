<?php

namespace App\Service;

use App\Models\Document;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PdfUploadService
{
    public static function pdfUpload(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
    {
        try {

            $createDirectory = public_path($file_path);
            if (!File::exists($createDirectory)) {
                File::makeDirectory($createDirectory, 0777, true, true);
                File::chmod($createDirectory, 0777);
            }
            if ($request->hasFile($field_name)) {
                for ($i = 0; $i < count($request->document); $i++) {
                    $fileSlug = Str::slug($name)[$i];
                    $file = $request->file($field_name)[$i];
                    $extension = $file->getClientOriginalExtension();
                    $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                    $file->move(public_path($file_path), $fileName);
                    $filePath = $file_path . $fileName;
                    $attachment = new Document();
                    $attachment->ref_object_name = $ref_object_name.' Part-'.$i+1;
                    $attachment->ref_pid = $ref_pid;
                    $attachment->file_type  = $extension;
                    $attachment->file_url = $filePath;
                    $attachment->save();
                }
            }

            return 200;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
