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

if ($table == 'client') {
    // Удаление клиента
    $sql = "DELETE FROM client WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $stmt->close();

} elseif ($table == 'order_table') {
    // Удаление заказа
    // Сначала получим client_id, чтобы знать, у какого клиента уменьшать order_count
    $get_client_sql = "SELECT client_id FROM order_table WHERE id = ?";
    $get_client_stmt = $mysqli->prepare($get_client_sql);
    if (!$get_client_stmt) {
        die("Ошибка подготовки запроса для получения client_id: " . $mysqli->error);
    }

    $get_client_stmt->bind_param("i", $id);
    if (!$get_client_stmt->execute()) {
        die("Ошибка выполнения запроса для получения client_id: " . $get_client_stmt->error);
    }

    $get_client_stmt->bind_result($client_id);
    if (!$get_client_stmt->fetch()) {
        die("Не удалось найти заказ с указанным ID.");
    }
    $get_client_stmt->close();

    // Удаление записи из order_table
    $delete_order_sql = "DELETE FROM order_table WHERE id = ?";
    $delete_order_stmt = $mysqli->prepare($delete_order_sql);
    if (!$delete_order_stmt) {
        die("Ошибка подготовки запроса на удаление заказа: " . $mysqli->error);
    }

    $delete_order_stmt->bind_param("i", $id);
    if (!$delete_order_stmt->execute()) {
        die("Ошибка выполнения запроса на удаление заказа: " . $delete_order_stmt->error);
    }
    $delete_order_stmt->close();

    // Уменьшение значения order_count для клиента
    $count_sql = "SELECT COUNT(*) FROM order_table WHERE client_id = ?";
    $count_stmt = $mysqli->prepare($count_sql);
    if (!$count_stmt) {
        die("Ошибка подготовки запроса для пересчета заказов клиента: " . $mysqli->error);
    }

    $count_stmt->bind_param("i", $client_id);
    if (!$count_stmt->execute()) {
        die("Ошибка выполнения запроса для пересчета заказов клиента: " . $count_stmt->error);
    }

    $count_stmt->bind_result($order_count);
    $count_stmt->fetch();
    $count_stmt->close();

    // Обновляем значение order_count у клиента
    $update_client_sql = "UPDATE client SET order_count = ? WHERE id = ?";
    $update_client_stmt = $mysqli->prepare($update_client_sql);
    if (!$update_client_stmt) {
        die("Ошибка подготовки запроса на обновление клиента: " . $mysqli->error);
    }

    $update_client_stmt->bind_param("ii", $order_count, $client_id);
    if (!$update_client_stmt->execute()) {
        die("Ошибка выполнения запроса на обновление клиента: " . $update_client_stmt->error);
    }
    $update_client_stmt->close();

} elseif($table == 'users'){
    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $stmt->close();
} elseif($table == 'dish_table'){
    $sql = "DELETE FROM dish_table WHERE dish_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $stmt->close();
} elseif($table == 'restoraunt_table'){
    $sql = "DELETE FROM restoraunt_table WHERE restoraunt_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $stmt->close();
} elseif($table == 'new_orders_table'){
    $restoraunt_id = $_POST['restoraunt_id'];
    $customer_id = $_POST['customer_id'];
    $sql = "DELETE FROM new_orders_table WHERE order_id = ? AND restoraunt_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("ii", $id, $restoraunt_id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $message = "Заказ удалён";
    $sql = "INSERT INTO message_table (message, order_id, restoraunt_id, customer_id) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("siii", $message, $id, $restoraunt_id, $customer_id);
    if (!$stmt->execute()) {
        $error_message = "Ошибка при добавлении блюда.";
    } else{
        echo "Сообщение успешно добавлено!";
    }

    $sql = "DELETE FROM ordered_dishes WHERE order_id = ? AND dish_id IN (SELECT dish_id FROM dish_table WHERE restoraunt_id = ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("ii", $id, $restoraunt_id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $stmt->close();

    $sql = "DELETE FROM message_for_restoraunts WHERE order_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }
} 
else {
    die("Неизвестная таблица.");
}

?>

<form id="returnForm" method="POST" action="index.php">
    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
</form>
<script>
    document.getElementById('returnForm').submit();
</script>
