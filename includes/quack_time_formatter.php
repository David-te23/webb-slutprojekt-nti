<?php
function formatQuackTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) { //Under 1 min
        return "now";
    } elseif ($diff < 3600) { //Under 1 timme
        return floor($diff / 60) . "m";
    } elseif ($diff < 86400) { // 24 timmar
        return floor($diff / 3600) . "h";
    } elseif (date('Y-m-d', $time) == date('Y-m-d')) {
        // Om det mot förmodan gått > 24h men fortfarande är samma kalenderdag
        return date('H:i', $time);
    } elseif (date('Y', $time) == date('Y')) {
        // Samma år men annan dag
        return date('M j', $time); 
    } else {
        // Annat år
        return date('j M Y', $time);
    }
}