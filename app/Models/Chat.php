<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    public $timestamps = false;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ec_chat_customer';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'chat_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    public function customer()
    {
        return $this->hasOne(Customer::class, 'customer_pid', 'message_sender_pid');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'product_pid', 'product_pid');
    }

    public function seller()
    {
        return $this->hasOne(Seller::class, 'enterpenure_pid', 'message_recever_pid');
    }
}
