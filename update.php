<?php
include 'db.php';

// Получаем таблицу, ID записи и новые данные
$table = $_POST['table'];
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
}

if ($table == 'client') {
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $creator_id = $_POST['creator_id'];

    // Валидация данных
    if (!preg_match('/^[a-zA-Zа-яА-Я\s]+$/u', $name)) {
        die("Имя не должно содержать числа.");
    }

    if (!preg_match('/^\+375 (29|44|25|33) \d{3}-\d{2}-\d{2}$/', $phone_number)) {
        die("Номер телефона должен быть в формате: +375 YY XXX-XX-XX, где YY - 29, 44, 33 или 25.");
    }

    // Подготовка и выполнение запроса для обновления данных в client
    $sql = "UPDATE client SET name = ?, phone_number = ?, creator_id = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }
    $stmt->bind_param("ssii", $name, $phone_number, $creator_id, $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }
    echo "Запись клиента успешно обновлена!";

} elseif ($table == 'order_table') {
    // Получаем старый client_id для сравнения
    $get_old_client_sql = "SELECT client_id FROM order_table WHERE id = ?";
    $get_old_client_stmt = $mysqli->prepare($get_old_client_sql);
    if (!$get_old_client_stmt) {
        die("Ошибка подготовки запроса получения старого client_id: " . $mysqli->error);
    }
    $get_old_client_stmt->bind_param("i", $id);
    $get_old_client_stmt->execute();
    $get_old_client_stmt->bind_result($old_client_id);
    $get_old_client_stmt->fetch();
    $get_old_client_stmt->close();

    $new_client_id = $_POST['client_id'];
    $delivery_date = $_POST['delivery_date'];
    $delivery_cost = $_POST['delivery_cost'];
    $creator_id = $_POST['creator_id'];

    // Валидация данных
    if ($new_client_id <= 0) {
        die("Выберите корректный ID клиента.");
    }

    if (!preg_match('/^(20[0-9]{2})-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $delivery_date)) {
        die("Дата доставки должна быть в формате YYYY-MM-DD и в диапазоне от 2000-01-01 до 2099-12-31.");
    }

    if ($delivery_cost <= 0 || $delivery_cost >= 1000000) {
        die("Стоимость доставки должна быть больше 0 и меньше 1000000.");
    }

    // Обновление записи в order_table
    $sql = "UPDATE order_table SET client_id = ?, delivery_date = ?, delivery_cost = ?, creator_id = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }
    $stmt->bind_param("issii", $new_client_id, $delivery_date, $delivery_cost, $creator_id, $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }
    echo "Запись заказа успешно обновлена!";

    // Если client_id изменился, нужно пересчитать количество заказов для обоих клиентов
    if ($old_client_id != $new_client_id) {
        // Пересчитываем количество заказов для старого клиента
        $count_old_sql = "SELECT COUNT(*) FROM order_table WHERE client_id = ?";
        $count_old_stmt = $mysqli->prepare($count_old_sql);
        if (!$count_old_stmt) {
            die("Ошибка подготовки запроса для старого клиента: " . $mysqli->error);
        }
        $count_old_stmt->bind_param("i", $old_client_id);
        $count_old_stmt->execute();
        $count_old_stmt->bind_result($old_order_count);
        $count_old_stmt->fetch();
        $count_old_stmt->close();

        // Обновляем значение order_count у старого клиента
        $update_old_sql = "UPDATE client SET order_count = ? WHERE id = ?";
        $update_old_stmt = $mysqli->prepare($update_old_sql);
        if (!$update_old_stmt) {
            die("Ошибка подготовки запроса для обновления старого клиента: " . $mysqli->error);
        }
        $update_old_stmt->bind_param("ii", $old_order_count, $old_client_id);
        if (!$update_old_stmt->execute()) {
            die("Ошибка выполнения запроса для обновления старого клиента: " . $update_old_stmt->error);
        }

        // Пересчитываем количество заказов для нового клиента
        $count_new_sql = "SELECT COUNT(*) FROM order_table WHERE client_id = ?";
        $count_new_stmt = $mysqli->prepare($count_new_sql);
        if (!$count_new_stmt) {
            die("Ошибка подготовки запроса для нового клиента: " . $mysqli->error);
        }
        $count_new_stmt->bind_param("i", $new_client_id);
        $count_new_stmt->execute();
        $count_new_stmt->bind_result($new_order_count);
        $count_new_stmt->fetch();
        $count_new_stmt->close();

        // Обновляем значение order_count у нового клиента
        $update_new_sql = "UPDATE client SET order_count = ? WHERE id = ?";
        $update_new_stmt = $mysqli->prepare($update_new_sql);
        if (!$update_new_stmt) {
            die("Ошибка подготовки запроса для обновления нового клиента: " . $mysqli->error);
        }
        $update_new_stmt->bind_param("ii", $new_order_count, $new_client_id);
        if (!$update_new_stmt->execute()) {
            die("Ошибка выполнения запроса для обновления нового клиента: " . $update_new_stmt->error);
        }
    } else {
        // Если client_id не изменился, просто пересчитаем количество заказов для текущего клиента
        $count_new_sql = "SELECT COUNT(*) FROM order_table WHERE client_id = ?";
        $count_new_stmt = $mysqli->prepare($count_new_sql);
        if (!$count_new_stmt) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
        $count_new_stmt->bind_param("i", $new_client_id);
        $count_new_stmt->execute();
        $count_new_stmt->bind_result($new_order_count);
        $count_new_stmt->fetch();
        $count_new_stmt->close();

        // Обновляем значение order_count у клиента
        $update_new_sql = "UPDATE client SET order_count = ? WHERE id = ?";
        $update_new_stmt = $mysqli->prepare($update_new_sql);
        if (!$update_new_stmt) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
        $update_new_stmt->bind_param("ii", $new_order_count, $new_client_id);
        if (!$update_new_stmt->execute()) {
            die("Ошибка выполнения запроса: " . $update_new_stmt->error);
        }
    }
} elseif ($table == 'users') {
    $name = $_POST['user_name'];
    $surname = $_POST['user_surname'];
    $email = $_POST['user_email'];
    $password =$_POST['user_password'];
    $check_id = $_POST['check_id'];
    

    $sql = "SELECT user_id FROM users WHERE user_email = '$email'";
    $sql_result = $mysqli->query($sql);

    if ($sql_result->num_rows > 0) {
        if($sql_result->fetch_assoc()['user_id'] != $check_id){
            $error_message = "Email уже зарегистрирован.";
        } else {
            // Добавление пользователя в базу данных
            $update_sql = "UPDATE users SET user_name = ?, user_surname = ?, user_email = ?, user_password = ? WHERE user_id = ?";
            $stmt = $mysqli->prepare($update_sql);
            if (!$stmt) {
                die("Ошибка подготовки запроса: " . $mysqli->error);
            }
            $stmt->bind_param("ssssi", $name, $surname, $email, $password, $id);
            if (!$stmt->execute()) {
                die("Ошибка выполнения запроса: " . $stmt->error);
            }
            echo "Запись клиента успешно обновлена!";
        }
    } 
} elseif ($table == 'dish_table'){
    $dish_name = $_POST['dish_name'];
    $dish_cost = $_POST['dish_cost'];

    $update_sql = "UPDATE dish_table SET dish_name = ?, dish_cost = ? WHERE dish_id = ?";
        $stmt = $mysqli->prepare($update_sql);
        if (!$stmt) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
        $stmt->bind_param("sii", $dish_name, $dish_cost, $id);
        if (!$stmt->execute()) {
            die("Ошибка выполнения запроса: " . $stmt->error);
        }
        echo "Запись блюда успешно обновлена!";
} elseif ($table == 'new_order_table'){
    $order_text = $_POST['order_text'];
    $order_cost = $_POST['order_cost'];
    $restoraunt_id = $_POST['restoraunt_id'];
    $customer_id = $_POST['customer_id'];


    $update_sql = "UPDATE new_order_table SET order_cost = ?, restoraunt_id = ?, customer_id = ? WHERE order_id = ?";
        $stmt = $mysqli->prepare($update_sql);
        if (!$stmt) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
        $stmt->bind_param("iiii", $order_cost, $restoraunt_id, $customer_id, $id);
        if (!$stmt->execute()) {
            die("Ошибка выполнения запроса: " . $stmt->error);
        }
        echo "Запись блюда успешно обновлена!";
} elseif ($table == 'restoraunt_table'){
    $restoraunt_name = $_POST['restoraunt_name'];
    
    $update_sql = "UPDATE restoraunt_table SET restoraunt_name = ? WHERE restoraunt_id = ?";
        $stmt = $mysqli->prepare($update_sql);
        if (!$stmt) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
        $stmt->bind_param("si", $restoraunt_name, $id);
        if (!$stmt->execute()) {
            die("Ошибка выполнения запроса: " . $stmt->error);
        }
        echo "Запись блюда успешно обновлена!";
}
// Возвращаемся на главную страницу с выбранной таблицей через POST
?>
<form id="returnForm" method="POST" action="index.php">
    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
</form>
<script>
    document.getElementById('returnForm').submit();
</script>
