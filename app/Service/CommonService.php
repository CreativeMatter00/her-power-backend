<?php

namespace App\Service;

use DateTime;
use Illuminate\Support\Facades\DB;

class CommonService
{
    /**
     * Universal class for Helper function
     */



    /**
     * @param date YYYY-MM-DD
     */
    public static function todayToGivenTimeDiff(string $todate)
    {
        $today = new DateTime();

        $todateConverted = date('Y-m-d', strtotime($todate));

        $givenDate = new DateTime($todateConverted);

        $interval = $today->diff($givenDate);


        return $interval->y . " years, " . $interval->m . " months, and " . $interval->d . " days.";
    }

    public static function getEnterprenureIdByUserId($uid){

        return DB::table('ec_enterpenure')->select('enterpenure_pid')->where('user_pid',$uid)->first()->enterpenure_pid;
    }
}
