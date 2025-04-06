<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
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
    protected $guarded = [];

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
