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

if ($id <= 0) {
    die("Некорректный ID записи.");
}

$dish_id = $_POST["dish_id"];

$sql = "DELETE FROM ordered_dishes WHERE order_id = ? AND dish_id = ?";
$sql1 = "DELETE FROM all_ordered_dishes WHERE order_id = ? AND dish_id = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
   die("Ошибка подготовки запроса: " . $mysqli->error);
}

$stmt->bind_param("ii", $id, $dish_id);
if (!$stmt->execute()) {
   die("Ошибка выполнения запроса: " . $stmt->error);
}

$stmt = $mysqli->prepare($sql1);
if (!$stmt) {
   die("Ошибка подготовки запроса: " . $mysqli->error);
}

$stmt->bind_param("ii", $id, $dish_id);
if (!$stmt->execute()) {
   die("Ошибка выполнения запроса: " . $stmt->error);
}

$count = 0;

$sql = "SELECT count(*) as dish_counter FROM ordered_dishes WHERE order_id = $id";
$sql_result = $mysqli->query($sql);
$count = $sql_result->fetch_assoc()["dish_counter"];

$stmt->close();
 

?>


<?php if($count > 0):?>
   <form id="returnForm" method="POST" action="bucket.php">
      <input type="hidden" name="table" value="<?= htmlspecialchars("ordered_dishes") ?>">
   </form>
   <script>
      document.getElementById('returnForm').submit();
   </script>
<?php else:?>
   <form id="returnForm" method="POST" action="index.php">
      <input type="hidden" name="table" value="<?= htmlspecialchars("dish_table") ?>">
   </form>
   <script>
      document.getElementById('returnForm').submit();
   </script>
<?php endif;?>