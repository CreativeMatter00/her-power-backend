<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnterpRating extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ec_enterp_rating';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'rating_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


    protected $guarded = [];
}
