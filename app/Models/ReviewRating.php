<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewRating extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ec_rating';

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
    protected $fillable = [
        'order_pid',
        'product_pid',
        'customer_pid',
        'enterpenure_pid',
        'rating_date',
        'rating_type',
        'rating_marks',
        'cre_date',
        'cre_by',
        'upd_date',
        'upd_by',
        'active_status',
        'unit_no',
        'review_content',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'rating_id',
        'order_pid',
        'product_pid',
        'enterpenure_pid',
        'rating_type',
        'unit_no',
        'ud_serialno',
        'remarks',
        'pid_currdate',
        'pid_prefix',
        'cre_date',
        'cre_by',
        'upd_date',
        'upd_by',
        'active_status',

    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'ref_pid', 'rating_pid');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_pid', 'customer_pid');
    }
}
