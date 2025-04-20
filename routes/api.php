<?php

use App\Http\Controllers\API\Backend\ArticleController;
use App\Http\Controllers\API\Backend\AuthController;
use App\Http\Controllers\API\Backend\BlogPostController;
use App\Http\Controllers\API\Backend\BranchController;
use App\Http\Controllers\API\Backend\CartController;
use App\Http\Controllers\API\Backend\CategoryController;
use App\Http\Controllers\API\Backend\ChallengesController;
use App\Http\Controllers\API\Backend\ChatController;
use App\Http\Controllers\API\Backend\CommonController;
use App\Http\Controllers\API\Backend\CourseCategoryController;
use App\Http\Controllers\API\Backend\CourseController;
use App\Http\Controllers\API\Backend\CourseProviderController;
use App\Http\Controllers\API\Backend\CourseVideoUploadAndProcessingController;
use App\Http\Controllers\API\Backend\DivisionController;
use App\Http\Controllers\API\Backend\DocumentController;
use App\Http\Controllers\API\Backend\EnterpFollowerController;
use App\Http\Controllers\API\Backend\EntrepreneurController;
use App\Http\Controllers\API\Backend\EventCategoryController;
use App\Http\Controllers\API\Backend\EventController;
use App\Http\Controllers\API\Backend\EventOrganizerController;
use App\Http\Controllers\API\Backend\EventParticipantController;
use App\Http\Controllers\API\Backend\EventScheduleController;
use App\Http\Controllers\API\Backend\EventSpeakerController;
use App\Http\Controllers\API\Backend\EventSponsorController;
use App\Http\Controllers\API\Backend\EventVenueController;
use App\Http\Controllers\API\Backend\JobController;
use App\Http\Controllers\API\Backend\MentorController;
use App\Http\Controllers\API\Backend\NewsController;
use App\Http\Controllers\API\Backend\OrderController;
use App\Http\Controllers\API\Backend\ProductController;
use App\Http\Controllers\API\Backend\ProductVariantController;
use App\Http\Controllers\API\Backend\SkillController;
use App\Http\Controllers\API\Backend\StudentController;
use App\Http\Controllers\API\Backend\TaskController;
use App\Http\Controllers\API\Backend\UserController;
use App\Http\Controllers\API\Backend\UserDashboardController;
use App\Http\Controllers\API\Backend\WishlistController;
use App\Http\Controllers\API\Frontend\CategoryFrontendController;
use App\Http\Controllers\API\Frontend\EventController as FrontendEventController;
use App\Http\Controllers\API\Frontend\FrontendCourseController;
use App\Http\Controllers\API\Frontend\FrontendMentorController;
use App\Http\Controllers\API\Frontend\FrontendStudentController;
use App\Http\Controllers\API\Frontend\NewsFrontendController;
use App\Http\Controllers\API\Frontend\NotificationController;
use App\Http\Controllers\API\Frontend\ProductController as FrontendProductController;
use App\Http\Controllers\API\Frontend\ReviewRatingController;
use App\Http\Controllers\API\Backend\ResourceLibraryVideoController;
use App\Http\Controllers\SuccessStoriesController;
use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;

/**
 * Auth Controller Route Start
 * @Author ATI Limited
 * Khan Rafaat Abtahe
 * rafaat@atilimited.net
 */

Route::fallback(function () {
    return response()->json(['status' => 404, 'message' => 'API Path Not Found.'], 404);
});

Route::post('user-registration', [AuthController::class, 'registration']);
Route::post('user-login', [AuthController::class, 'login']);
Route::post('customer-registration', [AuthController::class, 'customerRegistration']);
Route::post('seller-registration', [AuthController::class, 'sellerRegistration']);
Route::post('otp-verify', [AuthController::class, 'otpVerify']);

/**
 * Auth Controller Route end.
 */

/**
 * All Backend Controller Route Start
 * @author ATI Limited
 * Khan Rafaat Abtahe
 * rafaat@atilimited.net
 */

// Route::middleware(['auth:api', EnsureEmailIsVerified::class])->prefix('admin')->group(function () {

Route::middleware([EnsureEmailIsVerified::class])->prefix('admin')->group(function () {
    /**
     * This Routes needs authorization before take any action.
     */

    Route::post('update-profile-photo', [AuthController::class, 'changeCustomerOrSellerProfilePic']);



    Route::apiResource('/category', CategoryController::class);
    Route::apiResource('/news', NewsController::class);
    // Route::POST('news-update/{nid}',[NewsController::class,'update'])->name("news.update");
    Route::apiResource('/common', CommonController::class);
    Route::apiResource('/product', ProductController::class);
    Route::apiResource('/product-variant', ProductVariantController::class);
    Route::POST('/product-variant/{pid}', [ProductVariantController::class, 'store']);
    Route::POST('/product-variant-update/{vid}', [ProductVariantController::class, 'update']);
    Route::POST('/update-product/{id}', [ProductController::class, 'updateProductData']);
    Route::apiResource('/entrepreneur', EntrepreneurController::class);
    Route::POST('/entrepreneur-info-update/{eid}', [EntrepreneurController::class, "updateSellerInfoUpdate"]);
    Route::apiResource('/order', OrderController::class);
    Route::POST('cancel-order', [OrderController::class, 'cancelOrder']);
    Route::POST('cancel-whole-order', [OrderController::class, 'cancelWholeOrder']);
    Route::get('/product-by-id/{enterpenure_id}/{need?}', [ProductController::class, 'index']);
    Route::apiResource('/cart', CartController::class);
    Route::POST('/delete-cart/{cid}/{pid}', [CartController::class, 'deleteCartItem']);
    Route::PUT('/update-cart/{cid}/{pid}', [CartController::class, 'updateCartItem']);
    Route::get('/get-userinfo/{uid}', [UserController::class, 'getUserInfo']);
    Route::PUT('/user-info-update/{id}', [UserController::class, 'updateUserInfo']);
    Route::POST('/change-userpw', [UserController::class, 'changeUserPassword']);
    Route::get('/get-alluser/{need?}', [UserController::class, 'getAlluser']);
    Route::get('/get-allseller', [UserController::class, 'getAllseller']);
    Route::get('/seller-info-by-id/{uid}', [UserDashboardController::class, 'getSellerInfoById']);
    Route::get('/seller-basic-info/{uid}', [UserDashboardController::class, 'sellerBasicInfo']);
    Route::get('/seller-last-product/{uid}', [UserDashboardController::class, 'getSellerLast16Product']);
    Route::get('/seller-all-product/{uid}', [UserDashboardController::class, 'sellerAllProduct']);
    Route::get('/seller-oder-info/{eid}', [UserDashboardController::class, 'getSellerDashboardOrderInfo']);
    Route::get('/seller-oder-list/{eid}', [UserDashboardController::class, 'getSellerDashboardOrderList']);
    Route::get('/seller-oder-details/{oid}', [UserDashboardController::class, 'getSellerDashboardOrderDetails']);
    Route::get('/user-oder-details/{cid}', [UserDashboardController::class, 'getUserOrderDetails']);
    Route::get('/customer-oder-count/{cid}', [UserDashboardController::class, 'customerOrderCounter']);
    Route::get('/seller-profile-details/{eid}', [UserDashboardController::class, 'getSellerProfileDatails']);
    Route::get('/seller-product-filter', [FrontendProductController::class, 'productFilterSeller']);
    Route::apiResource('follower', EnterpFollowerController::class);
    Route::apiResource('/wishlist', WishlistController::class);
    Route::apiResource('/chat-with-seller', ChatController::class);
    Route::get('/get-chats-for-seller/{eid}/{need?}', [ChatController::class, "index"]);
    Route::apiResource('/geo-division', DivisionController::class);
    Route::apiResource('/student', StudentController::class);
    Route::get('/students/{need?}', [StudentController::class, 'index']);
    Route::apiResource('/mentor', MentorController::class);
    Route::apiResource('/course-category', CourseCategoryController::class);
    Route::apiResource('/course', CourseController::class);
    Route::apiResource('/course-provider', CourseProviderController::class);
    Route::get('course-providers/{need?}', [CourseProviderController::class, 'index']);
    Route::apiResource('/course-lessons', CourseVideoUploadAndProcessingController::class);
    Route::get('/course-lesson/{id}', [CourseVideoUploadAndProcessingController::class, 'course_lesson']);
    Route::apiResource('/branch', BranchController::class);
    Route::get('/recent-notifications/{id}', [NotificationController::class, 'recentNotifications']);
    Route::get('/all-notifications/{id}', [NotificationController::class, 'allNotifications']);

    /**
     * @author Md. Shohag Hossain
     * shohag@atilimited.net
     */
    Route::prefix('event')->group(function () {
        Route::apiResource('/newEvent', EventController::class);
        Route::apiResource('/venue', EventVenueController::class);
        Route::apiResource('/sponsor', EventSponsorController::class);
        Route::POST('sponsor-update/{id}', [EventSponsorController::class, 'update']);
        Route::apiResource('/participant', EventParticipantController::class);
        Route::apiResource('/schedule', EventScheduleController::class);
        Route::apiResource('/speaker', EventSpeakerController::class);
        Route::apiResource('/organizer', EventOrganizerController::class);
    });

    Route::get('/event-by-id/{event_pid}/{user_pid?}', [EventController::class, 'show_event']);
    Route::apiResource('/ew-category', EventCategoryController::class);
    Route::get('/event-by-category/{pid}', [EventCategoryController::class, 'event_by_category']);
    Route::get('/my-events/{user_pid}', [FrontendEventController::class, 'my_events']);
    Route::get('/events-by-organizer/{org_pid}', [FrontendEventController::class, 'events_by_organizer']);
    Route::get('/registration-overview/{user_pid}', [FrontendEventController::class, 'registration_overview']);
    Route::get('/event-perticipant/{event_pid}', [FrontendEventController::class, 'perticipant']);
    Route::get('/search-event', [FrontendEventController::class, 'search_event']);
    Route::get('/event-by-participant/{id}', [FrontendEventController::class, 'event_by_participant']);

    Route::get('/users-of-event/{id}', [FrontendEventController::class, 'users_of_event']);
    Route::get('/course-by-student/{id}', [CourseController::class, 'get_course_by_student']);
    Route::get('/provider-details/{provider_pid}', [CourseController::class, 'provider_details']);

    Route::delete('/experience-delete/{experience_pid}', [CourseProviderController::class, 'destroy_experience']);
    Route::delete('/education-delete/{education_pid}', [CourseProviderController::class, 'destroy_education']);
    Route::delete('/branch-delete/{branch_pid}', [CourseProviderController::class, 'destroy_branch']);
    Route::delete('/job-edu-delete/{job_edu_pid}', [JobController::class, 'destroy_job_edu']);
    Route::delete('/job-skill-delete/{job_skill_pid}', [JobController::class, 'destroy_job_skill']);
    Route::delete('/job-experience-delete/{job_exp_pid}', [JobController::class, 'destroy_job_exp']);
    Route::delete('/job-achievement-delete/{job_achi_pid}', [JobController::class, 'destroy_job_achievement']);

    /**
     * BlogPost API
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 06/01/2025
     */
    Route::post('/blog-post', [BlogPostController::class, 'store']);
    Route::post('/blog-post-update/{id}', [BlogPostController::class, 'update']);
    Route::get('/blog-post-homepage', [BlogPostController::class, 'homepage']);
    Route::get('/blog-post-all-blogs', [BlogPostController::class, 'allBlogs']);
    Route::get('/blog-post/{id}/{take_comm?}', [BlogPostController::class, 'getById']);
    Route::post('/blog-comment/{id}', [BlogPostController::class, 'blogCommentStore']);
    Route::get('/get-blog-comment/{id}/{take_comm?}', [BlogPostController::class, 'getblogComment']);
    Route::delete('/delete-blog-post/{id?}/{user_pid?}', [BlogPostController::class, 'destroy']);
    Route::post('/blog-comment-update/{id}', [BlogPostController::class, 'blogCommentUpdate']);
    Route::delete('/delete-comment/{id}', [BlogPostController::class, 'blogCommentDelete']);

    /**
     * Article API
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 11/01/2024
     */
    Route::post('/article', [ArticleController::class, 'store']);
    Route::post('/article-update/{id}', [ArticleController::class, 'update']);
    Route::get('/article-homepage', [ArticleController::class, 'homepage']);
    Route::get('/articles', [ArticleController::class, 'allArticle']);
    Route::get('/articles/{id}', [ArticleController::class, 'getById']);
    Route::delete('/delete-article/{id?}/{user_pid?}', [ArticleController::class, 'destroy']);


    /**
     * Document API
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 12/01/2024
     */
    Route::post('/document', [DocumentController::class, 'store']);
    Route::post('/document-update/{id}', [DocumentController::class, 'update']);
    Route::get('/documents', [DocumentController::class, 'allDocuments']);
    Route::get('/document-homepage', [DocumentController::class, 'documentsHomepage']);
    Route::delete('/delete-document/{id?}/{user_pid?}', [DocumentController::class, 'destroy']);

    /**
     * Resource Library Video API
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 18/01/2024
     */
    Route::post('/video', [ResourceLibraryVideoController::class, 'store']);
    Route::post('/video-update/{id}', [ResourceLibraryVideoController::class, 'update']);
    Route::get('/videos', [ResourceLibraryVideoController::class, 'allVideos']);
    Route::get('/video-homepage', [ResourceLibraryVideoController::class, 'homepage']);
    Route::get('/video/{id}', [ResourceLibraryVideoController::class, 'getById']);
    Route::delete('/delete-video/{id?}/{user_pid?}', [ResourceLibraryVideoController::class, 'destroy']);

    /**
     * get all videos, blogs, article, documents by user id
     * @author Md. Shohag Hossain <shohag@atilimited.net>
     * @since date 03.02.2025
     */
    Route::get('get-vbad-by-pid/{id}/{need?}', [BlogPostController::class, 'get_vbad_by_user']);

    /**
     * @api success stories module APIs
     * @author shohag <shohag@atilimited.net>
     * @since 18.02.2025
     */
    Route::apiResource('/success-stories', SuccessStoriesController::class);
    Route::get('/success-stories-homepage', [SuccessStoriesController::class, 'homePage']);
    Route::post('/update-success-stories/{id}', [SuccessStoriesController::class, 'update']);
    Route::delete('/delete-success-stories/{id?}/{user_pid?}', [SuccessStoriesController::class, 'destroy']);
    Route::get('/success-story-manage/{user_pid}/{need?}', [SuccessStoriesController::class, 'storyManage']);


    /**
     * @api challenges module APIs
     * @author shohag <shohag@atilimited.net>
     * @since 19.02.2025
     */
    Route::apiResource('/challanges', ChallengesController::class);
    Route::delete('/challanges/{id?}/{user_pid?}', [ChallengesController::class, 'destroy']);
    Route::post('/challange-update/{id}', [ChallengesController::class, 'update']);
    Route::get('/challanges-homepage', [ChallengesController::class, 'homepage']);
    Route::get('/challenge-manage/{user_pid}/{need?}', [ChallengesController::class, 'challengeManage']);
});
// });

/**
 * All Backend Controller Route end.
 */



/**
 * All Frontend Controller Route Start
 * @author ATI Limited
 * Khan Rafaat Abtahe
 * rafaat@atilimited.net
 */


/**
 * This Routes needs authorization before take any action.
 */
Route::prefix('frontend')->group(function () {

    /**
     * This route does not need any authorisation before taking any action.
     */
    Route::get('/category', [CategoryFrontendController::class, 'getCategoryData']);
    Route::get('/news', [NewsFrontendController::class, 'getNewsData']);
    Route::get('/news/{nid}', [NewsFrontendController::class, 'getNewsByPId']);
    Route::apiResource('/review-rating', ReviewRatingController::class);
    Route::POST('/seller-review-rating', [ReviewRatingController::class, 'sellerReviewRating']);
    Route::apiResource('/products', FrontendProductController::class);
    Route::get('/products-peginate', [FrontendProductController::class, 'allProductPeginate']);
    Route::get('/popular-products', [FrontendProductController::class, 'popularProduct']);
    Route::get('/popular-products-peginate', [FrontendProductController::class, 'populerProductPeginate']);
    Route::get('/new-products', [FrontendProductController::class, 'newProduct']);
    Route::get('/new-products-peginate', [FrontendProductController::class, 'newProductPeginate']);
    Route::get('/products-by-category-id/{cid}', [FrontendProductController::class, 'productByCategoryid']);
    Route::get('/customer-product-filter', [FrontendProductController::class, 'productFilter']);
    Route::POST('/cart-calculation', [CartController::class, 'calculationCartItems']);
    Route::get('/get-course', [FrontendCourseController::class, 'getCourse']);
    Route::get('/get-course-details/{courseid}', [FrontendCourseController::class, 'getCourseDetails']);
    Route::get('/get-course-by-mentor/{mentorid}', [FrontendCourseController::class, 'getCourseByMentor']);
    Route::POST('/student-course-enroll', [FrontendCourseController::class, 'studentCourseEnrollment']);
    Route::get('/get-student-enrolled-course/{studentid}', [FrontendCourseController::class, 'getStudentEnrolledCourse']);
    Route::get('/get-student-enrolled/{need?}', [FrontendCourseController::class, 'getStudentEnrolled']);
    Route::get('/search-course', [FrontendCourseController::class, 'searchCourseMentorOrTittleWise']);
    Route::apiResource('/student', FrontendStudentController::class);
    Route::apiResource('/mentor', FrontendMentorController::class);
    Route::apiResource('/branch', BranchController::class);



    /**
     * @author Md. Shohag Hossain
     * shohag@atilimited.net
     */
    // Event API
    Route::get('firsteight-of-allevents', [FrontendEventController::class, 'get_first_eight_of_all']);
    Route::get('get-all-events', [FrontendEventController::class, 'get_all']);
    Route::get('/up-comming-events-firstsix', [FrontendEventController::class, 'upcomming_events_firstsix']);
    Route::get('/up-comming-events-all', [FrontendEventController::class, 'upcomming_events']);
    Route::get('/up-comming-events-details/{id}', [FrontendEventController::class, 'upcomming_events_details']);
    Route::get('/featured-events-firstEight', [FrontendEventController::class, 'featured_events_firstEight']);
    Route::get('/featured-events-otherall', [FrontendEventController::class, 'featured_events']);
    Route::get('/post-events-firstSix', [FrontendEventController::class, 'post_events_firstSix']);
    Route::get('/post-events-all', [FrontendEventController::class, 'post_events_all']);
    Route::get('/events-by-division/{code}', [FrontendEventController::class, 'events_by_division']);
    Route::get('/events-by-monthyear/{month}/{year}', [FrontendEventController::class, 'events_by_month_year']);

    // Course API
    Route::get('/course-by-user_id/{user_pid}', [FrontendCourseController::class, 'course_by_user_pid']);
});
/**
 * All Frontend Controller Route end.
 */


/**
 * @api Job Provider
 * @author shohag <shohag@atilimited.net>
 * @author moazzem <moazzem@atilimited.net>
 * @since 03.02.2025
 */
Route::get('/get-all-job-providers/{need?}', [JobController::class, 'getJobProvider']);
Route::post('/job-provider-register', [JobController::class, 'jobProviderStore']);
Route::get('/get-job-provider/{id}', [JobController::class, 'getJobProviderById']);
Route::post('/job-provider-update/{id}', [JobController::class, 'updateJobProvider']);
Route::get('/get-jobs-by-provider/{id}', [JobController::class, 'getJobsByProviderId']);
Route::get('/get-task-by-provider/{id}', [TaskController::class, 'getTasksByJobProviderId']);

/**
 * @api Job Seeker
 * @author shohag <shohag@atilimited.net>
 * @author moazzem <moazzem@atilimited.net>
 * @since 03.02.2025
 */
Route::get('/get-all-job-seekers/{need?}', [JobController::class, 'getJobSeeker']);
Route::post('/job-seeker-register', [JobController::class, 'jobSeekerStore']);
Route::get('/get-job-seeker/{id}', [JobController::class, 'getJobSeekerById']);
Route::POST('/job-seeker-update/{id}', [JobController::class, 'updateJobSeeker']);


Route::post('/skillset-store', [SkillController::class, 'skillsetStore']);
Route::get('/get-skillset', [SkillController::class, 'getSkillset']);
Route::post('/update-skillset/{id}', [SkillController::class, 'updateSkillset']);
Route::post('/skill-store', [SkillController::class, 'skillStore']);
Route::get('/get-skill-by-skillset/{id}', [SkillController::class, 'getSkillsBySkillsetId']);
Route::get('/get-all-skills', [SkillController::class, 'getAllSkills']);
Route::post('/update-skill/{id}', [SkillController::class, 'updateSkill']);
Route::post('/job-post-store', [JobController::class, 'jobPostStore']);
Route::post('/task-store', [TaskController::class, 'taskStore']);
Route::get('/get-latest-jobs', [JobController::class, 'getLatestJobs']);
Route::get('/get-all-jobs', [JobController::class, 'getAllJobs']);
Route::get('/get-job/{id}', [JobController::class, 'getJobById']);

Route::get('/get-latest-tasks', [TaskController::class, 'getLatestTasks']);
Route::get('/get-all-tasks', [TaskController::class, 'getAllTasks']);
Route::get('/get-task/{id}', [TaskController::class, 'getTaskById']);
Route::post('/search-job-tasks', [JobController::class, 'searchJobsAndTasks']);

Route::POST('/job-post-update/{id}', [JobController::class, 'updateJobPost']);
Route::POST('/task-update/{id}', [TaskController::class, 'updateTask']);
Route::get('/delete-job-post/{id}', [JobController::class, 'deleteJobPost']);
Route::get('/delete-task-post/{id}', [TaskController::class, 'deleteTask']);
