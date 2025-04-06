<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Resources\ApiCommonResponseResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Resources\ErrorResource;
use App\Mail\sendOtpMail;
use App\Models\Attachment;
use App\Models\CourseProvider;
use App\Service\ImageUploadService;
use App\Models\Customer;
use App\Models\EventOrganizer;
use App\Models\JobProvider;
use App\Models\JobSeeker;
use App\Models\Seller;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AuthController extends BaseController
{

    public function registration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',

        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        } else {
            $input = $request->all();
            $input['password'] = Hash::make($input['password']);
            $user = User::create($input);
            $success['token'] = $user->createToken('ATI-LIMITED_HER_POWER')->accessToken;
            $success['name'] =  $user->name;
            return $this->sendResponse($success, 'User registred successfully.');
        }
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        } else {

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();

                if ($user->email_verified) {
                    $success['token'] = $user->createToken('ATI-LIMITED_HER_POWER')->accessToken;
                    $success['name'] =  $user->name;
                    $isCustomer = User::select('ec_customer.customer_pid as customer_pid', 'ec_customer.user_pid as iscus', 'users.user_pid')->where('id', $user->id)->leftJoin('ec_customer', 'users.user_pid', '=', 'ec_customer.user_pid')->first();
                    $isSeller = User::select('ec_enterpenure.enterpenure_pid as enterpenure_pid', 'ec_enterpenure.user_pid as isseller')->where('id', $user->id)->leftJoin('ec_enterpenure', 'users.user_pid', '=', 'ec_enterpenure.user_pid')->first();
                    $isOrganizer = EventOrganizer::where('ref_user_pid', $user->user_pid)->first();
                    $isCourseProvider = CourseProvider::select('providor_pid')->where('ref_user_pid', $user->user_pid)->first();
                    $isStudent = Student::select('student_pid')->where('ref_user_pid', $user->user_pid)->first();
                    $isJobProvider = JobProvider::select('jobprovider_pid')->where('user_pid', $user->user_pid)->first();
                    $isJobSeeker = JobSeeker::select('profile_pid')->where('user_pid', $user->user_pid)->first();
                    $profilePhoto = Attachment::select('file_url')->where('ref_pid', $isCustomer->user_pid)->first();
                    $success['user_pid'] = $isCustomer->user_pid;
                    $success['isCustomer'] = $isCustomer->iscus ? true : false;
                    $success['customer_pid'] = $isCustomer->customer_pid ? $isCustomer->customer_pid : null;
                    $success['isSeller'] = $isSeller->isseller ? true : false;
                    $success['isOrganizer'] = (bool) $isOrganizer;
                    $success['isOrganizer_pid'] = $isOrganizer ? $isOrganizer->org_pid : null;
                    $success['enterpenure_pid'] = $isSeller->enterpenure_pid ? $isSeller->enterpenure_pid : null;
                    if ($isCourseProvider) {
                        $success['isCourseProvider'] = (bool) $isCourseProvider;
                        $success['providor_pid'] = $isCourseProvider->providor_pid;
                    }
                    if ($isStudent) {
                        $success['isStudent'] = (bool) $isStudent;
                        $success['student_pid'] = $isStudent->student_pid;
                    }
                    if ($isJobProvider) {
                        $success['isJobProvider'] = (bool) $isJobProvider;
                        $success['jobprovider_pid'] = $isJobProvider->jobprovider_pid;
                    }
                    if ($isJobSeeker) {
                        $success['isJobSeeker'] = (bool) $isJobSeeker;
                        $success['profile_pid'] = $isJobSeeker->profile_pid;
                    }

                    if ($profilePhoto) {
                        $success['profile_photo'] = asset('/public/' . $profilePhoto->file_url);
                    } else {
                        $success['profile_photo'] = null;
                    }
                    return $this->sendResponse($success, 'User login successfully.');
                } else {
                    return (new ErrorResource("Your email address is not verified.", 403))->response()->setStatusCode(403);
                }
            } else {
                return $this->sendError('Unauthorized.', ['error' => 'Invalid User Name or Password'], 401);
            }
        }
    }

    /**
     * Customer Registration function
     * @author shohag <shohag@atilimited.net>
     * @param  Request  $request
     * @param  ImageUploadService  $imageUploadService
     * @return void
     */
    public function customerRegistration(Request $request, ImageUploadService $imageUploadService)
    {
        // nid or birth reg no check
        if (!request('nid') && !request('birth_reg_no')) {
            return $this->sendResponse(request()->all(), 'Sorry! NID or Birth Registration Numbers are required.');
        }

        // exist or not
        $exist = User::where('email', request('email'))->first();

        if ($exist) {
            $cust_info = Customer::select('nid', 'birth_reg_no')->where('user_pid', $exist->user_pid)->first();

            if (isset($cust_info->nid) || isset($cust_info->birth_reg_no)) {
                // nid or birth varification
                if (request('nid') != $cust_info->nid || request('birth_reg_no') != $cust_info->birth_reg_no) {
                    return $this->sendError('Please, Enter you valid nid or birth registration no!');
                }
            } else {
                return $this->sendError('Sorry, Your email is already exist!', 400)->setStatusCode(400);
            }

            // if exist, check email varificaiton completed or not
            $verified = $exist->email_verified;

            // if not verified
            if (!$verified) {

                // expire check
                if (Carbon::now()->lessThan($exist->otp_expires_at)) {
                    return $this->sendResponse(request('email'), 'Please, check your mail & activate your account!');
                } else {
                    $otp = rand(100000, 999999);
                    $input['otp_expires_at'] = Carbon::now()->addMinutes(5)->toDateTimeString();
                    $exist->update([
                        'email_verification_otp' => $otp,
                        'otp_expires_at' => Carbon::now()->addMinutes(5)->toDateTimeString()
                    ]);
                    $mailData = array('OTP' => $otp);
                    Mail::to(request('email'))->send(new sendOtpMail($mailData));
                    return $this->sendResponse(request('email'), "You’ve received a new OTP! Please check your email and activate your account.");
                }
            }
        }
        return $this->cust_reg($request, $imageUploadService);
    }

    /**
     * Customer Registraion Helper function
     *
     * @param  [object]  $request
     * @param  [class]  $imageUploadService
     * @return void
     */
    public function cust_reg($request, $imageUploadService)
    {
        $request->merge(['name' => $request->input('username')]);

        // return $nid_or_birth;
        $validator = Validator::make($request->all(), [
            'fname'             => 'required',
            'lname'             => 'required',
            'mobile_no'         => ['required', 'min:8', 'regex:/^[\+0-9-]+$/'],
            'name'              => 'required|unique:users,name',
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required',
            'confirm_password'  => 'required|same:password',
            'nid'               => 'min:10|max:17|unique:ec_customer,nid',
            'birth_reg_no'      => 'min:17|max:25|unique:ec_customer,birth_reg_no'
        ]);

        if ($validator->fails()) {
            // Dynamically get all the errors for the fields in the rules
            $msg = collect($validator->errors()->messages())->flatten()->filter()->values()->toArray();
            return $this->sendError($msg, 400)->setStatusCode(400);
        } else {
            try {
                $otp = rand(100000, 999999);
                DB::beginTransaction();
                $input = $request->all();
                $input['password'] = Hash::make($input['password']);
                $input['email_verification_otp'] =  $otp;
                $input['otp_expires_at'] = Carbon::now()->addMinutes(5)->toDateTimeString();
                $user = User::create($input);
                $success['token'] = $user->createToken('ATI-LIMITED_HER_POWER')->accessToken;
                $success['name'] =  $user->name;
                $success['email'] =  $user->email;
                $user_pid = User::select('user_pid')->where('id', $user->id)->first();
                $userToCustomer =  new Customer();
                $userToCustomer->user_pid       =  $user_pid->user_pid;
                $userToCustomer->fname          = $request->fname;
                $userToCustomer->lname          = $request->lname;
                $userToCustomer->full_name      = $request->fname . ' ' . $request->lname;
                $userToCustomer->mobile_no      = $request->mobile_no;
                $userToCustomer->address_line   = $request->address_line;
                $userToCustomer->house_number   = $request->house_number;
                $userToCustomer->area_name      = $request->area_name;
                $userToCustomer->city_name      = $request->city_name;
                $userToCustomer->zip_postal_code = $request->zip_postal_code;
                $userToCustomer->nid            = $request->nid;
                $userToCustomer->birth_reg_no   = $request->birth_reg_no;
                $userToCustomer->save();
                if ($request->hasFile('attachments')) {

                    $directory = 'attachments/profile_photo/' . now()->format('Ymd') . '/';
                    $storeProfileImage = $imageUploadService->uploadSingleImage($request, $request->fname, $directory, $user_pid->user_pid, "profile_photo");
                    if ($storeProfileImage != 200) {
                        return (new ErrorResource($storeProfileImage, 501))->response()->setStatusCode(501);
                        abort(500, 'Somthing wrong with Profile Image Upload');
                    }
                }
                DB::commit();
                $getCustomerPId = Customer::select('customer_pid')->where('user_pid', $user_pid->user_pid)->first()->customer_pid;
                $profile_url = Attachment::where('ref_pid', $user_pid->user_pid)->pluck('file_url')->first() ?? null;
                $userToCustomer->customer_pid = $getCustomerPId;
                $userToCustomer->userloginInfo = $success;
                $userToCustomer->profile_photo = $profile_url ? asset('public/' . $profile_url) : null;
                $mailData = [
                    'OTP' => $otp,
                ];
                Mail::to($request->email)->send(new sendOtpMail($mailData));

                return $this->sendResponse($userToCustomer, 'Customer registered successfully.');
            } catch (Exception $e) {
                DB::rollBack();
                return (new ErrorResource('Oop! Registration failed. Please try again.', 501))->response()->setStatusCode(501);
            }
        }
    }

    /**
     * seller registration function
     * @author shohag <shohag@atilimited.net>
     * @since 29.01.2025
     * @param  Request  $request
     * @param  ImageUploadService  $imageUploadService
     * @return void
     */
    public function sellerRegistration(Request $request, ImageUploadService $imageUploadService)
    {
        $is_exist = User::where('email', trim($request->email))->first();

        if ($is_exist) {

            $verified = $is_exist->email_verified;
            if (!$verified) {

                // expire check
                if (Carbon::now()->lessThan($is_exist->otp_expires_at)) {
                    return $this->sendResponse(request('email'), 'Please, check your mail & activate your account!');
                } else {
                    $otp = rand(100000, 999999);
                    $is_exist->update([
                        'email_verification_otp' => $otp,
                        'otp_expires_at' => Carbon::now()->addMinutes(5)->toDateTimeString()
                    ]);
                    $mailData = array('OTP' => $otp);
                    Mail::to(request('email'))->send(new sendOtpMail($mailData));
                    return $this->sendResponse(request('email'), "You’ve received a new OTP! Please check your email and activate your account.");
                }
            }
            return (new ApiCommonResponseResource($request->all(), "This seller already registered !!", 409))->response()->setStatusCode(409);
        }
        return $this->seller_reg($request, $imageUploadService);
    }

    /**
     * seller reg helper function
     * @since 29.01.2025
     * @param  [type]  $request
     * @param  [type]  $imageUploadService
     * @return void
     */
    public function seller_reg($request, $imageUploadService)
    {
        // validation
        $validator = Validator::make($request->all(), [
            'fname'             => 'required',
            'lname'             => 'required',
            'email'             => 'required|unique:users,email',
            'password'          => 'required',
            'confirm_password'  => 'required|same:password',
            'mobile_no'         => 'required',
        ]);

        if ($validator->fails()) {
            // Dynamically get all the errors for the fields in the rules
            $msg = collect($validator->errors()->messages())->flatten()->filter()->values()->toArray();
            return $this->sendError($msg, 400)->setStatusCode(400);
        } else {

            try {
                DB::beginTransaction();

                $otp = rand(100000, 999999);
                try {
                    // user create
                    $user = User::create([
                        'email'    => $request->email,
                        'password'  => Hash::make($request->password),
                        'name'      => $request->fname,
                        'email_verification_otp'  => $otp,
                        'otp_expires_at'   => Carbon::now()->addMinutes(5)->toDateTimeString()
                    ]);
                } catch (\Throwable $th) {
                    return (new ErrorResource('Oops! Registration failed. Please try again', 404))->response()->setStatusCode(404);
                }

                $user_pid = User::select('user_pid')->where('id', $user->id)->pluck('user_pid')->first();

                $sellerData = new Seller();
                $sellerData->business_name = $request->business_name;
                $sellerData->fname = $request->fname;
                $sellerData->lname = $request->lname;
                $sellerData->user_pid = $user_pid;
                $sellerData->shop_name = $request->shop_name;
                $sellerData->product_category = $request->product_category;
                $sellerData->address_line = $request->address_line;
                $sellerData->area_name = $request->area_name;
                $sellerData->city_name = $request->city_name;
                $sellerData->zip_postal_code = $request->zip_postal_code;
                $sellerData->bank_name = $request->bank_name;
                $sellerData->bank_code = $request->bank_code;
                $sellerData->account_holder_name = $request->account_holder_name;
                $sellerData->account_number = $request->account_number;
                $sellerData->account_type = $request->account_type;
                $sellerData->sell_other_websites = $request->sell_other_websites;
                $sellerData->own_ecommerce_site = $request->own_ecommerce_site;
                $sellerData->product_from = $request->product_from;
                $sellerData->annual_turnover = $request->annual_turnover;
                $sellerData->number_product_sell = $request->number_product_sell;
                // nid front side image upload
                if ($request->hasFile('nidimage_front_side')) {
                    $directory = 'attachments/seller/nid/frontside/' . now()->format('Ymd') . '/';
                    $sellerAttachement = $imageUploadService->uploadFileAndReturnPath($request->nidimage_front_side, $request->shop_name, $directory);
                    if ($sellerAttachement) {
                        $sellerData->nidimage_front_side = $sellerAttachement;
                    } else {
                        return $sellerAttachement;
                    }
                }
                // nid back side image upload
                if ($request->hasFile('nidimage_back_side')) {
                    $directory = 'attachments/seller/nid/backside/' . now()->format('Ymd') . '/';
                    $sellerAttachement = $imageUploadService->uploadFileAndReturnPath($request->nidimage_back_side, $request->shop_name, $directory);
                    if ($sellerAttachement) {
                        $sellerData->nidimage_back_side = $sellerAttachement;
                    } else {
                        return $sellerAttachement;
                    }
                }
                // tin image upload
                if ($request->hasFile('tin_certificate_image')) {
                    $directory = 'attachments/seller/tin/' . now()->format('Ymd') . '/';
                    $sellerAttachement = $imageUploadService->uploadFileAndReturnPath($request->tin_certificate_image, $request->shop_name, $directory);
                    if ($sellerAttachement) {
                        $sellerData->tin_certificate_image = $sellerAttachement;
                    } else {
                        return $sellerAttachement;
                    }
                }
                // sinature image upload
                if ($request->hasFile('signature_image')) {
                    $directory = 'attachments/seller/signature_image/' . now()->format('Ymd') . '/';
                    $sellerAttachement = $imageUploadService->uploadFileAndReturnPath($request->signature_image, $request->shop_name, $directory);
                    if ($sellerAttachement) {
                        $sellerData->signature_image = $sellerAttachement;
                    } else {
                        return $sellerAttachement;
                    }
                }
                // trade licence image upload
                if ($request->hasFile('trade_license_image')) {
                    $directory = 'attachments/seller/trade_license/' . now()->format('Ymd') . '/';
                    $sellerAttachement = $imageUploadService->uploadFileAndReturnPath($request->trade_license_image, $request->shop_name, $directory);
                    if ($sellerAttachement) {
                        $sellerData->trade_license_image = $sellerAttachement;
                    } else {
                        return $sellerAttachement;
                    }
                }
                // vat id image upload
                if ($request->hasFile('vat_id_image')) {
                    $directory = 'attachments/seller/vat_id_img/' . now()->format('Ymd') . '/';
                    $sellerAttachement = $imageUploadService->uploadFileAndReturnPath($request->vat_id_image, $request->shop_name, $directory);
                    if ($sellerAttachement) {
                        $sellerData->vat_id_image = $sellerAttachement;
                    } else {
                        return $sellerAttachement;
                    }
                }
                // vat id image upload
                if ($request->hasFile('tax_id_image')) {
                    $directory = 'attachments/seller/tax_id_img/' . now()->format('Ymd') . '/';
                    $sellerAttachement = $imageUploadService->uploadFileAndReturnPath($request->tax_id_image, $request->shop_name, $directory);
                    if ($sellerAttachement) {
                        $sellerData->tax_id_image = $sellerAttachement;
                    } else {
                        return $sellerAttachement;
                    }
                }
                $sellerData->save();

                $mailData = [
                    'OTP' => $otp,
                ];
                Mail::to($request->email)->send(new sendOtpMail($mailData));
                DB::commit();
                return $this->sendResponse($sellerData, 'Seller registration complete! Welcome to Her Power community!');
            } catch (Exception $e) {
                DB::rollBack();
                return $this->sendError('Oops! Registration failed. Please try again')->setStatusCode(501);
            }
        }
    }

    public function changeCustomerOrSellerProfilePic(Request $request, ImageUploadService $imageUploadService)
    {
        $ref_pid = $request->ref_pid;
        try {
            if ($request->hasFile('attachments')) {
                Attachment::where('ref_pid', $ref_pid)->where('ref_object_name', 'profile_photo')->delete();
                $directory = 'attachments/profile_photo/' . now()->format('Ymd') . '/';
                $storeProfileImage = $imageUploadService->uploadSingleImage($request, $ref_pid, $directory, $ref_pid, "profile_photo");
            }

            if ($storeProfileImage == 200) {
                return new ApiCommonResponseResource((array) $storeProfileImage, "Image Update success", 200);
            }
        } catch (Exception $e) {
            return (new ErrorResource('Oop! Profile photo change failed. Please try again.', 501))->response()->setStatusCode(501);
        }
    }


    public function otpVerify(Request $request)
    {

        $validatedData = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $validatedData['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Sorry! Invalid email or OTP',
                'status' => false,
                'http_status' => 401,
            ], 401)->setStatusCode(401);
        }

        // Check if OTP is correct
        if ($user->email_verification_otp != $validatedData['otp']) {
            return response()->json([
                'message' => 'Oops! Invalid OTP',
                'status' => false,
                'http_status' => 401,
            ], 401)->setStatusCode(401);
        }

        // Check if OTP has expired
        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.',
                'status' => false,
                'http_status' => 401,
            ], 401)->setStatusCode(401);
        }

        $user->email_verified = true;
        $user->email_verified_at = now();
        $user->email_verification_otp = null;
        $user->otp_expires_at = null;
        $user->save();


        return response()->json([
            'message'       => 'Email verified successfully!',
            'status'        => true,
            'http_status'   => 000,
        ])->setStatusCode(200);
    }
}
