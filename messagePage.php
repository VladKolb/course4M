<?php include "db.php"; 
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Сообщения</h1>


<form action="index.php" method="get">
   <input type="submit" value="На главную">
</form>

<?php
// SQL-запрос для получения сообщений
$sql = "SELECT id, message, customer_id, order_id, restoraunt_id FROM message_table WHERE customer_id = " . $_SESSION['user_id'];
$result = $mysqli->query($sql);

// Проверка, есть ли результаты
if ($result->num_rows > 0) {
    // Вывод данных для каждой строки
    echo "<table border='1'>
            <tr>
                <th>Сообщение</th>
                <th>Номер заказа</th>
                <th>Ресторан</th>
                <th>Действие</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
         $restoraunt_name_sql = "SELECT restoraunt_name FROM restoraunt_table WHERE restoraunt_id = " . $row["restoraunt_id"];
         $restoraunt_name_query_result = $mysqli->query($restoraunt_name_sql);
         $restoraunt_name = $restoraunt_name_query_result->fetch_assoc()['restoraunt_name'];
        echo "<tr>
                <td>" . $row["message"] . "</td>
                <td>" . $row["order_id"] . "</td>
                <td>" . $restoraunt_name . "</td>
                <td> <form method='POST' action='deleteMessage.php' style='display:inline;'>
                                <input type='hidden' name='id' value=" . $row['id'] . ">
                                <input type='hidden' name='table' value= message_table>
                                <input type='submit' value='Удалить'>
                     </form> 
               </td>
              </tr>
              ";


    }
    echo "</table>";

    

} else {
    echo "Сообщений нет";
}

?>

</body>
</html>
