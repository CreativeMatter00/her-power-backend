<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ec_product';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_pid',
        'product_name',
        'category_pid',
        'enterpenure_pid',
        'uom_no',
        'origin',
        'brand_name',
        'model_name',
        'ud_serialno',
        'description',
        'remarks',
        'cre_by',
        'active_status',
        'upd_date',
        'upd_by',
        'origin',
        'stockout_life',
        'stock_available',
        're_stock_level',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'ref_pid', 'product_pid');
    }
    public function productvariants()
    {
        return $this->hasmany(ProductVariant::class, 'product_pid', 'product_pid');
    }

    public function reviewratings()
    {
        return $this->hasMany(ReviewRating::class, 'product_pid', 'product_pid');
    }
    public function entrepreneurs()
    {

        return $this->belongsTo(Entrepreneur::class, 'enterpenure_pid', 'enterpenure_pid');
    }

    /**
     * this function make for product picture
     * @author shohag <shohag@atilimited.net>
     * @since 12.03.2025
     * @return object
     */
    public function attachment()
    {
        return $this->hasOne(Attachment::class, 'ref_pid', 'product_pid');
    }
}
