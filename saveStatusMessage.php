<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Подключение к базе данных

    // Получение данных из POST-запроса
    $user_id = $_POST['user_id'];
    $message = $_POST['message'];

    // SQL-запрос для вставки сообщения
    $message_sql = "INSERT INTO change_status_message (message, user_id) VALUES (?, ?)";

    // Подготовка и выполнение запроса
    $message_stmt = $mysqli->prepare($message_sql);

    if (!$message_stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    // Привязка параметров
    $message_stmt->bind_param("si", $message, $user_id);

    // Выполнение запроса
    if (!$message_stmt->execute()) {
        die("Ошибка выполнения запроса: " . $message_stmt->error);
    }

    echo "Сообщение успешно сохранено";

    // Закрытие запроса
    $message_stmt->close();
    $mysqli->close();
}
?>
