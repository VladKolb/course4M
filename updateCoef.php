<?php
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['coef'])) {
    $id = $_POST['id'];
    $coef = $_POST['coef'];
    
    // Обновление коэффициента
    $sql = "UPDATE range_coef SET coef = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("di", $coef, $id);
    
    if ($stmt->execute()) {
        echo "Коэффициент обновлен!";
    } else {
        echo "Ошибка при обновлении коэффициента.";
    }

    $delete_sql = "DELETE FROM message_coef";
    $mysqli->query($delete_sql);

    $message = "Система ранжирования изменена администратором";

    $user_sql = "SELECT user_id FROM users";
$user_sql_result = $mysqli->query($user_sql);

if ($user_sql_result) {
    $users_id = $user_sql_result->fetch_all(MYSQLI_ASSOC); 
    foreach ($users_id as $user) {
        $user_id = $user['user_id'];

        $insert_sql = "INSERT INTO message_coef(message, user_id) VALUES ('$message', $user_id)";
        $mysqli -> query($insert_sql);
    }
} else {
    echo "Ошибка при выполнении запроса: " . $mysqli->error;
}

    


    // Перенаправление назад на страницу с таблицей
    header("Location: rangePage.php");
    exit();
}
?>
