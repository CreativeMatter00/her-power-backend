<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    protected $statusCode;
    protected $message;

    public function __construct($resource, string $message, int $statusCode = 200)
    {
        parent::__construct($resource);
        $this->statusCode = $statusCode;
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "providor_id" => $this->providor_id,
            "providor_pid" => $this->providor_pid,
            "providor_name" => $this->providor_name,
            "second_name" => $this->second_name,
            "mobile_no" => $this->mobile_no,
            "email_id" => User::where('user_pid', $this->ref_user_pid)->pluck('email')->first(),
            "website_address" => $this->website_address,
            "address_line" => $this->address_line,
            "address_pid" => $this->address_pid,
            "trade_licence" => $this->trade_licence,
            "vat_reg_id" => $this->vat_reg_id,
            "tax_reg_id" => $this->tax_reg_id,
            "tin_number" => $this->tin_number,
            "ud_serialno" => $this->ud_serialno,
            "remarks" => $this->remarks,
            "pid_currdate" => $this->pid_currdate,
            "pid_prefix" => $this->pid_prefix,
            "cre_date" => $this->cre_date,
            "cre_by" => $this->cre_by,
            "upd_date" => $this->upd_date,
            "upd_by" => $this->upd_by,
            "active_status" => $this->active_status,
            "unit_no" => $this->unit_no,
            "ref_user_pid" => $this->ref_user_pid,
            "approve_flag" => $this->approve_flag,
            "approve_by" => $this->approve_by,
            "approve_date" => $this->approve_date
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        $meta = [
            'message' => $this->message,
            'status' => true,
            'http_status' => $this->statusCode,
        ];

        return [
            'meta' => $this->filterNullValues($meta),
        ];
    }

    /**
     * Filter out null, empty strings, and empty arrays from an array.
     *
     * @param  array  $array
     * @return array
     */
    protected function filterNullValues(array $array)
    {
        return array_filter($array, function ($value) {
            if (is_array($value)) {
                return !empty($this->filterNullValues($value));
            }
            return !is_null($value) && $value !== '';
        });
    }
}
