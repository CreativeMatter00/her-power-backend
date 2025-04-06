<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ec_news';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'news_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'news_pid',
        'news_title',
        'news_content',
        'publish_date',
        'effectivefrom',
        'effectiveto',
        'news_author',
        'attached_url',
        'ud_serialno',
        'remarks',
        'pid_currdate',
        'pid_prefix',
        'cre_date',
        'cre_by',
        'upd_date',
        'upd_by',
        'active_status',
        'unit_no',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'ref_pid', 'news_pid');
    }
}
