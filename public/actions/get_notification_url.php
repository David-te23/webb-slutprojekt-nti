<?php
 //Genererar rätt URL baserat på notis-typ och källa
 function getNotificationUrl($type, $source_id, $source_user_id) {
    switch ($type) {
        case 'message':
            return "messages.php?user_id=" . $source_user_id; 
        case 'like':
        case 'comment':
        case 'quack':
            return "quack.php?id=" . $source_id;
        case 'follow':
            return "profile.php?id=" . $source_id;
        case 'requack':
            return "quack.php?id=" . $source_id;
            
        default:
            return "#";
    }
}
