<?php

// db.php
$host = 'localhost'; // Хост
$dbname = 'delivery_db1'; // Имя базы данных
$username = 'root'; // Имя пользователя базы данных
$password = ''; // Пароль базы данных

// Создаем подключение
try{
    $mysqli = new mysqli($host, $username, $password, $dbname);
} catch(mysqli_sql_exception $e) {
    echo "Ошибка бд: ".$e->getMessage();
    exit;
}


// Проверяем подключение
if ($mysqli->connect_error) {
    die('Ошибка подключения: ' . $mysqli->connect_error);
}
?>
