<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'challenges';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'cpost_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    public function documents()
    {
        return $this->hasMany(ChallengePostAttachment::class, 'ref_pid', 'cpost_pid');
    }
}
