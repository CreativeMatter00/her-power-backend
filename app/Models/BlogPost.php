<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rl_blog_post';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'bpost_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * attachment relationship
     *
     * @return array
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'ref_pid', 'bpost_pid');
    }

    /**
     * commants relationship
     *
     * @return array
     */
    public function comments()
    {
        return $this->hasMany(Comments::class, 'bpost_pid', 'bpost_pid');
    }
}
