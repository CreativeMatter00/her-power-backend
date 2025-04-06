<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseProvider extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trn_providor';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'providor_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];


    /**
     * attachments relationship
     * @author shohag <shohag@atilimited.net>
     * @since 27.11.2024
     * @return collection
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'ref_pid', 'ref_user_pid');
    }
}
