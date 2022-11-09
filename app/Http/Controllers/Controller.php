<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function convertPersianNumbersToEnglish($input)
    {

        try {
            list($date, $time, $name) = explode("_", $input);
            $string = $date . $time;

            $newNumbers = range(0, 9);
            // 1. Persian HTML decimal
            $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
            // 2. Arabic HTML decimal
            $arabicDecimal = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
            // 3. Arabic Numeric
            $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
            // 4. Persian Numeric
            $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

            $string = str_replace($persianDecimal, $newNumbers, $string);
            $string = str_replace($arabicDecimal, $newNumbers, $string);
            $string = str_replace($arabic, $newNumbers, $string);
            $string = str_replace($persian, $newNumbers, $string);
            $dateCall = Carbon::parse($string)->format('Y-m-d H:i:s');
            //$dateCall = Carbon::parse($string)->format('Y-m-d -  g:i A');
            //dd($dateCall);
            return $dateCall;
        } catch (\Exception $e) {
            return null;
        }

    }

}
