<?php 
include("db.php");

// Инициализация переменных
$editId = null;
$editCoef = "";

// Если передан id для редактирования
if (isset($_POST['edit'])) {
    $editId = $_POST['id'];
    
    // Получаем текущий коэффициент для редактирования
    $sql = "SELECT coef FROM range_coef WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $editCoef = $row["coef"];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranges</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Ранги</h1>

<form action="index.php" method="get">
   <input type="submit" value="На главную">
</form>

<?php
// SQL-запрос для получения сообщений
$sql = "SELECT id, name, coef FROM range_coef";
$result = $mysqli->query($sql);

// Проверка, есть ли результаты
if ($result->num_rows > 0) {
    // Вывод данных для каждой строки
    echo "<table border='1'>
            <tr>
                <th>Коэффициент</th>
                <th>Значение</th>
                <th>Действие</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["name"] . "</td>
                <td>" . $row["coef"] . "</td>
                <td>
                    <form method='POST' action='' style='display:inline;'>
                        <input type='hidden' name='id' value=" . $row['id'] . ">
                        <input type='submit' name='edit' value='Изменить'>
                    </form> 
               </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "Сообщений нет";
}
?>

<?php if ($editId): ?>
<!-- Форма для редактирования коэффициента -->
<h2>Редактировать коэффициент</h2>
<form method="POST" action="updateCoef.php">
    <input type="hidden" name="id" value="<?php echo $editId; ?>">
    <label for="coef">Коэффициент:</label>
    <input type="number" name="coef" value="<?php echo $editCoef; ?>" min="0" max="1" step="0.01">
    <input type="submit" value="Применить изменения">
</form>

<?php endif; ?>

</body>
</html>
