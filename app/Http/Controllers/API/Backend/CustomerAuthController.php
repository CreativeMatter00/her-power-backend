<?php

namespace App\Http\Controllers\API\Backend;


use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Resources\ErrorResource;
use App\Service\ImageUploadService;
use Illuminate\Support\Facades\File;

class CustomerAuthController extends BaseController
{
    public function customerRegistration(Request $request, ImageUploadService $imageUploadService)
    {
        $request->merge(['name' => $request->input('username')]);
        $validator = Validator::make($request->all(), [
            'fname' => 'required',
            'lname' => 'required',
            'mobile_no' => 'required',
            'name' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',

        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        } else {
            try {

                DB::beginTransaction();
                $input = $request->all();
                $input['password'] = Hash::make($input['password']);
                $user = User::create($input);
                $success['token'] = $user->createToken('ATI-LIMITED_HER_POWER')->accessToken;
                $success['name'] =  $user->name;
                $user_pid = User::select('user_pid')->where('id', $user->id)->first();
                $userToCustomer =  new Customer();
                $userToCustomer->user_pid =  $user_pid->user_pid;
                $userToCustomer->fname = $request->fname;
                $userToCustomer->lname = $request->lname;
                $userToCustomer->mobile_no = $request->mobile_no;
                $userToCustomer->house_number = $request->house_number;
                $userToCustomer->area_name = $request->area_name;
                $userToCustomer->city_name = $request->city_name;
                $userToCustomer->zip_postal_code = $request->zip_postal_code;
                $userToCustomer->save();
                if ($request->hasFile('attachments')) {

                    $directory = 'attachments/profile_photo/' . now()->format('Ymd') . '/';
                    $createDirectory = public_path('attachments/profile_photo/' . now()->format('Ymd') . '/');
                    if (!File::exists($createDirectory)) {
                        File::makeDirectory($createDirectory, 0777, true, true);
                    }
                    $storeProfileImage = $imageUploadService->uploadSingleImage($request, $request->fname, $directory, $user_pid->user_pid, "profile Photo");

                    if ($storeProfileImage != 200) {
                        return (new ErrorResource($storeProfileImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Profile Image Upload');
                    }
                }
                DB::commit();
                return $this->sendResponse($success, 'Customer registred successfully.');
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oops! Customer registration failed, Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }
}
