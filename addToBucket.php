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
  
  $table = isset($_POST['table']) ? $_POST['table'] : '';
  
  if ($id <= 0) {
      die("Некорректный ID записи.");
  }
  
  $restoraunt_id = 0;

  $restoraunt_id_sql = "SELECT restoraunt_id FROM dish_table WHERE dish_id = $id";
  $restoraunt_id_sql_result = $mysqli->query($restoraunt_id_sql);
  $restoraunt_id = $restoraunt_id_sql_result->fetch_assoc()['restoraunt_id'];


  
//   $sql = "INSERT INTO new_orders_table (customer_id, restoraunt_id) VALUES (?, ?)";
  
//   $stmt = $mysqli->prepare($sql);
//     if (!$stmt) {
//         die("Ошибка подготовки запроса: " . $mysqli->error);
//     }

//     $stmt->bind_param("ii", $_SESSION['user_id'], $restoraunt_id);
//     if (!$stmt->execute()) {
//         die("Ошибка выполнения запроса: " . $stmt->error);
//     }
//     $stmt->close();


   $check_sql = "SELECT * FROM ordered_dishes WHERE order_id = ? AND dish_id = ?";
   $stmt = $mysqli->prepare($check_sql);
   if (!$stmt) {
       die("Ошибка подготовки запроса: " . $mysqli->error);
   }

   // Привязка параметров и выполнение запроса
   $stmt->bind_param("ii", $max_order_id, $id);
   if (!$stmt->execute()) {
       die("Ошибка выполнения запроса: " . $stmt->error);
   }

   $result = $stmt->get_result(); // Для использования get_result() требуется драйвер MySQLi с поддержкой

   if ($result->num_rows > 0) {

      $select_sql = "SELECT amount FROM ordered_dishes WHERE order_id = ? AND dish_id = ?";
      $stmt2 = $mysqli->prepare($select_sql);
      if (!$stmt2) {
         die("Ошибка подготовки запроса: " . $mysqli->error);
      }

      // Привязка параметров
      
      $stmt2->bind_param("ii", $max_order_id, $id);

      if (!$stmt2->execute()) {
         die("Ошибка выполнения запроса: " . $stmt2->error);
      }

      // Получение результата
      $result = $stmt2->get_result();
      if ($result->num_rows > 0) {
         $row = $result->fetch_assoc();
         $amount = $row['amount'] + 1; // Присваиваем значение amount в переменную $amount
      } else {
         echo "Запись не найдена.";
      }

      $stmt2->close();

      $update_sql = "UPDATE ordered_dishes SET amount = ? WHERE order_id = ? AND dish_id = ?";
      $update_sql1 = "UPDATE all_ordered_dishes SET amount = ? WHERE order_id = ? AND dish_id = ?";
  
      $stmt1 = $mysqli->prepare($update_sql);
       if (!$stmt1) {
           die("Ошибка подготовки запроса: " . $mysqli->error);
       }
   
       // Привязка параметров и выполнение запроса
       $stmt1->bind_param("iii",$amount, $max_order_id, $id);
       if (!$stmt1->execute()) {
           die("Ошибка выполнения запроса: " . $stmt1->error);
       }
       $stmt1 = $mysqli->prepare($update_sql1);
       if (!$stmt1) {
           die("Ошибка подготовки запроса: " . $mysqli->error);
       }
   
       // Привязка параметров и выполнение запроса
       $stmt1->bind_param("iii",$amount, $max_order_id, $id);
       if (!$stmt1->execute()) {
           die("Ошибка выполнения запроса: " . $stmt1->error);
       }
       $stmt1->close();
   
   } else {
       // Ничего не найдено
      $sql = "INSERT INTO ordered_dishes (order_id, dish_id, customer_id) VALUES (?, ?, ?)";
      $sql1 = "INSERT INTO all_ordered_dishes (order_id, dish_id, customer_id) VALUES (?, ?, ?)";
  
      $stmt1 = $mysqli->prepare($sql);
        if (!$stmt1) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
    
        // Привязка параметров и выполнение запроса
        $stmt1->bind_param("iii", $max_order_id, $id, $_SESSION['user_id']);
        if (!$stmt1->execute()) {
            die("Ошибка выполнения запроса: " . $stmt1->error);
        }

        $stmt1 = $mysqli->prepare($sql1);
        if (!$stmt1) {
            die("Ошибка подготовки запроса: " . $mysqli->error);
        }
    
        // Привязка параметров и выполнение запроса
        $stmt1->bind_param("iii", $max_order_id, $id, $_SESSION['user_id']);
        if (!$stmt1->execute()) {
            die("Ошибка выполнения запроса: " . $stmt1->error);
        }
        $stmt1->close();
    
   }

   $stmt->close();

  ?>
  
  <form id="returnForm" method="POST" action="index.php">
      <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
  </form>
  <script>
      document.getElementById('returnForm').submit();
  </script>

