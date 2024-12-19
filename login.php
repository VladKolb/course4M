<?php
include 'db.php';
session_start();

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['user_email'];
    $password = $_POST['user_password'];

    $sql = "SELECT user_id, user_name, user_surname, user_email, user_password, isAdmin FROM users WHERE user_email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    

    if ($result->num_rows > 0) {
        if (password_verify($password, $user['user_password'])) {
            $_SESSION['max_order_id'] = 0;
            $_SESSION['user_email'] = $user['user_email'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['user_surname'] = $user['user_surname'];
            $_SESSION['isAdmin'] = $user['isAdmin'];
            $_SESSION['isSeen'] = false;
            
            header('Location: index.php');
        } else {
            $error_message = "Неверный пароль.";
        }
    } else {
        $error_message = "Пользователь с таким email не найден.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="loginStyle.css">
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
    <form method="POST" action="login.php">
        <h2>Вход в систему</h2>
        <div>
            <label for="email">Email:</label>
            <input type="email" name="user_email" id="email" required>
        </div>
        <div>
            <label for="password">Пароль:</label>
            <input type="password" name="user_password" id="password" required>
        </div>
        <div class="buttons">
            <input type="submit" value="Войти">
            <a href="register.php"><button type="button">Зарегистрироваться</button></a>
        </div>

        <?php if ($error_message): ?>
            <div id="error-message" class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </form>
</body>
</html>
