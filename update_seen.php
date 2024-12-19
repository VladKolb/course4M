<?php
include("db.php");
$table = $_POST['table'];
if (isset($_POST['id'])) {
    if($table == "change_status_message" || $table == "message_for_sysadmin") {
        $id = (int)$_POST['id'];
    $update_sql = "UPDATE $table SET isSeen = 1 WHERE id = $id";
    $mysqli->query($update_sql);
    } elseif($table == 'message_coef'){
        $id = (int)$_POST['id'];
        $update_sql = "UPDATE message_coef SET isRead = 1 WHERE user_id = $id";
        $mysqli->query($update_sql);
    } else{
        $id = (int)$_POST['id'];
        $update_sql = "UPDATE message_table SET isSeen = 1 WHERE id = $id";
        $mysqli->query($update_sql);
    }
    
}
?>