<?php
include 'db.php';
// Проверяем, был ли отправлен POST-запрос
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем переданные данные
    $user_id = $_POST['id'];
    $isAdmin = $_POST['status'];

    // Обновляем статус isAdmin для пользователя
    $stmt = $mysqli->prepare('UPDATE users SET isAdmin = ? WHERE user_id = ?');
    $stmt->bind_param('ii', $isAdmin, $user_id);
    
    // Выполняем запрос
    if ($stmt->execute()) {
        echo 'Статус обновлен успешно';
    } else {
        echo 'Ошибка при обновлении';
    }

    // if($isAdmin == 0){
    //     $message_sql = "INSERT INTO change_status_message(message, user_id) VALUES(?, ?)";
    //     $message = "Вы теперь пользователь";

    //     $message_stmt = $mysqli->prepare($message_sql);

    //     if (!$message_stmt) {
    //         die("Ошибка". $mysqli->error);
    //     }

    //     $message_stmt->bind_param("si", $message, $user_id);
    //     if (!$message_stmt->execute()) {
    //         die("Ошибка". $message_stmt->error);
    //     }
    // } elseif($isAdmin == 1){
    //     $message_sql = "INSERT INTO change_status_message(message, user_id) VALUES(?, ?)";
    //     $message = "Вы теперь администратор";

    //     $message_stmt = $mysqli->prepare($message_sql);

    //     if (!$message_stmt) {
    //         die("Ошибка". $mysqli->error);
    //     }

    //     $message_stmt->bind_param("si", $message, $user_id);
    //     if (!$message_stmt->execute()) {
    //         die("Ошибка". $message_stmt->error);
    //     }
    // }
    

    // Закрываем соединение
    $stmt->close();
    $mysqli->close();
}
?>

<form id="returnForm" method="POST" action="index.php">
    <input type="hidden" name="table" value="<?= htmlspecialchars('users') ?>">
</form>
<script>
    document.getElementById('returnForm').submit();
</script>