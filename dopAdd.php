<?php

include 'db.php';
session_start();

$table = isset($_POST['table']) ? $_POST['table'] : '';

$dish_cost = $_POST['dish_cost'];
$dish_name = $_POST['dish_name'];
$restoraunt_id = $_POST['restoraunt_id'];

$sql = "CALL AddDish(?, ?, ?)";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sdi", $dish_name, $dish_cost, $restoraunt_id);

if (!$stmt->execute()) {
   
    $error_message = "Ошибка при добавлении блюда: " . $stmt->error;
    echo $error_message;
} else {
    
    if ($mysqli->affected_rows > 0) {
        echo "Блюдо успешно добавлено!";
    } else {
        echo "Блюдо не добавлено из-за ошибки в хранимой процедуре.";
    }
}


?>
