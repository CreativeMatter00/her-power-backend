<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    public $timestamps = false;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trn_student';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'student_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * relation with user pid
     * @author shohag <shohag@atilimited.net>
     * @return void
     */
    public function user_info()
    {
        return $this->hasOne(User::class, 'user_pid', 'ref_user_pid');
    }

    /**
     * relation with user pid
     * @author shohag <shohag@atilimited.net>
     * @return void
     */
    public function cust_info()
    {
        return $this->hasOne(Customer::class, 'user_pid', 'ref_user_pid');
    }
}
