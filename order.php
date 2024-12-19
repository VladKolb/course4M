<?php
  
    include "db.php";
    session_start();

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

            $sql_max_in_not = 'SELECT order_id FROM new_orders_table WHERE order_id = ' . $max_order_id_client;
            $result_max_in_not = $mysqli->query($sql_max_in_not);
        
            if($max_order_id != 0){
                if($result_max_in_not->num_rows == 0) {
                    if($max_order_id_client != 0){
                        $max_order_id = $max_order_id_client;
                    } else{
                        $max_order_id = $max_order_id + 1;
                    }
                    
                } else{
                    $max_order_id = $max_order_id + 1;
                }
            }

            
    
    } else {
                // Обработка ошибки, если запрос не выполнен
                echo "Ошибка выполнения запроса: " . $mysqli->error;
        }  
        
   } else {
    // Обработка ошибки, если запрос не выполнен
      echo "Ошибка выполнения запроса: " . $mysqli->error;
   }

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
  
  $table = 'new_orders_table';
  
  if ($id <= 0) {
      die("Некорректный ID записи.");
  }
  
  // Собираем информацию о ресторанах и стоимости
// Получение всех уникальных restoraunt_id для данного заказа
$restoraunt_id_sql = "
    SELECT DISTINCT d.restoraunt_id
    FROM ordered_dishes od
    JOIN dish_table d ON od.dish_id = d.dish_id
    WHERE od.order_id = ?
";

$restoraunt_id_stmt = $mysqli->prepare($restoraunt_id_sql);
if (!$restoraunt_id_stmt) {
    die("Ошибка подготовки запроса для получения restoraunt_id: " . $mysqli->error);
}
$restoraunt_id_stmt->bind_param("i", $max_order_id);
if (!$restoraunt_id_stmt->execute()) {
    die("Ошибка выполнения запроса для получения restoraunt_id: " . $restoraunt_id_stmt->error);
}
$restoraunt_id_result = $restoraunt_id_stmt->get_result();

$restoraunt_dish_array = [];
while ($restoraunt_row = $restoraunt_id_result->fetch_assoc()) {
    $restoraunt_id = $restoraunt_row['restoraunt_id'];

    // Получение суммарной стоимости для данного restoraunt_id
    $order_cost_sql = "
        SELECT SUM(od.amount * d.dish_cost) AS total_cost
        FROM ordered_dishes od
        JOIN dish_table d ON od.dish_id = d.dish_id
        WHERE od.order_id = ? AND d.restoraunt_id = ?
    ";

    $order_cost_stmt = $mysqli->prepare($order_cost_sql);
    if (!$order_cost_stmt) {
        die("Ошибка подготовки запроса для расчета стоимости: " . $mysqli->error);
    }
    $order_cost_stmt->bind_param("ii", $max_order_id, $restoraunt_id);
    if (!$order_cost_stmt->execute()) {
        die("Ошибка выполнения запроса для расчета стоимости: " . $order_cost_stmt->error);
    }
    $order_cost_result = $order_cost_stmt->get_result();
    $order_cost_row = $order_cost_result->fetch_assoc();
    $order_cost = $order_cost_row['total_cost'] ?? 0;
    $order_cost_stmt->close();

    // Сохраняем данные в массиве
    $restoraunt_dish_array[$restoraunt_id] = $order_cost;
}

// Вставка данных в таблицу new_orders_table для каждого уникального restoraunt_id
$insert_sql = "
    INSERT INTO new_orders_table (order_id, customer_id, order_cost, restoraunt_id)
    VALUES (?, ?, ?, ?)
";

$insert_stmt = $mysqli->prepare($insert_sql);
if (!$insert_stmt) {
    die("Ошибка подготовки запроса для вставки данных: " . $mysqli->error);
}

foreach ($restoraunt_dish_array as $restoraunt_id => $order_cost) {
    $insert_stmt->bind_param("iiii", $max_order_id, $_SESSION['user_id'], $order_cost, $restoraunt_id);
    if (!$insert_stmt->execute()) {
        die("Ошибка выполнения запроса для вставки данных: " . $insert_stmt->error);
    }

    $message_sql = "INSERT INTO message_for_restoraunts(message, restoraunt_id, order_id) VALUES(?, ?, ?)";
$message = "Зазказ $max_order_id ожидает подтверждения";

$message_stmt = $mysqli->prepare($message_sql);

if (!$message_stmt) {
    die("Ошибка". $mysqli->error);
}

$message_stmt->bind_param("sii", $message, $restoraunt_id, $max_order_id);
if (!$message_stmt->execute()) {
    die("Ошибка". $message_stmt->error);
}
}

$insert_stmt->close();


?>
  
  <form id="returnForm" method="POST" action="index.php">
      <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
  </form>
  <script>
      document.getElementById('returnForm').submit();
  </script>

?>