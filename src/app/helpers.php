<?php

if (!function_exists('formatJapaneseDate')) {
    function formatJapaneseDate($date = null)  // nullのデフォルト値を設定
    {
        if (is_null($date)) {
            $date = \Carbon\Carbon::now();  // 引数がない場合は現在時刻を使用
        }
        
        $week = ['日', '月', '火', '水', '木', '金', '土'];
        
        if (!$date instanceof \Carbon\Carbon) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        return $date->format('Y年n月j日') . '(' . $week[$date->dayOfWeek] . ')';
    }
}