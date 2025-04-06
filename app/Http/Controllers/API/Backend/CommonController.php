<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Exception;


class CommonController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        try {
            $deleteAttachment = Attachment::where('attached_pid', $id)->first();

            $filePath = public_path($deleteAttachment->file_url);
            echo $deleteAttachment->file_url;


            var_dump(file_exists($filePath));
            exit;
            
            if (!empty($deleteAttachment)) {
            }
        } catch (Exception $e) {
            return (new ErrorResource('Oops! Something went wrong, Please try again.', 501))->response()->setStatusCode(501);
        }
    }
}
