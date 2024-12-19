<?php
include 'db.php';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        $error_message = "Email уже зарегистрирован.";
    } else {
        // Добавление пользователя в базу данных
        $sql = "INSERT INTO users (user_name, user_surname, user_email, user_password) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssss", $name, $surname, $email, $password);
        if ($stmt->execute()) {
            header('Location: login.php');
        } else {
            $error_message = "Ошибка при регистрации.";
        }

        $message_sql = "INSERT INTO message_for_sysadmin(message) VALUES(?)";
        $message = "Пользователь $name $surname зарегистрирован";

        $message_stmt = $mysqli->prepare($message_sql);

        if (!$message_stmt) {
            die("Ошибка". $mysqli->error);
        }

        $message_stmt->bind_param("s", $message);
        if (!$message_stmt->execute()) {
            die("Ошибка". $message_stmt->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="registerStyle.css">
    <script>
        window.onload = function() {
            let errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                setTimeout(function() {
                    errorMessage.classList.add('hidden');
                }, 5000); // 5 секунд
            }
        }
    </script>
</head>
<body>
    <form method="POST" action="register.php">
        <h2>Регистрация</h2>
        <div>
            <label for="name">Имя:</label>
            <input type="text" name="user_name" id="name" maxlength="30" required>
        </div>
        <div>
            <label for="surname">Фамилия:</label>
            <input type="text" name="user_surname" id="surname" maxlength="30" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" name="user_email" id="email" maxlength="30" required>
        </div>
        <div>
            <label for="password">Пароль:</label>
            <input type="password" name="user_password" id="password" maxlength="20" required>
        </div>
        <div class="buttons">
            <input type="submit" value="Зарегистрироваться">
            <a href="login.php"><button type="button">Войти</button></a>
        </div>

        <?php if ($error_message): ?>
            <div id="error-message" class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </form>
</body>
</html>
