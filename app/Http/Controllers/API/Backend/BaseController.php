<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
/**
 * @OA\Info(
 *     title="Her Power API Service",
 *     version="1.0.0"
 * )
 * @author ATI Limited
 * Developed By 
 * Khan Rafaat Abtahe
 * <<rafaat@atilimited.net>> 
 */

 
class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 501)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response,  $code);
    }
}
