<?php

namespace App\Http\Controllers\API\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\ReviewRatingResource;
use App\Models\Attachment;
use App\Models\EnterpRating;
use App\Models\ReviewRating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ReviewRatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'rating_marks' => 'required|numeric|between:1,5',

            'product_pid' => 'required',
            'customer_pid' => 'required',
            'enterpenure_pid' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                $directory = 'attachments/review/' . now()->format('Ymd') . '/';
                $createDirectory = public_path('attachments/review/' . now()->format('Ymd') . '/');

                DB::beginTransaction();
                $insertReviewRating = new ReviewRating();
                $insertReviewRating->product_pid = $request->product_pid;
                $insertReviewRating->customer_pid = $request->customer_pid;
                $insertReviewRating->enterpenure_pid = $request->enterpenure_pid;
                $insertReviewRating->rating_date = Carbon::now();
                $insertReviewRating->rating_marks = $request->rating_marks;
                $insertReviewRating->review_content = $request->review_content;
                // $insertReviewRating->cre_by = Auth::user()->user_pid;
                $insertReviewRating->save();

                $rating_pid = ReviewRating::where('rating_id', $insertReviewRating->rating_id)->pluck('rating_pid')->first();
                $insertReviewRating->rating_pid = $rating_pid;

                // Handle file uploads
                if ($request->hasFile('attachments')) {

                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                    }
                    $fileSlug = Str::slug($request->news_title);
                    foreach ($request->file('attachments') as $file) {
                        $extension = $file->getClientOriginalExtension();
                        $fileName = uniqid() . '-' . $fileSlug . '.' . $extension;
                        $file->move(public_path($directory), $fileName);
                        $filePath = $directory . $fileName;
                        $attachment = new Attachment();
                        $attachment->ref_object_name = "review";
                        $attachment->ref_pid = $rating_pid;
                        $attachment->file_extantion  = $extension;
                        $attachment->file_url = $filePath;
                        // $attachment->cre_by = Auth::user()->user_pid;
                        $attachment->save();
                    }
                }
                DB::commit();

                return (new ReviewRatingResource($insertReviewRating, 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    public function sellerReviewRating(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'rating_marks' => 'required|numeric|between:1,5',
            'enterpenure_pid' => 'required',
        ]);
        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();
                $insertReviewRating = new EnterpRating();
                $insertReviewRating->enterpenure_pid = $request->enterpenure_pid;
                $insertReviewRating->rating_marks = $request->rating_marks;
                $insertReviewRating->review_content = $request->review_content;
                $insertReviewRating->rating_date = Carbon::now();
                $insertReviewRating->save();
                $rating_pid = EnterpRating::where('rating_id', $insertReviewRating->rating_id)->pluck('rating_pid')->first();
                $insertReviewRating->rating_pid = $rating_pid;
                DB::commit();
                return (new ReviewRatingResource($insertReviewRating, 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reviewRatings = DB::select("SELECT
                                    a.rating_pid,
                                    a.rating_marks,
                                    a.rating_date,
                                    a.review_content,
                                    b.fname,
                                    b.lname
                                FROM
                                    ec_rating a
                                    LEFT JOIN ec_customer b on a.customer_pid = b.customer_pid
                                where
                                a.product_pid = ? ORDER BY a.rating_id DESC ", [$id]);
          
          $baseURl = asset('/public');
          foreach ($reviewRatings as $reviewRating) {
            $attachments = Attachment::select(DB::raw("CONCAT('$baseURl/', file_url) as full_file_url"))
                            ->where('ref_pid', $reviewRating->rating_pid)
                            ->get();
            $reviewRating->attachments = $attachments->toArray();
        }

        return new ApiCommonResponseResource($reviewRatings, 'data fetched', 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
