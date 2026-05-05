<?php
function formatMessageTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $date = date('Y-m-d', $time);
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('yesterday'));

    if ($date === $today) {
        // Idag: "14:30"
        return date('H:i', $time);
    } elseif ($date === $yesterday) {
        // Igår: "Yesterday 14:30"
        return "Yesterday " . date('H:i', $time);
    } elseif ($now - $time < 604800) {
        // Senaste veckan: "Mon 14:30"
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return $days[date('w', $time)] . " " . date('H:i', $time);
    } elseif (date('Y', $time) == date('Y')) {
        // Samma år: "12 May"
        return date('j M', $time);
    } else {
        // Annat år: "12 May 2023"
        return date('j M Y', $time);
    }
}
