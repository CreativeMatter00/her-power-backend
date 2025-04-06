<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventVenue extends Model
{

    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ew_venue';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'venue_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


    protected $guarded = [];

    public function events(){
    return $this->belongsTo(Event::class,'venue_pid','venue_pid');
    }
}
