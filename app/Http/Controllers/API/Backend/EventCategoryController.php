<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\EventCategoryResource;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Models\Attachment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Service\ImageUploadService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = EventCategory::with('attachment')->select('category_pid', 'category_name', 'category_desc')->where('active_status', 1)->get();
        try {
            foreach ($data as $item) {
                $item->attachment->each(function ($attachment) use (&$category_file_url, &$thumbnail_file_url) {
                    $category_file_url = $attachment->file_url;
                });

                $item->category_file_url = isset($category_file_url) ? asset('/public/' . $category_file_url) : null;
                unset($item->attachment);
            }
            return (new ApiCommonResponseResource($data, "Events fetched successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
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
    public function store(Request $request, ImageUploadService $imageUploadService)
    {
        // validation part
        $validator = Validator::make($request->all(), [
            'category_name'      => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle venue information.
                $insert = new EventCategory();
                $insert->category_name       = $request->category_name;
                $insert->category_desc       = $request->category_desc;
                $insert->parent_category_pid = $request->parent_category_pid;
                $insert->remarks             = $request->remarks;
                // $insert->short_name          = $request->short_name;
                // $insert->ud_serialno         = $request->ud_serialno;
                // $insert->pid_currdate        = $request->pid_currdate;
                // $insert->pid_prefix          = $request->pid_prefix;
                // $insert->cre_date            = $request->cre_date;
                // $insert->cre_by              = Auth::user()->id;
                // $insert->active_status       = $request->active_status ?? 1;
                // $insert->unit_no             = $request->unit_no ?? 1;
                $insert->save();


                $category_pid = EventCategory::where('category_id', $insert->category_id)->pluck('category_pid')->first();

                // img
                $ew_category_directory = 'attachments/event_category/' . now()->format('Ymd') . '/';
                $storeBanImage = $imageUploadService->uploadSingleImage($request, $request->category_name, $ew_category_directory, $category_pid, "event_category");
                if ($storeBanImage != 200) {
                    return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                    abort(500, 'Somthing wrong with Category Image Upload');
                }

                $category_pid = EventCategory::with('attachment')->where('category_id', $insert->category_id)->first();

                DB::commit();
                return (new EventCategoryResource($category_pid, "Event Category created successfully", 201))->response()->setStatusCode(201);
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
        $data = EventCategory::with('attachment')->select('category_pid', 'category_name', 'category_desc')
        ->where('category_pid', $id)
        ->where('active_status', 1)
        ->get();
        try {
            foreach ($data as $item) {
                $item->attachment->each(function ($attachment) use (&$category_file_url, &$thumbnail_file_url) {
                    $category_file_url = $attachment->file_url;
                });

                $item->category_file_url = isset($category_file_url) ? asset('/public/' . $category_file_url) : null;
                unset($item->attachment);
            }
            return (new ApiCommonResponseResource($data, "Events fetched successfully", 200))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
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
    public function update(Request $request, string $id, ImageUploadService $imageUploadService)
    {
        // validation part
        $validator = Validator::make($request->all(), [
            'category_name'      => 'required',
        ]);

        if ($validator->fails()) {
            return (new ErrorResource($validator->errors(), 400))->response()->setStatusCode(400);
        } else {
            try {

                DB::beginTransaction();

                // handle venue information.
                $update = EventCategory::where('category_pid', $id)->first();
                if ($request->category_name) {
                    $update->category_name = $request->category_name;
                }

                if ($request->category_desc) {
                    $update->category_desc = $request->category_desc;
                }

                if ($request->parent_category_pid) {
                    $update->parent_category_pid = $request->parent_category_pid;
                }

                if ($request->remarks) {
                    $update->remarks = $request->remarks;
                }

                // $update->short_name          = $request->short_name;
                // $update->ud_serialno         = $request->ud_serialno;
                // $update->pid_currdate        = $request->pid_currdate;
                // $update->pid_prefix          = $request->pid_prefix;
                // $update->cre_date            = $request->cre_date;
                // $update->cre_by              = Auth::user()->id;
                // $update->active_status       = $request->active_status ?? 1;
                // $update->unit_no             = $request->unit_no ?? 1;
                $update->update();

                $category_pid = EventCategory::where('category_id', $update->category_id)->pluck('category_pid')->first();

                // img
                if ($request->hasFile('attachments')) {
                    // delete previous img
                    $previous_img = Attachment::where('ref_pid', $category_pid)->first();
                    $previous_img ? $previous_img->delete() : null;

                    // insert new img
                    $ew_category_directory = 'attachments/event_category/' . now()->format('Ymd') . '/';
                    $storeBanImage = $imageUploadService->uploadSingleImage($request, $request->category_name, $ew_category_directory, $category_pid, "event_category");
                    if ($storeBanImage != 200) {
                        return (new ErrorResource($storeBanImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Category Image Upload');
                    }
                }

                $category_pid = EventCategory::with('attachment')->where('category_id', $update->category_id)->first();

                DB::commit();
                return (new EventCategoryResource($category_pid, "Event Category created successfully", 201))->response()->setStatusCode(201);
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function event_by_category(string $id)
    {
        $event = Event::with('attachments', 'venues', 'tricketInfo', 'notification', 'eventSchedule')
        ->where('category_pid', $id)
        ->where('active_status', 1)
        ->paginate(10);

        if (empty($event)) {
            return (new ErrorResource("No Event Found !!", 404))->response()->setStatusCode(404);
        } else {
            return (new EventCollection($event, "Event fetch successfully", 200))->response()->setStatusCode(200);
        }
    }
}
