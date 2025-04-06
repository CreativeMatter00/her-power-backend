<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    public $timestamps = false;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ec_customer';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'customer_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    public function reviewRatings()
    {
        return $this->hasMany(ReviewRating::class, 'customer_pid', 'customer_pid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_pid', 'user_pid');
    }

    /**
     * this function make for profile picture
     * @author shohag <shohag@atilimited.net>
     * @since 12.03.2025
     * @return object
     */
    public function attachment()
    {
        return $this->hasOne(Attachment::class, 'ref_pid', 'user_pid');
    }
}
