<?php
include 'db.php';
session_start();

$table = isset($_POST['table']) ? $_POST['table'] : '';

if ($table == 'client') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $creator_id = isset($_POST['creator_id']) ? (int)$_POST['creator_id'] : (int)$_SESSION['user_id'];

    // Валидация данных
    if (!preg_match('/^[a-zA-Zа-яА-Я\s]+$/u', $name)) {
        die("Имя не должно содержать числа.");
    }

    if (!preg_match('/^\+375 (29|44|25|33) \d{3}-\d{2}-\d{2}$/', $phone_number)) {
        die("Номер телефона должен быть в формате: +375 YY XXX-XX-XX, где YY - 29, 44, 33 или 25.");
    }

    // Подготовка запроса
    $stmt = $mysqli->prepare("INSERT INTO client (name, phone_number, creator_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    // Привязка параметров и выполнение запроса
    $stmt->bind_param("ssi", $name, $phone_number, $creator_id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }
    $stmt->close();

} elseif ($table == 'order_table') {
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $delivery_date = isset($_POST['delivery_date']) ? $_POST['delivery_date'] : '';
    $delivery_cost = isset($_POST['delivery_cost']) ? (int)$_POST['delivery_cost'] : 0;
    $creator_id = isset($_POST['creator_id']) ? (int)$_POST['creator_id'] : (int)$_SESSION['user_id'];

    // Валидация данных
    if ($client_id <= 0) {
        die("Выберите корректный ID клиента.");
    }

    if (!preg_match('/^(20[0-9]{2})-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $delivery_date)) {
        die("Дата доставки должна быть в формате YYYY-MM-DD и в диапазоне от 2000-01-01 до 2099-12-31.");
    }

    if ($delivery_cost <= 0 || $delivery_cost >= 1000000) {
        die("Стоимость доставки должна быть больше 0 и меньше 1000000.");
    }

    // Подготовка запроса
    $stmt = $mysqli->prepare("INSERT INTO order_table (client_id, delivery_date, delivery_cost, creator_id) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    // Привязка параметров и выполнение запроса
    $stmt->bind_param("isii", $client_id, $delivery_date, $delivery_cost, $creator_id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }
    $stmt->close();

    // Пересчитываем количество заказов клиента
    $count_sql = "SELECT COUNT(*) FROM order_table WHERE client_id = ?";
    $count_stmt = $mysqli->prepare($count_sql);
    if (!$count_stmt) {
        die("Ошибка подготовки запроса для пересчета заказов: " . $mysqli->error);
    }
    $count_stmt->bind_param("i", $client_id);
    if (!$count_stmt->execute()) {
        die("Ошибка выполнения запроса для пересчета заказов: " . $count_stmt->error);
    }
    $count_stmt->bind_result($order_count);
    $count_stmt->fetch();
    $count_stmt->close();

    // Обновляем количество заказов у клиента
    $update_sql = "UPDATE client SET order_count = ? WHERE id = ?";
    $update_stmt = $mysqli->prepare($update_sql);
    if (!$update_stmt) {
        die("Ошибка подготовки запроса для обновления клиента: " . $mysqli->error);
    }
    $update_stmt->bind_param("ii", $order_count, $client_id);
    if (!$update_stmt->execute()) {
        die("Ошибка выполнения запроса для обновления клиента: " . $update_stmt->error);
    }
    $update_stmt->close();

} elseif ($table == 'users'){
    $name = $_POST['user_name'];
    $surname = $_POST['user_surname'];
    $email = $_POST['user_email'];
    $password = password_hash($_POST['user_password'], PASSWORD_DEFAULT);

    // Проверка, существует ли уже пользователь с таким email
    $sql = "SELECT user_id FROM users WHERE user_email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Email уже зарегистрирован.";
    } else {
        // Добавление пользователя в базу данных
        $sql = "INSERT INTO users (user_name, user_surname, user_email, user_password) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssss", $name, $surname, $email, $password);
        if (!$stmt->execute()) {
            $error_message = "Ошибка при регистрации.";
        }
    }
} elseif($table == 'dish_table'){
    $dish_name = $_POST['dish_name'];
    $dish_cost = $_POST['dish_cost'];
    $restoraunt_id = $_POST['restoraunt_id'];
    
    $sql = "INSERT INTO dish_table (dish_name, dish_cost, restoraunt_id) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sii", $dish_name, $dish_cost, $restoraunt_id);
    if (!$stmt->execute()) {
        $error_message = "Ошибка при добавлении блюда.";
    } else{
        echo "Блюдо успешно добавлено!";
    }

    if(!$_SESSION['isAdmin']){
        $message_sql = "INSERT INTO message_for_sysadmin(message) VALUES(?)";
        $restoraunt_name_sql = "SELECT restoraunt_name FROM restoraunt_table where restoraunt_id = $restoraunt_id";
        $restoraunt_name_sql_result = $mysqli->query($restoraunt_name_sql);
        if ($restoraunt_name_sql_result->num_rows > 0) {
            $restoraunt_name = $restoraunt_name_sql_result->fetch_assoc()['restoraunt_name'];
        }
        $message = "Добавлено новое блюдо в ресторане $restoraunt_name";

        $message_stmt = $mysqli->prepare($message_sql);

        if (!$message_stmt) {
            die("Ошибка". $mysqli->error);
        }

        $message_stmt->bind_param("s", $message);
        if (!$message_stmt->execute()) {
            die("Ошибка". $message_stmt->error);
        }
    }
    

}  elseif($table == 'restoraunt_table'){
    $restoraunt_name = $_POST['restoraunt_name'];
    
    $sql = "INSERT INTO restoraunt_table (restoraunt_name) VALUES (?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $restoraunt_name);
    if (!$stmt->execute()) {
        $error_message = "Ошибка при добавлении ресторана.";
    } else{
        echo "Ресторан успешно добавлен!";
    }
} 
//ДОДЕЛАТЬ!
elseif($table == 'new_order_table'){
    $order_text = $_POST['order_text'];
    $order_cost = $_POST['order_cost'];
    $restoraunt_id = $_POST['restoraunt_id'];
    $customer_id = $_POST['customer_id'];
    
    $sql = "INSERT INTO restoraunt_table (restoraunt_name) VALUES (?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $restoraunt_name);
    if (!$stmt->execute()) {
        $error_message = "Ошибка при добавлении ресторана.";
    } else{
        echo "Ресторан успешно добавлен!";
    }
} 
else {
    die("Неизвестная таблица $table.");
}
?>

<form id="returnForm" method="POST" action="index.php">
    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
</form>
<script>
    document.getElementById('returnForm').submit();
</script>
