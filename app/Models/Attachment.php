<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attached_file';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'attached_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ref_object_name',
        'ref_object_code',
        'ref_pid',
        'file_type',
        'file_url',
        'file_extantion',
        'remarks',
        'pid_prefix',
        'cre_by',
        'img_thumb',
        'img_cart',
        'img_wishlist',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'attached_id',
        'ref_object_name',
        'ref_object_code',
        'ref_pid',
        'file_type',
        'file_extantion',
        'remarks',
        'pid_prefix',
        'cre_by',
        'img_thumb',
        'img_cart',
        'img_wishlist',
        'pid_currdate',
        'cre_date',
        'upd_date',
        'upd_by',
        'active_status',
        'unit_no',

    ];

    public function news()
    {

        return $this->belongsTo(News::class, 'ref_pid', 'news_pid');
    }
}
