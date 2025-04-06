<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrepreneur extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ec_enterpenure';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'enterpenure_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'user_pid',
    //     'salutation',
    //     'fname',
    //     'lname',
    //     'full_name',
    //     'father_name',
    //     'mother_name',
    //     'gender',
    //     'dob',
    //     'skill',
    //     'last_education',
    //     'mobile_no',
    //     'address_line',
    //     'location_pid',
    //     'shop_name',
    //     'ud_serialno',
    //     'cre_by',
    //     'upd_date',
    //     'upd_by',
    // ];
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_pid', 'user_pid');
    }
}
