<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ew_event';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'event_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'ref_pid', 'event_pid');
    }

    public function venues()
    {
        return $this->hasMany(EventVenue::class, 'venue_pid', 'venue_pid');
    }

    public function eventSchedule()
    {
        return $this->hasMany(EventSchedule::class, 'event_pid', 'event_pid');
    }

    public function tricketInfo()
    {
        return $this->hasMany(TricketPayment::class, 'event_pid', 'event_pid');
    }

    public function notification()
    {
        return $this->hasMany(EventNotification::class, 'event_pid', 'event_pid');
    }

    public function venue()
    {
        return $this->hasOne(EventVenue::class, 'venue_pid', 'venue_pid');
    }
}
