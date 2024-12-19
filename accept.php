<?php
include 'db.php';

// Получаем ID записи и таблицу
if(isset($_POST['id'])){
    $id = (int)$_POST['id'];
} elseif(isset($_POST['user_id'])){
    $id = (int)$_POST['user_id'];
} elseif(isset($_POST['dish_id'])){
    $id = (int)$_POST['dish_id'];
} elseif(isset($_POST['order_id'])){
    $id = (int)$_POST['order_id'];
} elseif(isset($_POST['restoraunt_id'])){
    $id = (int)$_POST['restoraunt_id'];
} else{
    $id = 0;
}

$table = isset($_POST['table']) ? $_POST['table'] : '';

if ($id <= 0) {
    die("Некорректный ID записи.");
}

$restoraunt_id = $_POST['restoraunt_id'];
$customer_id = $_POST['customer_id'];
$message = "Заказ принят";

$sql = "INSERT INTO message_table (message, order_id, restoraunt_id, customer_id) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("siii", $message, $id, $restoraunt_id, $customer_id);
    if (!$stmt->execute()) {
        $error_message = "Ошибка при добавлении.";
    } else{
        echo "Сообщение успешно добавлено!";
    }

    $update_sql = "UPDATE new_orders_table SET isAccept = 1, added_at = CURRENT_TIMESTAMP WHERE order_id = $id AND restoraunt_id = $restoraunt_id";
    $stmt = $mysqli->prepare($update_sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    echo "Запись клиента успешно обновлена!";


    $update_sql = "UPDATE message_for_restoraunts SET isAccept = 1 WHERE order_id = ? AND restoraunt_id = ?";
    $stmt = $mysqli->prepare($update_sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }
    $stmt->bind_param("ii", $id, $restoraunt_id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }
    echo "Запись клиента успешно обновлена!";

?>

<form id="returnForm" method="POST" action="index.php">
    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
</form>
<script>
    document.getElementById('returnForm').submit();
</script>