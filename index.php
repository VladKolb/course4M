<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_name'])) {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];
    $user_surname = $_SESSION['user_surname'];
    $isAdmin = $_SESSION['isAdmin'];
    $user_email = $_SESSION['user_email'];
    $restoraunt_admin = false;
    $restoraunt_id = 0;
    
    
    $restoraunts_array = [];
    $restoraunt_result = $mysqli->query("SELECT restoraunt_name FROM restoraunt_table");
    if (!$restoraunt_result) {
        die("Ошибка выполнения запроса для получения названий ресторанов: " . $mysqli->error);
    }

    while ($restoraunt_row = $restoraunt_result->fetch_assoc()) {
        $restoraunts_array[] = $restoraunt_row['restoraunt_name'];
    }

    $restoraunt_string = implode(', ', $restoraunts_array);
    $isAdmin_string = '';

    if($user_name == "admin"){
        
        foreach($restoraunts_array as $rest){
            if($user_surname == $rest){ 
                $restoraunt_admin = true;
                
                
                // КОПИРОВАТЬ



                $_SESSION['restoraunt_admin'] = $restoraunt_admin;
                $isAdmin_string = (string) $restoraunt_admin; 
                break;
            }
        }

        if($restoraunt_admin){
            echo "<div class='user-info'>Вход выполнен: $user_name $user_surname </div>";
                
            $restoraunt_result = $mysqli->query("SELECT restoraunt_id FROM restoraunt_table WHERE restoraunt_name = '$user_surname'");
            if (!$restoraunt_result) {
                die("Ошибка выполнения запроса для получения названий ресторанов: " . $mysqli->error);
            }
            $restoraunt_id = $restoraunt_result->fetch_assoc()['restoraunt_id'];
            $_SESSION['restoraunt_id'] = $restoraunt_id;
        }
    }else{
        echo "<div class='user-info'>Вход выполнен: $user_name $user_surname, " . ($isAdmin ? "администратор" : "пользователь") . "</div>";
    }
} else {
    header('Location: login.php');
    exit();
}


// Получаем выбранную таблицу из POST или используем таблицу по умолчанию 'client'
$table = isset($_POST['table']) ? $_POST['table'] : 'dish_table';
$edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

// Запрос для получения данных из выбранной таблицы
// 

if($table == "dish_table"){
    if($restoraunt_admin){
        $sql = "SELECT * FROM dish_table WHERE restoraunt_id = " . $restoraunt_id;
    } else {
        $sql = "SELECT * FROM dish_table";
    }
} elseif ($table == "new_orders_table" && $restoraunt_admin) {
    $sql = "SELECT * FROM new_orders_table WHERE restoraunt_id = " . $restoraunt_id;
} else {
    $sql = "SELECT * FROM " . $table; 
}

$result = $mysqli->query($sql);
if (!$result) {
    die("Ошибка выполнения запроса для получения данных из таблицы: " . $mysqli->error);
}

// Если выбрана таблица заказов, нужно получить список клиентов

$users = [];

$user_result = $mysqli->query("SELECT user_id, user_name, user_surname FROM users");
if (!$user_result) {
    die("Ошибка выполнения запроса для получения клиентов: " . $mysqli->error);
}

while ($user_row = $user_result->fetch_assoc()) {
    $users[] = $user_row;
}

$restoraunts = [];
if ($table == 'new_order_table' || $table == 'restoraunt_table' || $table == 'dish_table') {
    $rest_result = $mysqli->query("SELECT restoraunt_id, restoraunt_name FROM restoraunt_table");
    if (!$rest_result) {
        die("Ошибка выполнения запроса для получения клиентов: " . $mysqli->error);
    }

    while ($rest_row = $rest_result->fetch_assoc()) {
        $restoraunts[] = $rest_row;
    }
}

$customers = [];
if ($table == 'new_order_table') {
    $customer_result = $mysqli->query("SELECT customer_id, customer_name FROM new_order_table");
    if (!$rest_result) {
        die("Ошибка выполнения запроса для получения клиентов: " . $mysqli->error);
    }

    while ($customer_row = $customer_result->fetch_assoc()) {
        $customers[] = $customer_row;
    }
}

// Получаем данные для редактирования, если требуется
$edit_row = null;
$dishes = [];
if ($edit_id) {
    if($table == 'users'){
        $edit_sql = "SELECT * FROM " . $table . " WHERE user_id = ?";
    } else if($table == 'dish_table'){
        $edit_sql = "SELECT * FROM " . $table . " WHERE dish_id = ?";
    } else if($table == 'new_order_table'){
        if(!$restoraunt_admin){
            $edit_sql = "SELECT * FROM " . $table . " WHERE order_id = ?";
            $edit_dishes_sql = "SELECT * FROM ordered_dishes WHERE order_id = ?";
            $edit_dishes_sql_result = $mysqli->query($edit_dishes_sql);
            while($dishes_row = $edit_dishes_sql_result->fetch_assoc()){
                $dishes[] = $dishes_row;
            } 
        }
    } else if($table == 'restoraunt_table'){
        $edit_sql = "SELECT * FROM " . $table . " WHERE restoraunt_id = ?";
    }

    $edit_stmt = $mysqli->prepare($edit_sql);
    if (!$edit_stmt) {
        die("Ошибка подготовки запроса для редактирования: " . $mysqli->error);
    }

    $edit_stmt->bind_param("i", $edit_id);
    if (!$edit_stmt->execute()) {
        die("Ошибка выполнения запроса для редактирования: " . $edit_stmt->error);
    }

    $edit_result = $edit_stmt->get_result();
    if (!$edit_result) {
        die("Ошибка получения результата для редактирования: " . $edit_stmt->error);
    }

    $edit_row = $edit_result->fetch_assoc();
}

// Поиск
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';
$column = isset($_POST['column']) ? $_POST['column'] : '';
$search_type = isset($_POST['search_type']) ? $_POST['search_type'] : '';




if ($search_query) {
    // if ($table == 'client') {
    //     $columns = ['name' => 'Имя', 'order_count' => 'Количество заказов', 'phone_number' => 'Номер телефона'];
    // } else {
    //     $columns = ['client_id' => 'ID клиента', 'delivery_date' => 'Дата доставки', 'delivery_cost' => 'Стоимость доставки'];
    // }

    $columns = [];
    
    // Получаем список колонок из базы данных для указанной таблицы
    $sql = "SHOW COLUMNS FROM " . $table;
    $result = $mysqli->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row['Field']; // Для каждой колонки устанавливаем ключ и значение как имя колонки
        }
    }

    if (!array_key_exists($column, $columns)) {
        die("Некорректное поле для поиска.");
    }

    $sql = "SELECT * FROM $table WHERE $column LIKE ?";

    // Определяем шаблон поиска
    if ($search_query) {
        // Определяем список столбцов для каждой таблицы
        $columns = [];
    
    // Получаем список колонок из базы данных для указанной таблицы
        $sql = "SHOW COLUMNS FROM " . $table;
        $result = $mysqli->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[$row['Field']] = $row['Field']; // Для каждой колонки устанавливаем ключ и значение как имя колонки
            }
        }
    
        // Проверка валидности выбранного столбца
        if (!array_key_exists($column, $columns)) {
            die("Некорректное поле для поиска.");
        }
    
        // Определение SQL-запроса для поиска с символом '%', но отображаем чистое значение
        if($table == "new_orders_table" && $restoraunt_admin){
            $sql = "SELECT * FROM $table WHERE restoraunt_id = $restoraunt_id AND $column LIKE ?";
        } else {
            $sql = "SELECT * FROM $table WHERE $column LIKE ?";
        }
        
    
        // Определяем шаблон поиска для SQL-запроса
        if ($search_type == 'start') {
            $search_query_sql = $search_query . '%'; // Поиск по началу
        } elseif ($search_type == 'end') {
            $search_query_sql = '%' . $search_query; // Поиск по концу
        } else {
            $search_query_sql = '%' . $search_query . '%'; // Обычный поиск
        }
    
        // Подготовка и выполнение запроса
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            die("Ошибка подготовки запроса для поиска: " . $mysqli->error);
        }
    
        $stmt->bind_param("s", $search_query_sql);
        if (!$stmt->execute()) {
            die("Ошибка выполнения запроса для поиска: " . $stmt->error);
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            die("Ошибка получения результата поиска: " . $stmt->error);
        }
        $search_result = $result;
    
    } else {
        // Если поиска нет, просто выбираем все записи
        // if ($table == 'client') {
        //     $sql = "SELECT * FROM client";
        // } else {
        //     $sql = "SELECT * FROM order_table";
        // }

        $sql = "SELECT * FROM " . $table;
    
        $result = $mysqli->query($sql);
        if (!$result) {
            die("Ошибка выполнения запроса для получения данных: " . $mysqli->error);
        }
        $search_result = $result;
    }
}

?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery App</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-photo {
            width: 1000px; 
            height: 150px; 
            object-fit: cover; 
            border-radius: 50%; 
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
    

        .button-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .button-container a {
            text-decoration: none;
            padding: 5px 10px;
            border: 2px solid #5cb85c;
            border-radius: 5px;
            background-color: #5cb85c;
            color: white;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .button-container a:hover {
            background-color: white;
            color: #007BFF;
        }

        .button-container a:active {
            background-color: #0056b3;
            border-color: #0056b3;
        }
    </style>
    
    <script>
        function validateForm() {
            var valid = true;
            var numberFields = document.querySelectorAll("input[type='number']");
            var phoneFields = document.querySelectorAll("input[name='phone_number']");
            var nameFields = document.querySelectorAll("input[name='name']");

            // Validate number fields
            numberFields.forEach(function(field) {
                var value = field.value;
                if (!/^\d+$/.test(value) || value <= 0 || value >= 1000000) {
                    alert("Введите корректное число больше 0 и меньше 1000000.");
                    valid = false;
                }
            });

            // Validate phone numbers
            phoneFields.forEach(function(field) {
                var value = field.value;
                var phoneRegex = /^\+375 (29|44|25|33) \d{3}-\d{2}-\d{2}$/;
                if (!phoneRegex.test(value)) {
                    alert("Введите номер телефона в формате: +375 YY XXX-XX-XX, где YY - 29, 44, 33 или 25.");
                    valid = false;
                }
            });

            // Validate name fields
            nameFields.forEach(function(field) {
                var value = field.value;
                if (/[\d]/.test(value)) {
                    alert("Имя не должно содержать числа.");
                    valid = false;
                }
            });

            return valid;
        }

        function validateFormForSearch() {
            var valid = true;
            var numberFields = document.querySelectorAll("input[type='number']");
            var nameFields = document.querySelectorAll("input[name='name']");

            // Validate number fields
            numberFields.forEach(function(field) {
                var value = field.value;
                if (!/^\d+$/.test(value) || value <= 0 || value >= 1000000) {
                    alert("Введите корректное число больше 0 и меньше 1000000.");
                    valid = false;
                }
            });

            // Validate name fields
            nameFields.forEach(function(field) {
                var value = field.value;
                if (/[\d]/.test(value)) {
                    alert("Имя не должно содержать числа.");
                    valid = false;
                }
            });

            return valid;
        }
        function validateFormForSearch() {
            var valid = true;
            var numberFields = document.querySelectorAll("input[type='number']");
            var nameFields = document.querySelectorAll("input[name='name']");

            // Validate name fields
            nameFields.forEach(function(field) {
                var value = field.value;
                if (/[\d]/.test(value)) {
                    alert("Имя не должно содержать числа.");
                    valid = false;
                }
            });

            return valid;
        }    
    </script>
    
</head>
<body>

<?php
if (!$restoraunt_admin && !$isAdmin) {
$sql_is_seen="SELECT id, message from message_table where isSeen=0 and customer_id = $user_id";
$sql_is_seen_result=$mysqli->query($sql_is_seen);
if ($sql_is_seen_result && $sql_is_seen_result->num_rows > 0) {
    while ($row = $sql_is_seen_result->fetch_assoc()) {
        // Выводим alert для каждого сообщения
        echo "<script>
            alert('{$row['message']}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_seen.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('id={$row['id']}&table=message_table');
        </script>";       
    }
}

$sql_is_seen="SELECT id, message from change_status_message where isSeen=false and user_id = $user_id";
$sql_is_seen_result=$mysqli->query($sql_is_seen);
if ($sql_is_seen_result && $sql_is_seen_result->num_rows > 0) {
    while ($row = $sql_is_seen_result->fetch_assoc()) {
        // Выводим alert для каждого сообщения
        echo "<script>
            alert('{$row['message']}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_seen.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('id={$row['id']}&table=change_status_message');
        </script>";        
        }
    }

$sql_is_seen = "SELECT message, user_id from message_coef where isRead=false and user_id = $user_id";
$sql_is_seen_result=$mysqli->query($sql_is_seen);
if ($sql_is_seen_result && $sql_is_seen_result->num_rows > 0) {
    while ($row = $sql_is_seen_result->fetch_assoc()) {
        echo "<script>
            alert('{$row['message']}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_seen.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('id={$row['user_id']}&table=message_coef');
        </script>";        
        }
    }

} else if ($restoraunt_admin && $_SESSION['isSeen'] == false) {
    $sql_is_seen="SELECT id, message from message_for_restoraunts where isAccept=0 and restoraunt_id = $restoraunt_id";
    $sql_is_seen_result=$mysqli->query($sql_is_seen);
    if ($sql_is_seen_result && $sql_is_seen_result->num_rows > 0) {
        while ($row = $sql_is_seen_result->fetch_assoc()) {
            // Выводим alert для каждого сообщения
            echo "<script>
                alert('{$row['message']}');
            </script>";
        }
    }
    $_SESSION['isSeen'] = true;
    
    $sql_is_seen="SELECT id, message from change_status_message where isSeen=false and user_id = $user_id";
$sql_is_seen_result=$mysqli->query($sql_is_seen);
if ($sql_is_seen_result && $sql_is_seen_result->num_rows > 0) {
    while ($row = $sql_is_seen_result->fetch_assoc()) {
        // Выводим alert для каждого сообщения
        echo "<script>
            alert('{$row['message']}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_seen.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('id={$row['id']}&table=change_status_message');
        </script>";        
        }
    }
} elseif($isAdmin){
    $sql_is_seen="SELECT id, message from message_for_sysadmin where isSeen=false";
$sql_is_seen_result=$mysqli->query($sql_is_seen);
if ($sql_is_seen_result && $sql_is_seen_result->num_rows > 0) {
    while ($row = $sql_is_seen_result->fetch_assoc()) {
        // Выводим alert для каждого сообщения
        echo "<script>
            alert('{$row['message']}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_seen.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('id={$row['id']}&table=message_for_sysadmin');
      </script>";
     
    }
}
$sql_is_seen="SELECT id, message from change_status_message where isSeen=false and user_id = $user_id";
$sql_is_seen_result=$mysqli->query($sql_is_seen);
if ($sql_is_seen_result && $sql_is_seen_result->num_rows > 0) {
    while ($row = $sql_is_seen_result->fetch_assoc()) {
        // Выводим alert для каждого сообщения
        echo "<script>
            alert('{$row['message']}');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_seen.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('id={$row['id']}&table=change_status_message');
        </script>";        
        }
    }
}
?>


<div class="button-container">
<?php
       $sql_max = "SELECT MAX(order_id) AS max_order_id FROM ordered_dishes";
       $result_max = $mysqli->query($sql_max);
    
        if ($result_max) {
        // Извлекаем строку результата
            $row_max = $result_max->fetch_assoc();
            // Присваиваем значение максимального order_id в переменную
            $max_order_id = isset($row_max['max_order_id']) ? $row_max['max_order_id'] : 0;
            
            $sql_max = 'SELECT MAX(order_id) AS max_order_id FROM ordered_dishes WHERE customer_id = ' . $_SESSION['user_id'];
            $result_max = $mysqli->query($sql_max);
    
            if ($result_max) {
            // Извлекаем строку результата
                $row_max = $result_max->fetch_assoc();
            // Присваиваем значение максимального order_id в переменную
                $max_order_id_client = isset($row_max['max_order_id']) ? $row_max['max_order_id'] : 0;

                if($max_order_id_client > 0) {

                $sql_max_in_not = 'SELECT order_id FROM new_orders_table WHERE order_id = ' . $max_order_id_client;
                $result_max_in_not = $mysqli->query($sql_max_in_not);
    
                if($result_max_in_not->num_rows == 0) {?>
                    
                    <a href="bucket.php">Корзина</a>
                <?php 
        
                } 
            }
        
        }else {
                    // Обработка ошибки, если запрос не выполнен
                    echo "Ошибка выполнения запроса: " . $mysqli->error;
            }  
            
       } else {
        // Обработка ошибки, если запрос не выполнен
          echo "Ошибка выполнения запроса: " . $mysqli->error;
       }
?>
    


<a href="profile.php">Профиль</a>

<?php if(!$restoraunt_admin):?>
    <a href="messagePage.php">Сообщения</a>
<?php endif;?>

<?php if($isAdmin):?>
    <a href="rangePage.php">Ранжирование</a>
<?php endif;?>

<?php if($isAdmin || $restoraunt_admin):?>
    <a href="analitics.php">Аналитика</a>
<?php endif; ?>

<a href="logout.php">Выйти</a>
</div>

<div class="container">
    <h2>Выберите таблицу для отображения:</h2>
    <form method="POST" action="" onsubmit="return validateFormForSearch()">
        <select name="table" onchange="this.form.submit()">
            <option value="dish_table" <?= $table == 'dish_table' ? 'selected' : '' ?>>Меню</option>
            <option value="new_orders_table" <?= $table == 'new_orders_table' ? 'selected' : '' ?>>Заказы</option>
            <?php if($isAdmin) :?>
                <option value="users" <?= $table == 'users' ? 'selected' : '' ?>>Пользователи</option>
                <option value="restoraunt_table" <?= $table == 'restoraunt_table' ? 'selected' : '' ?>>Рестораны</option>
            <?php endif ?>
        </select>
    </form>

    <?php if ($table): ?>
    <h2>Поиск:</h2>
    <form method="POST" action="" onsubmit="return validateFormForSearch()">
        <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">

        <label for="column">Выберите столбец:</label>
        <select name="column" id="column" onchange="handleColumnChange()">
            <?php
            // Проверяем таблицу и соответствующие столбцы
            if ($table == 'dish_table') {
                if (!$restoraunt_admin) {
                    $columns = ['dish_name' => 'Название блюда', 'dish_cost' => 'Цена', 'restoraunt_name' => 'Ресторан'];
                } else {
                    $columns = ['dish_name' => 'Название блюда', 'dish_cost' => 'Цена'];
                }
            } elseif ($table == 'new_orders_table' && ($restoraunt_admin || $isAdmin)) {
                $columns = ['customer_name' => 'Имя заказчика', 'order_cost' => 'Стоимость заказа'];
            } 
            elseif ($table == 'new_orders_table' && (!$restoraunt_admin || !$isAdmin)){
                $columns = ['order_cost' => 'Стоимость заказа'];
            } elseif ($table == 'users') {
                $columns = ['user_id' => 'ID пользователя', 'user_name' => 'Имя пользователя', 'user_surname' => 'Фамилия пользователя', 'user_password' => 'Пароль пользователя'];
            } elseif ($table == 'restoraunt_table') {
                $columns = ['restoraunt_name' => 'Название ресторана'];
            }

            // Выводим опции для выбора столбца
            foreach ($columns as $col => $label): ?>
                <option value="<?= htmlspecialchars($col) ?>" <?= $col == $column ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="search_type">Тип поиска:</label>
        <select name="search_type" id="search_type">
            <option value="normal" <?= $search_type == 'normal' ? 'selected' : '' ?>>Обычный поиск</option>
            <option value="start" <?= $search_type == 'start' ? 'selected' : '' ?>>Поиск по началу</option>
            <option value="end" <?= $search_type == 'end' ? 'selected' : '' ?>>Поиск по концу</option>
        </select>

        <div id="search_query_container">
            <?php
            // Если выбран столбец "restoraunt_name" в таблице dish_table
            if ($table == 'dish_table' && isset($column) && $column == 'restoraunt_name'): ?>
                <label for="search_query">Выберите ресторан:</label>
                <select name="search_query" id="search_query">
                    <option value="" disabled selected>Выберите ресторан</option>
                    <?php
                    // Запрос для получения списка ресторанов из таблицы restoraunt_table
                    $restoraunt_query = "SELECT restoraunt_name FROM restoraunt_table";
                    $result_restoraunts = $mysqli->query($restoraunt_query);

                    // Выводим список ресторанов
                    if ($result_restoraunts) {
                        while ($row = $result_restoraunts->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['restoraunt_name']) ?>" <?= $search_query == $row['restoraunt_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['restoraunt_name']) ?>
                            </option>
                        <?php endwhile;
                    }
                    ?>
                </select>
            <?php else: ?>
                <input type="text" name="search_query" id="search_query_field" value="<?= htmlspecialchars($search_query) ?>" maxlength="20" placeholder="Введите запрос для поиска">
            <?php endif; ?>
        </div>

        <input type="submit" value="Найти">
    </form>
<?php endif; ?>

<script>
    function handleColumnChange() {
        const columnSelect = document.getElementById('column');
        const searchFieldContainer = document.getElementById('search_query_container');
        
        // Сбрасываем содержимое контейнера для поиска
        searchFieldContainer.innerHTML = '';

        // Если выбран "Ресторан", показываем выпадающее меню
        if (columnSelect.value === 'restoraunt_name') {
            searchFieldContainer.innerHTML = `
                <label for="search_query">Выберите ресторан:</label>
                <select name="search_query" id="search_query">
                    <option value="" disabled selected>Выберите ресторан</option>
                    <?php
                    // Запрос для получения списка ресторанов из таблицы restoraunt_table
                    $restoraunt_query = "SELECT restoraunt_name FROM restoraunt_table";
                    $result_restoraunts = $mysqli->query($restoraunt_query);

                    // Выводим список ресторанов
                    if ($result_restoraunts) {
                        while ($row = $result_restoraunts->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['restoraunt_name']) ?>"><?= htmlspecialchars($row['restoraunt_name']) ?></option>
                        <?php endwhile;
                    }
                    ?>
                </select>
            `;
        } else {
            searchFieldContainer.innerHTML = `
                <input type="text" name="search_query" id="search_query_field" placeholder="Введите запрос для поиска">
            `;
        }
    }
</script>


    
    
    <table id="sortableTable">
    <thead>
    <tr>
        <?php if ($table == 'dish_table'): ?>
            <h2>Меню:</h2>
            <th>Название блюда</th>
            <th>Стоимость блюда</th>
            <th>Где готовят</th>
            <th>Действие</th>
            <?php if ($restoraunt_admin):?>
            <th>Популярнось блюда</th>
            <?php endif; ?>
        <?php elseif ($table == 'new_orders_table'): ?>
            <h2>Заказы:</h2>
            <th>Номер заказа</th>
            <th>Содержание заказа</th>
            <th>Ресторан</th>
            <?php if($restoraunt_admin || $isAdmin):?>
                <th>Имя заказчика</th>
            <?php endif?>
            <th>Стоимость заказа</th>
            <th>Статус заказа</th>
            <!-- NEWWW -->



            <th>Статус доставки</th>
            <th>Действие</th>
        <?php elseif ($table == 'users'): ?>
            <h2>Пользователи:</h2>
            <th>ID</th>
            <th>Имя</th>
            <th>Фамилия</th>
            <th>Email</th>
            <th>Пароль</th>
            <th>Действие</th>
            <th>Фото</th>
        <?php elseif ($table == 'restoraunt_table'):?>
            <h2>Рестораны:</h2>
            <th>Имя ресторана</th>
            <th>Действие</th>
        <?php endif; ?>

    </tr>
</thead>
        <?php if($table == "new_orders_table" && !$restoraunt_admin && !$isAdmin): ?>
        <?php $sql = "SELECT * FROM new_orders_table WHERE customer_id = " . $user_id;
        $result = $mysqli->query($sql);
        if (!$result) {
            die("Ошибка выполнения запроса для получения данных из таблицы: " . $mysqli->error);    
        }
        if(isset($search_result)){
            $result = $search_result;
        }
        ?>
        <?php if($result->num_rows == 0):?>
            <h2>Таблица пуста</h2>
        <?php else: ?>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                    <?php foreach ($row as $column => $cell): ?>
                        <?php if($column !== "customer_id"):?>
                            <?php if($column == "order_id"): ?>
                                <td>
                                    <?= htmlspecialchars($cell)?>
                                </td>
                                <?php $ordered_dishes = [];
                                $total_restoraunt_id;
                                $dishes_query = "SELECT dish_id FROM ordered_dishes WHERE order_id = " . $cell;
                                $dishes_query_result = $mysqli->query($dishes_query);
                                while ($dish_id = $dishes_query_result->fetch_assoc()){
                                    $ordered_dishes_query = "SELECT dish_name, restoraunt_id 
                                    FROM dish_table WHERE dish_id = " . $dish_id['dish_id'];
                                    $ordered_dishes_query_result = $mysqli->query($ordered_dishes_query);
                                    $dish = $ordered_dishes_query_result->fetch_assoc();
                                    if($dish['restoraunt_id'] == $row["restoraunt_id"]){
                                        $ordered_dishes[] = $dish['dish_name'];
                                    }
                                }
                                ?>
                                <td>
                                    <?= implode(' , ', $ordered_dishes)?>
                                </td>
                            <?php elseif($column == "restoraunt_id"):?>
                                <?php $restoraunt_name_query = "SELECT restoraunt_name FROM restoraunt_table WHERE restoraunt_id = " . $cell;
                                $restoraunt_name_query_result = $mysqli->query($restoraunt_name_query);
                                $restoraunt_name = $restoraunt_name_query_result->fetch_assoc()['restoraunt_name'];
                                ?>
                                <td>
                                    <?= htmlspecialchars($restoraunt_name) ?>
                                </td>
                            <?php elseif($column == "order_cost"):?>
                                <td>
                                    <?= htmlspecialchars($cell) ?>
                                </td>
                            <?php elseif($column == "isAccept"):?>
                                <?php if($cell == 0):?>
                                    <td>
                                        Ожидание подтверждения
                                    </td>
                                <?php elseif($cell == -1):?>
                                    <td>
                                        Отклонён
                                    </td>
                                <?php elseif($cell == 1):?>
                                    <td>
                                        Подтверждён
                                    </td>
                                <?php endif; ?>
                            <?php elseif($column == "added_at"):?>
                                <?php if($row['isAccept'] == 0):?>
                                    <td>
                                        Ожидание подтверждения
                                    </td>
                                <?php elseif($row['isAccept'] == -1):?>
                                    <td>
                                        Отклонён
                                    </td>
                                <?php elseif($row['isAccept'] == 1):?>
                                    <?php 
                                        date_default_timezone_set('Europe/Moscow');
                                        $query_time = "SELECT added_at FROM new_orders_table WHERE order_id = " . $row['order_id'];
                                        $result_time = $mysqli->query($query_time);
                                
                                        $message = "";
                                        if ($result_time->num_rows > 0) {
                                            $time_row = $result_time->fetch_assoc();
                                            $add_at = strtotime($time_row['added_at']); // Преобразуем время из базы в формат UNIX
                                            $current_time = time(); // Текущее время
                                
                                            // Рассчитываем разницу в минутах
                                            $time_diff = ($current_time - $add_at) / 60;
                                
                                            if ($time_diff < 20) {
                                                $remaining_time = 20 - floor($time_diff);
                                                $message = "Доставка через $remaining_time минут!";
                                            } else {
                                                $message = "Заказ доставлен";
                                            }
                                        } else {
                                            $message = "Время заказа не найдено";
                                        }
                                    ?>
                                    
                                    <td>
                                        <?= htmlspecialchars($message)?>
                                    </td>
                                <?php endif; ?>
                            <?php endif;?>
                        <?php endif;?>
                    <?php endforeach;?>
                    <td>
                        <form method="POST" action="delete.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['order_id']) ?>">
                            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                            <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($row['restoraunt_id']) ?>">
                            <input type="hidden" name="customer_id" value="<?= htmlspecialchars($row['customer_id']) ?>">
                            <input type="submit" value="Удалить">
                        </form>
                            <!-- Форма для редактирования записи -->
                        <!-- <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($row['order_id']) ?>">
                            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                            <input type="submit" value="Изменить">
                        </form> -->
                    </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <?php endif; ?>
        <?php elseif ($table == "new_orders_table" && $isAdmin):?>
            <?php $sql = "SELECT * FROM new_orders_table";
            $result = $mysqli->query($sql);
            if (!$result) {
                die("Ошибка выполнения запроса для получения данных из таблицы: " . $mysqli->error);    
            }
            if(isset($search_result)){
                $result = $search_result;
            }
            ?>
            <?php if($result->num_rows == 0):?>
                <h2>Таблица пуста</h2>
            <?php else: ?>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                        <?php foreach ($row as $column => $cell): ?>
                            <?php if($column == "customer_id"):
                                $user_query = "SELECT user_name FROM users WHERE user_id = " . $cell;
                                $user_query_result = $mysqli->query($user_query);
                                $customer_name = $user_query_result->fetch_assoc()['user_name'];
                                ?>
                                    <td>
                                        <?= htmlspecialchars($customer_name)?>
                                    </td>
                                <?php elseif($column == "order_id"): ?>
                                    <td>
                                        <?= htmlspecialchars($cell)?>
                                    </td>
                                    <?php $ordered_dishes = [];
                                    $total_restoraunt_id;
                                    $dishes_query = "SELECT dish_id FROM ordered_dishes WHERE order_id = " . $cell;
                                    $dishes_query_result = $mysqli->query($dishes_query);
                                    while ($dish_id = $dishes_query_result->fetch_assoc()){
                                        $ordered_dishes_query = "SELECT dish_name, restoraunt_id 
                                        FROM dish_table WHERE dish_id = " . $dish_id['dish_id'];
                                        $ordered_dishes_query_result = $mysqli->query($ordered_dishes_query);
                                        $dish = $ordered_dishes_query_result->fetch_assoc();
                                        
                                        if($dish['restoraunt_id'] == $row["restoraunt_id"]){
                                            $ordered_dishes[] = $dish['dish_name'];
                                        }
                                    }
                                    ?>
                                    <td>
                                        <?= implode(' , ', $ordered_dishes)?>
                                    </td>
                                <?php elseif($column == "restoraunt_id"):?>
                                    <?php $restoraunt_name_query = "SELECT restoraunt_name FROM restoraunt_table WHERE restoraunt_id = " . $cell;
                                    $restoraunt_name_query_result = $mysqli->query($restoraunt_name_query);
                                    $restoraunt_name = $restoraunt_name_query_result->fetch_assoc()['restoraunt_name'];
                                    ?>
                                    <td>
                                        <?= htmlspecialchars($restoraunt_name) ?>
                                    </td>
                                <?php elseif($column == "order_cost"):?>
                                    <td>
                                        <?= htmlspecialchars($cell) ?>
                                    </td>
                                <?php elseif($column == "isAccept"):?>
                                    <?php if($cell == 0):?>
                                        <td>
                                            Ожидание подтверждения
                                        </td>
                                    <?php elseif($cell == -1):?>
                                        <td>
                                            Отклонён
                                        </td>
                                    <?php elseif($cell == 1):?>
                                        <td>
                                            Подтверждён
                                        </td>
                                    <?php endif; ?>
                                    <?php elseif($column == "added_at"):?>
                                <?php if($row['isAccept'] == 0):?>
                                    <td>
                                        Ожидание подтверждения
                                    </td>
                                <?php elseif($row['isAccept'] == -1):?>
                                    <td>
                                        Отклонён
                                    </td>
                                <?php elseif($row['isAccept'] == 1):?>
                                    <?php 
                                        date_default_timezone_set('Europe/Moscow');
                                        $query_time = "SELECT added_at FROM new_orders_table WHERE order_id = " . $row['order_id'];
                                        $result_time = $mysqli->query($query_time);
                                
                                        $message = "";
                                        if ($result_time->num_rows > 0) {
                                            $time_row = $result_time->fetch_assoc();
                                            $add_at = strtotime($time_row['added_at']); // Преобразуем время из базы в формат UNIX
                                            $current_time = time(); // Текущее время
                                
                                            // Рассчитываем разницу в минутах
                                            $time_diff = ($current_time - $add_at) / 60;
                                
                                            if ($time_diff < 20) {
                                                $remaining_time = 20 - floor($time_diff);
                                                $message = "Доставка через $remaining_time минут!";
                                            } else {
                                                $message = "Заказ доставлен";
                                            }
                                        } else {
                                            $message = "Время заказа не найдено";
                                        }
                                    ?>
                                    
                                    <td>
                                        <?= htmlspecialchars($message)?>
                                    </td>
                                <?php endif; ?>
                                <?php endif;?>
                            
                        <?php endforeach;?>
                        <td>
                            <form method="POST" action="delete.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($row['restoraunt_id']) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($row['customer_id']) ?>">
                                <input type="submit" value="Удалить">
                            </form>
                            <?php if($row['isAccept'] == 0):?>
                            <form method="POST" action="accept.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($row['restoraunt_id']) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($row['customer_id']) ?>">
                                <input type="submit" value="Подтвердить">
                            </form>
                            <form method="POST" action="decline.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($row['restoraunt_id']) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($row['customer_id']) ?>">
                                <input type="submit" value="Отклонить">
                            </form>
                            <?php endif; ?>
                                <!-- Форма для редактирования записи -->
                            <!-- <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="submit" value="Изменить">
                            </form> -->
                        </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <?php endif; ?>
        <?php elseif($table == "new_orders_table" && $restoraunt_admin):?>
            <?php $sql = "SELECT * FROM new_orders_table WHERE restoraunt_id = " . $restoraunt_id;
            $result = $mysqli->query($sql);
            if (!$result) {
                die("Ошибка выполнения запроса для получения данных из таблицы: " . $mysqli->error);    
            }
            if(isset($search_result)){
                $result = $search_result;
            }
            ?>
            <?php if($result->num_rows == 0):?>
                <h2>Таблица пуста</h2>
            <?php else: ?>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                        <?php foreach ($row as $column => $cell): ?>
                            <?php if($column == "customer_id"):
                                $user_query = "SELECT user_name FROM users WHERE user_id = " . $cell;
                                $user_query_result = $mysqli->query($user_query);
                                $customer_name = $user_query_result->fetch_assoc()['user_name'];
                                ?>
                                    <td>
                                        <?= htmlspecialchars($customer_name)?>
                                    </td>
                                <?php elseif($column == "order_id"): ?>
                                    <td>
                                        <?= htmlspecialchars($cell)?>
                                    </td>
                                    <?php $ordered_dishes = [];
                                    $total_restoraunt_id;
                                    $dishes_query = "SELECT dish_id FROM ordered_dishes WHERE order_id = " . $cell;
                                    $dishes_query_result = $mysqli->query($dishes_query);
                                    while ($dish_id = $dishes_query_result->fetch_assoc()){
                                        $ordered_dishes_query = "SELECT dish_name, restoraunt_id 
                                        FROM dish_table WHERE dish_id = " . $dish_id['dish_id'];
                                        $ordered_dishes_query_result = $mysqli->query($ordered_dishes_query);
                                        $dish = $ordered_dishes_query_result->fetch_assoc();
                                        if($dish['restoraunt_id'] == $row["restoraunt_id"]){
                                            $ordered_dishes[] = $dish['dish_name'];
                                        }
                                    }
                                    ?>
                                    <td>
                                        <?= implode(' , ', $ordered_dishes)?>
                                    </td>
                                <?php elseif($column == "restoraunt_id"):?>
                                    <?php $restoraunt_name_query = "SELECT restoraunt_name FROM restoraunt_table WHERE restoraunt_id = " . $cell;
                                    $restoraunt_name_query_result = $mysqli->query($restoraunt_name_query);
                                    $restoraunt_name = $restoraunt_name_query_result->fetch_assoc()['restoraunt_name'];
                                    ?>
                                    <td>
                                        <?= htmlspecialchars($restoraunt_name) ?>
                                    </td>
                                <?php elseif($column == "order_cost"):?>
                                    <td>
                                        <?= htmlspecialchars($cell) ?>
                                    </td>
                                <?php elseif($column == "isAccept"):?>
                                    <?php if($cell == 0):?>
                                        <td>
                                            Ожидание подтверждения
                                        </td>
                                    <?php elseif($cell == -1):?>
                                        <td>
                                            Отклонён
                                        </td>
                                    <?php elseif($cell == 1):?>
                                        <td>
                                            Подтверждён
                                        </td>
                                    <?php endif; ?>
                                    <?php elseif($column == "added_at"):?>
                                <?php if($row['isAccept'] == 0):?>
                                    <td>
                                        Ожидание подтверждения
                                    </td>
                                <?php elseif($row['isAccept'] == -1):?>
                                    <td>
                                        Отклонён
                                    </td>
                                <?php elseif($row['isAccept'] == 1):?>
                                    <?php 
                                    date_default_timezone_set('Europe/Moscow');
                                        $query_time = "SELECT added_at FROM new_orders_table WHERE order_id = " . $row['order_id'];
                                        $result_time = $mysqli->query($query_time);
                                
                                        $message = "";
                                        if ($result_time->num_rows > 0) {
                                            $time_row = $result_time->fetch_assoc();
                                            $add_at = strtotime($time_row['added_at']); // Преобразуем время из базы в формат UNIX
                                            $current_time = time(); // Текущее время
                                
                                            // Рассчитываем разницу в минутах
                                            $time_diff = ($current_time - $add_at) / 60;
                                
                                            if ($time_diff < 20) {
                                                $remaining_time = 20 - floor($time_diff);
                                                $message = "Доставка через $remaining_time минут!";
                                            } else {
                                                $message = "Заказ доставлен";
                                            }
                                        } else {
                                            $message = "Время заказа не найдено";
                                        }
                                    ?>
                                    
                                    <td>
                                        <?= htmlspecialchars($message)?>
                                    </td>
                                <?php endif; ?>
                                <?php endif;?>
                            
                        <?php endforeach;?>
                        <td>
                            <form method="POST" action="delete.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($row['restoraunt_id']) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($row['customer_id']) ?>">
                                <input type="submit" value="Удалить">
                            </form>
                            <?php if($row['isAccept'] == 0):?>
                            <form method="POST" action="accept.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($row['restoraunt_id']) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($row['customer_id']) ?>">
                                <input type="submit" value="Подтвердить">
                            </form>
                            <form method="POST" action="decline.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($row['restoraunt_id']) ?>">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($row['customer_id']) ?>">
                                <input type="submit" value="Отклонить">
                            </form>
                            <?php endif; ?>
                                <!-- Форма для редактирования записи -->
                            <!-- <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($row['order_id']) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="submit" value="Изменить">
                            </form> -->
                        </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <?php endif; ?>
        <?php elseif ($table !== "new_orders_table"): ?>
            <?php if($result->num_rows == 0):?>
                <h2>Таблица пуста</h2>
            <?php else: ?>    
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($row as $column => $cell): ?>
                            <?php if($column !== 'dish_id' && $column !== 'restoraunt_id' && $column !== 'customer_id'):?>
                                <?php if(!$isAdmin): ?>
                                    <?php if ($column !== 'isAdmin' && $column !== 'creator_id'): // Проверка, если столбец не 'isAdmin' ?>
                                        <td>
                                        <?php
                                            if ($column === 'user_password') {
                        // Расшифровываем пароль, если это столбец 'user_password'
                                                $cell = substr($cell, 0, 10) . '****';
                                            }
                                        ?>
                                        <?= htmlspecialchars($cell) ?>
                                        </td>
                                    <?php endif; ?>
                                <?php else:?>
                                    <?php if ($column !== 'isAdmin'):?>
                                        <td>
                                        <?php
                                            if ($column === 'user_password') {
                        // Расшифровываем пароль, если это столбец 'user_password'
                                                $cell = substr($cell, 0, 10) . '****';
                                            }
                                    ?>
                                    <?= htmlspecialchars($cell) ?>
                                    </td>
                                    <?php endif; ?>
                                <?php endif;?>
                            <?php endif;?>
                        <?php endforeach; ?>
                        <td>
                            
                            <?php if($isAdmin || $restoraunt_admin):?>
                                <?php if($restoraunt_admin): ?>
                            <!-- Форма для удаления записи -->
                            <form method="POST" action="delete.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars(isset($row['dish_id']) ? $row['dish_id'] : (isset($row['order_id']) ? $row['order_id'] : (isset($row['restoraunt_id']) ? $row['restoraunt_id'] : $row['user_id'])))?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="submit" value="Удалить">
                            </form>
                            <!-- Форма для редактирования записи -->
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="edit_id" value="<?= htmlspecialchars(isset($row['dish_id']) ? $row['dish_id'] : (isset($row['order_id']) ? $row['order_id'] : (isset($row['restoraunt_id']) ? $row['restoraunt_id'] : $row['user_id']))) ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="submit" value="Изменить">
                            </form>
                                <?php elseif($isAdmin):?>
                                    <form method="POST" action="delete.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars(isset($row['dish_id']) ? $row['dish_id'] : (isset($row['order_id']) ? $row['order_id'] : (isset($row['restoraunt_id']) ? $row['restoraunt_id'] : $row['user_id'])))?>">
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                        <input type="submit" value="Удалить">
                                    </form>
                                        <!-- Форма для редактирования записи -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="edit_id" value="<?= htmlspecialchars(isset($row['dish_id']) ? $row['dish_id'] : (isset($row['order_id']) ? $row['order_id'] : (isset($row['restoraunt_id']) ? $row['restoraunt_id'] : $row['user_id']))) ?>">
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                        <input type="submit" value="Изменить">
                                    </form>
                                    <?php if($table == "dish_table"):?>
                                        <form method="POST" action="addToBucket.php" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars(isset($row['dish_id']) ? $row['dish_id'] : (isset($row['order_id']) ? $row['order_id'] : (isset($row['restoraunt_id']) ? $row['restoraunt_id'] : $row['user_id']))) ?>">
                                            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                            <input type="submit" value="Добавить в корзину">
                                        </form>
                                    <?php endif;?>
                                <?php endif;?>
                            <?php elseif($table == "dish_table" && !$restoraunt_admin): ?>
                                <form method="POST" action="addToBucket.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars(isset($row['dish_id']) ? $row['dish_id'] : (isset($row['order_id']) ? $row['order_id'] : (isset($row['restoraunt_id']) ? $row['restoraunt_id'] : $row['user_id']))) ?>">
                                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                    <input type="submit" value="Добавить в корзину">
                                </form>
                            <?php endif; ?>
                            <!-- Форма с чекбоксом -->
                            <?php if(isset($row['user_name'])): ?>
                                <?php if($row['user_name'] != $user_name): ?>
                                    <form method="POST" action="changeStatus.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars(isset($row['id']) ? $row['id'] : $row['user_id']) ?>">
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">

                                        <?php if (isset($row['isAdmin'])): ?>
                                            <label style="display: inline-flex; align-items: center;">
                                                <input type="checkbox" name="status" class="status-checkbox" 
                                                value="<?= $row['isAdmin'] ? '1' : '0' ?>"
                                                <?= $row['isAdmin'] ? 'checked' : '' ?>
                                                data-user-id="<?= htmlspecialchars(isset($row['id']) ? $row['id'] : $row['user_id']) ?>">
                                                <span class="status-label"><?= $row['isAdmin'] ? 'Админ' : 'Пользователь' ?></span>
                                            </label>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        
                            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                            <script>
                            $(document).ready(function() {
                                // Отслеживаем изменение состояния чекбоксов с классом 'status-checkbox'
                                $('.status-checkbox').off('change').on('change', function() {
                                    var isChecked = $(this).is(':checked');  // Проверка, активен ли чекбокс
                                    var userId = $(this).data('user-id');    // Получение ID пользователя
                                    var label = $(this).next('.status-label');  // Найти соответствующий элемент <span>

                                    // Отправляем AJAX-запрос на сервер для обновления статуса
                                    $.ajax({
                                        url: 'changeStatus.php',  // Путь к серверному скрипту
                                        type: 'POST',
                                        data: {
                                            id: userId,            // Передаем ID пользователя
                                            status: isChecked ? 1 : 0  // Передаем новое состояние (1 для админа, 0 для обычного пользователя)
                                        },
                                        success: function(response) {
                                            console.log('Статус успешно обновлен: ' + response);
                                            
                                            // Меняем текст в <span> в зависимости от состояния чекбокса
                                            if (isChecked) {
                                                label.text('Админ');
                                                // Отправляем сообщение "Вы теперь администратор"
                                                sendStatusChangeMessage(userId, 'Вы теперь администратор');
                                            } else {
                                                label.text('Пользователь');
                                                // Отправляем сообщение "Вы теперь пользователь"
                                                sendStatusChangeMessage(userId, 'Вы теперь пользователь');
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Ошибка обновления статуса: ' + error);
                                        }
                                    });
                                });

                                function sendStatusChangeMessage(userId, message) {
                                $.ajax({
                                    url: 'saveStatusMessage.php',  // Скрипт для сохранения сообщения в базу данных
                                    type: 'POST',
                                    data: {
                                        user_id: userId,
                                        message: message
                                    },
                                    success: function(response) {
                                        console.log('Сообщение сохранено: ' + response);
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Ошибка сохранения сообщения: ' + error);
                                    }
                                });
                            }
                            });
                            </script>

                        </td>
                        <?php if($table == "dish_table" && !$restoraunt_admin): ?>
                            <?php
                                $all_ordered_dishes_count_for_customer_sql = "SELECT 
                                    COUNT(*) AS all_ordered_dishes_count,
                                    COUNT(IF(n.isAccept = 1, 1, NULL)) AS accepted_orders_count,
                                    COUNT(IF(n.isAccept = 0, 1, NULL)) AS waiting_orders_count,
                                    COUNT(IF(n.isAccept = -1, 1, NULL)) AS declined_orders_count
                                FROM 
                                    all_ordered_dishes a
                                JOIN 
                                    new_orders_table n ON a.order_id = n.order_id
                                WHERE a.customer_id = " . $user_id;
                                $all_ordered_dishes_count_for_customer_result = $mysqli->query($all_ordered_dishes_count_for_customer_sql);
                                $all_ordered_dishes_count_for_customer_arr = $all_ordered_dishes_count_for_customer_result->fetch_assoc();
                                $all_ordered_dishes_count_for_customer = $all_ordered_dishes_count_for_customer_arr['all_ordered_dishes_count'];
                                $all_accepted_orders_count = $all_ordered_dishes_count_for_customer_arr['accepted_orders_count'];
                                $all_waiting_orders_count = $all_ordered_dishes_count_for_customer_arr['waiting_orders_count'];
                                $all_declined_orders_count = $all_ordered_dishes_count_for_customer_arr['declined_orders_count'];


                                $ordered_dish_count_for_customer_sql =  "SELECT count(*) as ordered_dishes_count, 
                                    COUNT(IF(n.isAccept = 1, 1, NULL)) AS accepted_orders_count,
                                    COUNT(IF(n.isAccept = 0, 1, NULL)) AS waiting_orders_count,
                                    COUNT(IF(n.isAccept = -1, 1, NULL)) AS declined_orders_count
                                FROM all_ordered_dishes a
                                JOIN 
                                    new_orders_table n ON a.order_id = n.order_id
                                where a.dish_id = " . $row['dish_id']. "
                                AND a.customer_id = " . $user_id;
                                 
                                $ordered_dish_count_for_customer_result = $mysqli->query($ordered_dish_count_for_customer_sql);
                                $ordered_dish_count_for_customer_arr = $ordered_dish_count_for_customer_result->fetch_assoc();
                                $ordered_dish_count_for_customer = $ordered_dish_count_for_customer_arr['ordered_dishes_count'];
                                $ordered_dishes_count_for_customer = $all_ordered_dishes_count_for_customer_arr['all_ordered_dishes_count'];
                                $accepted_orders_count = $ordered_dish_count_for_customer_arr['accepted_orders_count'];
                                $waiting_orders_count = $ordered_dish_count_for_customer_arr['waiting_orders_count'];
                                $declined_orders_count = $ordered_dish_count_for_customer_arr['declined_orders_count'];

                                $coef_sql = "SELECT name, coef FROM range_coef";
                                $coef_sql_result = $mysqli->query($coef_sql);
                                
                                $coef_array = array();
                                
                                while ($row_coef = $coef_sql_result->fetch_assoc()) {
                                    $coef_array[$row_coef['name']] = $row_coef['coef'];
                                }

                                $ordered_dish_count_for_restoraunt_sql = "SELECT count(*) as ordered_dishes_count, 
                                    COUNT(IF(n.isAccept = 1, 1, NULL)) AS accepted_orders_count,
                                    COUNT(IF(n.isAccept = 0, 1, NULL)) AS waiting_orders_count,
                                    COUNT(IF(n.isAccept = -1, 1, NULL)) AS declined_orders_count
                                FROM all_ordered_dishes a
                                JOIN 
                                    new_orders_table n ON a.order_id = n.order_id 
                                WHERE a.dish_id IN (SELECT dish_id FROM dish_table WHERE restoraunt_id = " . $row["restoraunt_id"] . ")";
                                // . " AND order_id IN (SELECT order_id FROM new_orders_table)";
                                $ordered_dish_count_for_restoraunt_result = $mysqli->query($ordered_dish_count_for_restoraunt_sql);
                                $ordered_dish_count_for_restoraunt_arr = $ordered_dish_count_for_restoraunt_result->fetch_assoc();
                                $ordered_dish_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr["ordered_dishes_count"];
                                $accepted_orders_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr['accepted_orders_count'];
                                $waiting_orders_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr['waiting_orders_count'];
                                $declined_orders_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr['declined_orders_count'];


                                if($coef_array['accept_coef'] == 0 && $coef_array['waiting_coef'] == 0 && $coef_array['decline_coef'] == 0){
                                    $procent = 0;
                                } else{
                                    $dish_count_for_customer = $ordered_dish_count_for_customer * ($accepted_orders_count * $coef_array['accept_coef'] + 
                                    $waiting_orders_count * $coef_array['waiting_coef'] + $declined_orders_count * $coef_array['decline_coef']);

                                    $all_dish_count_for_customer = $all_ordered_dishes_count_for_customer * ($all_accepted_orders_count * $coef_array['accept_coef'] + 
                                    $all_waiting_orders_count * $coef_array['waiting_coef'] + $all_declined_orders_count * $coef_array['decline_coef']);

                                    $dish_count_for_restoraunt = $ordered_dish_count_for_restoraunt * ($accepted_orders_count_for_restoraunt * $coef_array['accept_coef'] + 
                                    $waiting_orders_count_for_restoraunt * $coef_array['waiting_coef'] + $declined_orders_count_for_restoraunt * $coef_array['decline_coef']);

                                    if($all_dish_count_for_customer != 0){
                                        $procent = ($dish_count_for_customer / $all_dish_count_for_customer * $coef_array["order_coef"]) + ($dish_count_for_restoraunt / $all_dish_count_for_customer * $coef_array['rest_coef']);
                                        $procent = round($procent, 2) * 100;
                                    } else{
                                        $procent = 0;
                                    }
                                }
                                
                                ?>
                            <!-- <td>
                                <?= htmlspecialchars($procent) ?> %
                            </td> -->
                        <?php elseif($table == "dish_table" && $restoraunt_admin): ?>
                            <?php
                                $coef_sql = "SELECT name, coef FROM range_coef";
                                $coef_sql_result = $mysqli->query($coef_sql);
                                
                                $coef_array = array();
                                
                                while ($row_coef = $coef_sql_result->fetch_assoc()) {
                                    $coef_array[$row_coef['name']] = $row_coef['coef'];
                                }


                                 $all_ordered_dishes_count_for_customer_sql = "SELECT 
                                 COUNT(*) AS all_ordered_dishes_count,
                                 COUNT(IF(n.isAccept = 1, 1, NULL)) AS accepted_orders_count,
                                 COUNT(IF(n.isAccept = 0, 1, NULL)) AS waiting_orders_count,
                                 COUNT(IF(n.isAccept = -1, 1, NULL)) AS declined_orders_count
                             FROM 
                                 all_ordered_dishes a
                             LEFT JOIN 
                                 new_orders_table n ON a.order_id = n.order_id
                             WHERE a.dish_id IN (SELECT dish_id FROM dish_table WHERE restoraunt_id = " . $restoraunt_id . ")";
                                $all_ordered_dishes_count_for_customer_result = $mysqli->query($all_ordered_dishes_count_for_customer_sql);
                                $all_ordered_dishes_count_for_customer_arr = $all_ordered_dishes_count_for_customer_result->fetch_assoc();
                                $all_ordered_dishes_count_for_customer = $all_ordered_dishes_count_for_customer_arr['all_ordered_dishes_count'];
                                $all_accepted_orders_count = $all_ordered_dishes_count_for_customer_arr['accepted_orders_count'];
                                $all_waiting_orders_count = $all_ordered_dishes_count_for_customer_arr['waiting_orders_count'];
                                $all_declined_orders_count = $all_ordered_dishes_count_for_customer_arr['declined_orders_count'];

                                $all_dish_count_for_customer = $all_ordered_dishes_count_for_customer * ($all_accepted_orders_count * $coef_array['accept_coef'] + 
                                $all_waiting_orders_count * $coef_array['waiting_coef'] + $all_declined_orders_count * $coef_array['decline_coef']);


                                $ordered_dish_count_for_restoraunt_sql = "SELECT count(*) as ordered_dishes_count, 
                                    COUNT(IF(n.isAccept = 1, 1, NULL)) AS accepted_orders_count,
                                    COUNT(IF(n.isAccept = 0, 1, NULL)) AS waiting_orders_count,
                                    COUNT(IF(n.isAccept = -1, 1, NULL)) AS declined_orders_count
                                FROM all_ordered_dishes a
                                LEFT JOIN 
                                    new_orders_table n ON a.order_id = n.order_id 
                                WHERE a.dish_id IN (SELECT dish_id FROM dish_table WHERE restoraunt_id = " . $restoraunt_id . ") AND dish_id = ". $row['dish_id'];
                                // . " AND order_id IN (SELECT order_id FROM new_orders_table)";
                                $ordered_dish_count_for_restoraunt_result = $mysqli->query($ordered_dish_count_for_restoraunt_sql);
                                $ordered_dish_count_for_restoraunt_arr = $ordered_dish_count_for_restoraunt_result->fetch_assoc();
                                $ordered_dish_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr["ordered_dishes_count"];
                                $accepted_orders_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr['accepted_orders_count'];
                                $waiting_orders_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr['waiting_orders_count'];
                                $declined_orders_count_for_restoraunt = $ordered_dish_count_for_restoraunt_arr['declined_orders_count'];
                                //die(htmlspecialchars($ordered_dish_count_for_customer) . " " . htmlspecialchars($all_ordered_dishes_count_for_customer));

                                if($coef_array['accept_coef'] == 0 && $coef_array['waiting_coef'] == 0 && $coef_array['decline_coef'] == 0){
                                    $procent = 0;
                                } else {
                                    $dish_count_for_restoraunt = $ordered_dish_count_for_restoraunt * ($accepted_orders_count_for_restoraunt * $coef_array['accept_coef'] + 
                                    $waiting_orders_count_for_restoraunt * $coef_array['waiting_coef'] + $declined_orders_count_for_restoraunt * $coef_array['decline_coef']);
    
                                    if($dish_count_for_restoraunt != 0){
                                        $procent = ($dish_count_for_restoraunt / $all_dish_count_for_customer /* * $coef_array['rest_coef']*/);
                                        $procent = round($procent, 2) * 100;
                                    } else{
                                        $procent = 0;
                                    }
                                }
                                ?>
                            <td>
                                <?= htmlspecialchars($procent) ?> %
                            </td>
                        <?php endif;?>
                        <?php if ($table == "users"):?>
                            <?php
                               $pictureData = null; 
                               $stmt = $mysqli->prepare("SELECT picture_data FROM picture_table WHERE user_id = ?");
                               $stmt->bind_param("i", $row['user_id']);
                               $stmt->execute();
                               $stmt->bind_result($pictureData);
                               $stmt->fetch();
                               $stmt->close();

                               if ($pictureData != null) {
                                    $base64Image = base64_encode($pictureData);
                                    $imageType = 'image/jpg'; 
                                    $imageSrc = "data:$imageType;base64,$base64Image"; 
                                } else {
                                    $defaultImagePath = 'resources/default.jpg';
    
                                    if (file_exists($defaultImagePath)) {
                                        $defaultImageData = file_get_contents($defaultImagePath);
                                        $base64Image = base64_encode($defaultImageData);
                                        $imageType = mime_content_type($defaultImagePath);
                                        $imageSrc = "data:$imageType;base64,$base64Image";
                                    } else {
                                        $imageSrc = '';
                                    }
                                }
                            
                                ?>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Фотография профиля" class="profile-photo">
                                    </td>
                        <?php endif;?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <?php endif; ?>
        <?php endif; ?>
    </table>

    <?php if($table == "dish_table"):?>
        <script>
            function sortTableByLastColumn() {
            const table = document.getElementById("sortableTable");
            const rows = Array.from(table.rows).slice(1); // Пропускаем заголовок
            const lastColIndex = rows[0].cells.length - 1; // Индекс последнего столбца

            // Сортировка строк по значению последнего столбца (как число)
            rows.sort((rowA, rowB) => {
                const a = parseFloat(rowA.cells[lastColIndex].innerText.replace('%', '').trim());
                const b = parseFloat(rowB.cells[lastColIndex].innerText.replace('%', '').trim());
                return b - a; // Сортируем по убыванию
            });

            // Вставляем отсортированные строки обратно в таблицу
            rows.forEach(row => table.tBodies[0].appendChild(row));
            }

            // Сортируем таблицу сразу при загрузке страницы    
            document.addEventListener("DOMContentLoaded", sortTableByLastColumn);
        </script>
    <?php endif; ?>

    <?php if ($edit_row): ?>
        <h2>Изменить запись:</h2>
        <form method="POST" action="update.php">
            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars(isset($edit_row['dish_id']) ? $edit_row['dish_id'] : (isset($edit_row['order_id']) ? $edit_row['order_id'] : (isset($edit_row['restoraunt_id']) ? $edit_row['restoraunt_id'] : $edit_row['user_id']))) ?>">

            <?php if ($table == 'dish_table'): ?>
                Название блюда: <input type="text" name="dish_name" maxlength="20" value="<?= htmlspecialchars($edit_row['dish_name']) ?>" required><br>
                Стоимость блюда: <input type="number" name="dish_cost" value="<?= htmlspecialchars($edit_row['dish_cost']) ?>" required><br>
            <?php elseif ($table == 'new_order_table'): ?>
                <?php if(!$restoraunt_admin):?>
                    Содержание заказа: <input type="text" name="order_text" value="<?= $dishes ?>" required><br>
                    Стоимость заказа: <input type="number" name="order_cost" value="<?= htmlspecialchars($edit_row['delivery_cost']) ?>" min="1" max="1000000" required><br>
                    Ресторан: <select name="restoraunt_id" required>
                        <?php foreach ($restoraunts as $restoraunts_row): ?>
                            <option value="<?= htmlspecialchars($restoraunts_row['restoraunt_id']) ?>" <?= $restoraunts_row['restoraunt_id'] == $edit_row['restoraunt_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($restoraunts_row['restoraunt_id'] . ' - ' . $restoraunts_row['restoraunt_name']) ?>
                            </option>
                        <?php endforeach; ?>
                </select><br>
                <?php else: ?>
                    Содержание заказа: <input type="text" name="order_text" value="<?= $dishes ?>" required><br>
                    Имя заказчика: <select name="customer_id" required>
                        <?php foreach ($customers as $customers_row): ?>
                            <option value="<?= htmlspecialchars($customers_row['customer_id']) ?>" <?= $customers_row['customer_id'] == $edit_row['customer_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customers_row['customer_id'] . ' - ' . $customers_row['customer_name']) ?>
                            </option>
                        <?php endforeach; ?>
                </select><br>
                    Стоимость заказа: <input type="number" name="order_cost" value="<?= htmlspecialchars($edit_row['delivery_cost']) ?>" min="1" max="1000000" required><br>
                <?php endif; ?>
            <?php elseif($table == 'restoraunt_table'):?>
                Название ресторана: <input type="text" name="restoraunt_name" maxlength="20" value="<?= htmlspecialchars($edit_row['restoraunt_name']) ?>" required><br>
            <?php elseif ($table == 'users'): ?>
                <input type="hidden" name="check_id"value="<?= htmlspecialchars($edit_row['user_id'])?>">
                Имя: <input type="text" name="user_name" maxlength="20" value="<?= htmlspecialchars($edit_row['user_name']) ?>" required><br>
                Фамилия: <input type="text" name="user_surname" maxlength="20" value="<?= htmlspecialchars($edit_row['user_surname']) ?>" required><br>
                Email: <input type="text" name="user_email" value="<?= htmlspecialchars($edit_row['user_email']) ?>" required><br>
                Пароль: <input type="text" name="user_password" value="<?= htmlspecialchars($edit_row['user_password']) ?>" readonly required><br>
            <?php endif; ?>

            <input type="submit" value="Применить изменения">
        </form>
    <?php endif; ?>
    <?php if($table != "new_orders_table" && ($table == "dish_table" && ($restoraunt_admin || $isAdmin))):?>
        <h2>Добавить запись в таблицу <?= htmlspecialchars($table) ?>:</h2>
        <form method="POST" action="add.php" onsubmit="return validateForm()">
            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">

            <?php if ($table == 'dish_table' && $isAdmin): ?>
                    Название блюда: <input type="text" name="dish_name" maxlength="20" required><br>
                    Стоимость блюда: <input type="number" name="dish_cost" min ="0" max="1000" required><br>
                    Где готовят: <select name="restoraunt_id" required>
                            <?php foreach ($restoraunts as $restoraunts_row): ?>
                                <option>
                                    <?= htmlspecialchars($restoraunts_row['restoraunt_id'] . ' - ' . $restoraunts_row['restoraunt_name']) ?>
                                </option>
                            <?php endforeach; ?>
                    </select><br>
            <?php elseif($table == "dish_table" && $restoraunt_admin):?>
                Название блюда: <input type="text" name="dish_name" maxlength="20" required><br>
                Стоимость блюда: <input type="number" name="dish_cost" min="0" max="100000" required><br>
                Где готовят: <input type="text" name="restoraunt_name" value="<?= $user_surname ?>" required><br>
                <input type="hidden" name="restoraunt_id" value="<?= htmlspecialchars($restoraunt_id) ?>">                        
            <?php elseif($table == 'restoraunt_table'):?>
                    Название ресторана: <input type="text" name="restoraunt_name" maxlength="20" required><br>
            <?php elseif ($table == 'users'): ?>
                    Имя: <input type="text" name="user_name" maxlength="20" required><br>
                    Фамилия: <input type="text" name="user_surname" maxlength="20" required><br>
                    Email: <input type="text" name="user_email" required><br>
                    Пароль: <input type="text" name="user_password" required><br>
            <?php endif; ?>

            <input type="submit" value="Добавить запись">
        </form>
    <?php endif;?>
</div>


</body>
</html>