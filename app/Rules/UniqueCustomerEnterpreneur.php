<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueCustomerEnterpreneur implements ValidationRule
{
    protected $customer_pid;

    public function __construct($customer_pid)
    {
        $this->customer_pid = $customer_pid;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the combination of customer_pid and enterpenure_pid exists
        $exists = DB::table('ec_enterp_follower') // Replace with your actual table name
                    ->where('customer_pid', $this->customer_pid)
                    ->where('enterpenure_pid', $value)
                    ->exists();

        if ($exists) {
            $fail('You already followed this seller');
        }
    }
}
