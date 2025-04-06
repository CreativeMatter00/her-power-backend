<?php

namespace App\Service;

use App\Models\Attachment;
use App\Models\ChallengePostAttachment;
use App\Models\Document;
use App\Models\StoriesAttachment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ImageUploadService
{

    public  function storeMultipleImage($product_pid, $key, $image)
    {
        try {


            $file = $image;
            $extension = $file->getClientOriginalExtension();
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $fileName = uniqid() . '.' . $extension;

            if ($key == 0) {

                // for main
                $mainPath = public_path('attachments/product/450x660/' . now()->format('Ymd') . '/');  // Path For main photo
                if (!file_exists($mainPath)) {
                    mkdir($mainPath, 0777, true);
                    File::chmod($mainPath, 0777);
                }
                $mainPathWithfile = $mainPath . $fileName;
                // $image->resize(450, 660);
                $image->resizeDown(450, 660);
                $image->save($mainPathWithfile);


                // for thumbnail
                $thumbnailDirectory = public_path('attachments/product/244x244/' . now()->format('Ymd') . '/');  // Path For thumbnail
                if (!file_exists($thumbnailDirectory)) {
                    mkdir($thumbnailDirectory, 0777, true);

                    File::chmod($mainPath, 0777);
                }
                $thumbnailPath = $thumbnailDirectory . $fileName;
                // $image->resize(244, 244);
                $image->resizeDown(244, 244);
                $image->save($thumbnailPath);

                // for cart
                $cartPath = public_path('attachments/product/60x75/' . now()->format('Ymd') . '/');  // Path For cart photo
                if (!file_exists($cartPath)) {
                    mkdir($cartPath, 0777, true);
                    File::chmod($mainPath, 0777);
                }
                $cartPathWithFile = $cartPath . $fileName;
                // $image->resize(60, 75);
                $image->resizeDown(60, 75);
                $image->save($cartPathWithFile);

                // for wish list
                $wishPath = public_path('attachments/product/157x160/' . now()->format('Ymd') . '/');  // Path For wish list photo
                if (!file_exists($wishPath)) {
                    mkdir($wishPath, 0777, true);
                    File::chmod($mainPath, 0777);
                }
                $wishPathWithFile = $wishPath . $fileName;
                // $image->resize(157, 160);
                $image->resizeDown(157, 160);
                $image->save($wishPathWithFile);

                $attachment = new Attachment();
                $attachment->ref_object_name = "product";
                $attachment->ref_pid = $product_pid;
                $attachment->file_extantion  = $extension;
                $attachment->file_url =  'attachments/product/450x660/' . now()->format('Ymd') . '/' . $fileName;
                $attachment->img_thumb =  'attachments/product/244x244/' . now()->format('Ymd') . '/' . $fileName;
                $attachment->img_cart = 'attachments/product/60x75/' . now()->format('Ymd') . '/' . $fileName;
                $attachment->img_wishlist =  'attachments/product/157x160/' . now()->format('Ymd') . '/' . $fileName;
                // $attachment->cre_by = Auth::user()->user_pid;
                $attachment->save();
            } else {

                // for main
                $mainPath = public_path('attachments/product/450x660/' . now()->format('Ymd') . '/');  // Path For thumbnail
                if (!file_exists($mainPath)) {
                    mkdir($mainPath, 0777, true);
                    File::chmod($mainPath, 0777);
                }
                $mainPathWithfile = $mainPath . $fileName;
                // $image->resize(450, 660);
                $image->resizeDown(450, 660);
                $image->save($mainPathWithfile);

                // for cart
                $cartPath = public_path('attachments/product/60x75/' . now()->format('Ymd') . '/');  // Path For thumbnail
                if (!file_exists($cartPath)) {
                    mkdir($cartPath, 0777, true);
                }
                $cartPathWithFile = $cartPath . $fileName;
                // $image->resize(60, 75);
                $image->resizeDown(60, 75);
                $image->save($cartPathWithFile);

                // for wish list
                $wishPath = public_path('attachments/product/157x160/' . now()->format('Ymd') . '/');  // Path For thumbnail
                if (!file_exists($wishPath)) {
                    mkdir($wishPath, 0777, true);
                }
                $wishPathWithFile = $wishPath . $fileName;
                // $image->resize(157, 160);
                $image->resizeDown(157, 160);
                $image->save($wishPathWithFile);

                $attachment = new Attachment();
                $attachment->ref_object_name = "product";
                $attachment->ref_pid = $product_pid;
                $attachment->file_extantion  = $extension;
                $attachment->file_url =  'attachments/product/450x660/' . now()->format('Ymd') . '/' . $fileName;
                $attachment->img_cart = 'attachments/product/60x75/' . now()->format('Ymd') . '/' . $fileName;
                $attachment->img_wishlist =  'attachments/product/157x160/' . now()->format('Ymd') . '/' . $fileName;
                // $attachment->cre_by = Auth::user()->user_pid;
                $attachment->save();
            }
            return 200;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function uploadSingleImage(object $request, string $name, string $file_path, string $ref_pid, string $ref_object_name)
    {
        try {

            $createDirectory = public_path($file_path);
            if (!File::exists($createDirectory)) {
                File::makeDirectory($createDirectory, 0777, true, true);
                File::chmod($createDirectory, 0777);
            }
            if ($request->hasFile('attachments')) {
                $fileSlug = Str::slug($name);
                $file = $request->file('attachments');
                $extension = $file->getClientOriginalExtension();
                $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                $file->move(public_path($file_path), $fileName);
                $filePath = $file_path . $fileName;
                $attachment = new Attachment();
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

    public function uploadFileAndReturnPath($attachFile, string $name, string $file_path,)
    {
        try {

            $createDirectory = public_path($file_path);
            if (!File::exists($createDirectory)) {
                File::makeDirectory($createDirectory, 0777, true, true);
                File::chmod($createDirectory, 0777);
            }
            $file = $attachFile;
            $fileSlug = Str::slug($name);
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
            $file->move(public_path($file_path), $fileName);
            $filePath = $file_path . $fileName;
            return  $filePath;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function uploadEventBannerImage(object $request, string $name, string $file_path, string $ref_pid, string $ref_object_name)
    {
        try {

            $createDirectory = public_path($file_path);
            if (!File::exists($createDirectory)) {
                File::makeDirectory($createDirectory, 0777, true, true);
                File::chmod($createDirectory, 0777);
            }
            if ($request->hasFile('banner')) {
                $fileSlug = Str::slug($name);
                $file = $request->file('banner');
                $extension = $file->getClientOriginalExtension();
                $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                $file->move(public_path($file_path), $fileName);
                $filePath = $file_path . $fileName;
                $attachment = new Attachment();
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


    public function uploadEventThumnailImage(object $request, string $name, string $file_path, string $ref_pid, string $ref_object_name)
    {
        try {

            $createDirectory = public_path($file_path);
            if (!File::exists($createDirectory)) {
                File::makeDirectory($createDirectory, 0777, true, true);
                File::chmod($createDirectory, 0777);
            }
            if ($request->hasFile('thumbnail')) {
                $fileSlug = Str::slug($name);
                $file = $request->file('thumbnail');
                $extension = $file->getClientOriginalExtension();
                $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                $file->move(public_path($file_path), $fileName);
                $filePath = $file_path . $fileName;
                $attachment = new Attachment();
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
     * @blogPost images upload servises
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public static function bannerImage(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
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
     * @blogPost images upload servises
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public static function thumbnailImage(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
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
     * @api Story images upload servises
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 19/02/2025
     */
    public static function storyThumbnailImage(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
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

    /**
     * @challenges images upload servises
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public static function challengesBannerImage(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
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
                $attachment = new ChallengePostAttachment();
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
     * @Challenge images upload servises
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since 06/01/2025
     */
    public static function challengesThumbnailImage(object $request, string $name, string $file_path, string $field_name, string $ref_pid, string $ref_object_name)
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
                $attachment = new ChallengePostAttachment();
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
