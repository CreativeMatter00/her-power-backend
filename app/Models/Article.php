<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nc_post';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'post_id';

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
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'ref_pid', 'post_pid');
    }

    /**
     * documents relationship
     *
     * @return array
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'ref_pid', 'post_pid');
    }
}
