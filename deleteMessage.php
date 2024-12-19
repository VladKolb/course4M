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

if($table == 'message_table'){
    $sql = "DELETE FROM message_table WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $stmt->close();
} 
else {
    die("Неизвестная таблица.");
}

?>

<form id="returnForm" method="POST" action="messagePage.php">
    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
</form>
<script>
    document.getElementById('returnForm').submit();
</script>
