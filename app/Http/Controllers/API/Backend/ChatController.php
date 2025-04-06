<?php

namespace App\Http\Controllers\API\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCommonResponseResource;
use App\Http\Resources\ErrorResource;
use App\Models\Chat;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($eid, int $need = null)
    {
        $baseUrl = $this->baseURL;
        $query = DB::table('ec_chat_customer as a')
            ->select(
                'a.chat_pid',
                DB::raw("CONCAT('$baseUrl/', (SELECT p.file_url FROM attached_file p WHERE p.ref_pid = a.product_pid AND p.ref_object_name = 'product' LIMIT 1)) as producImg"),
                'a.product_pid',
                'pdt.product_name',
                'cu.fname as customerFirstName',
                'cu.lname as customerLastName',
                'a.review_content',
                DB::raw("CONCAT('$baseUrl/', (SELECT csp.file_url FROM attached_file csp WHERE csp.ref_pid = a.message_sender_pid AND csp.ref_object_name = 'profile_photo' AND csp.img_thumb IS NULL LIMIT 1)) as customerImg"),
                'eu.fname as sellerFirstName',
                'eu.lname as sellerLastName',
                'eu.shop_name',
                'a.reply_content',
                'a.cre_date',
                DB::raw("CONCAT('$baseUrl/', (SELECT csp.file_url FROM attached_file csp WHERE csp.ref_pid = a.message_recever_pid AND csp.ref_object_name = 'profile_photo' AND csp.img_thumb IS NULL LIMIT 1)) as sellerImg")
            )
            ->leftJoin('ec_customer as cu', 'a.message_sender_pid', '=', 'cu.user_pid')
            ->leftJoin('ec_product as pdt', 'a.product_pid', '=', 'pdt.product_pid')
            ->leftJoin('ec_enterpenure as eu', 'a.message_recever_pid', '=', 'eu.user_pid')
            ->where('a.message_recever_pid', '=', $eid);

        if ($need != null) {
            $data['chatinfo'] = $query->paginate($need);
        } else {
            $data['chatinfo'] = $query->get();
        }

        return new ApiCommonResponseResource($data, 'Data fetched', 200);
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
        $saveChat = new Chat();
        $saveChat->product_pid = $request->product_pid;
        $saveChat->message_sender_pid = $request->message_sender_pid;
        $saveChat->message_recever_pid = $request->message_recever_pid;
        $saveChat->review_content = $request->review_content;
        $saveChat->save();


        return new ApiCommonResponseResource($saveChat, "Data Saved", 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $baseUrl = $this->baseURL;


        try {
            $chats = Chat::select('chat_id', 'chat_pid', 'product_pid', 'message_sender_pid', 'message_recever_pid', 'review_content', 'reply_content', 'cre_date')
                ->with(['customer' => function ($query) {
                    $query->select('customer_id', 'customer_pid', 'user_pid', 'fname', 'lname')->with('attachment');
                }, 'product' => function ($query) {
                    $query->select('product_id', 'product_pid', 'product_name');
                }, 'seller' => function ($query) {
                    $query->select('enterpenure_id', 'enterpenure_pid', 'user_pid', 'fname', 'lname', 'shop_name')->with('attachment');
                }])
                ->whereNotNull('reply_content')
                ->where('product_pid', $id)
                ->orderBy('chat_id', 'desc')
                ->paginate(4);

            $chats->transform(function ($query) {
                if ($query->customer) {
                    $query->customerFirstName = $query->customer->fname;
                    $query->customerLastName = $query->customer->lname;
                    if ($query->customer->attachment) {
                        $query->customerImg = asset('public/' . $query->customer->attachment->file_url);
                    }
                }
                if ($query->seller) {
                    $query->sellerFirstName = $query->seller->fname;
                    $query->sellerLastName = $query->seller->lname;
                    $query->shop_name = $query->seller->shop_name;
                    if ($query->seller->attachment) {
                        $query->sellerImg = asset('public/' . $query->seller->attachment->file_url);
                    }
                }
                if ($query->product) {
                    $query->product_name = $query->product->product_name;
                }
                unset($query->customer, $query->seller, $query->product);
                return $query;
            });

            $productInfo = Product::with('attachment')->select('product_pid', 'product_name')
                ->where('product_pid', $chats[0]->product_pid)
                ->first();

            if ($productInfo->attachment->file_url) {
                $productInfo->file_url = $productInfo->attachment->file_url;
                unset($productInfo->attachment);
            }

            $data = [
                'chatinfo' => $chats,
                'productInfo' => $productInfo
            ];

            return new ApiCommonResponseResource($data, 'Data fetched', 200);
        } catch (Exception $e) {

            return new ErrorResource("No chat found", 200, true);
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
    public function update(Request $request, string $id)
    {
        $updateSttaus = Chat::where('chat_pid', $id)->update(
            [
                "reply_content" => $request->reply_content,
                "chat_status" => 2,
            ]

        );

        if ($updateSttaus) {
            $getChatBypid = DB::select("SELECT
            a.chat_pid,
            cu.fname as customerFirstName,
            cu.lname as customerLastName,
            a.review_content,
            eu.fname as sellerFirstName,
            eu.lname as sellerLastName,
            eu.shop_name,
            a.reply_content,
            a.cre_date
            FROM
            ec_chat_customer a
            LEFT JOIN ec_customer cu on a.message_sender_pid = cu.user_pid
            LEFT JOIN ec_enterpenure eu on a.message_recever_pid = eu.user_pid
            where a.chat_pid = ?", [$id]);

            return new ApiCommonResponseResource($getChatBypid, 'Replay successful', 200);
        } else {
            return (new ErrorResource('replay failed', 200))->response()->setStatusCode(200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
