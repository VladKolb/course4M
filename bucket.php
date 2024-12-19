<?php
// Подключение к базе данных
include "db.php";
session_start();

$encryptionKey = "hardpassword";

function decrypt($data, $key){
  return openssl_decrypt($data, "AES-128-ECB", $key);
}

// Получаем order_id (можно получить через GET или POST)
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
$order_id = $max_order_id;

// SQL-запрос для получения данных из таблиц
$sql = "
    SELECT 
        dt.dish_id,
        dt.dish_name, 
        od.dish_cost, 
        od.amount, 
        dt.restoraunt_name 
    FROM ordered_dishes AS od 
    JOIN dish_table AS dt ON od.dish_id = dt.dish_id 
    WHERE od.order_id = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $mysqli->error);
}

// Привязка параметров и выполнение запроса
$stmt->bind_param("i", $order_id);
if (!$stmt->execute()) {
    die("Ошибка выполнения запроса: " . $stmt->error);
}

// Получение результата
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Данные не найдены для указанного order_id.";
    exit;
}

// HTML-разметка и стили
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>Корзина заказа №<?= htmlspecialchars($order_id) ?></h2>
    <h2></h2>

    <!-- Кнопка на главную -->
    <form action="index.php" method="get" onsubmit="return checkRememberCookie()">
        <input type="submit" value="На главную">
    </form>

    <!-- Таблица с данными о заказанных блюдах -->
    <table>
        <thead>
            <tr>
                <th>Название блюда</th>
                <th>Стоимость за 1 шт.</th>
                <th>Количество заказанного</th>
                <th>Где готовят</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['dish_name']) ?></td>
                <td><?= htmlspecialchars($row['dish_cost']) ?></td>
                <td><?= htmlspecialchars($row['amount']) ?></td>
                <td><?= htmlspecialchars($row['restoraunt_name']) ?></td>
                <td> 
                    <form method='POST' action='deleteOrder.php' style='display:inline;'>
                                <input type='hidden' name='id' value="<?= htmlspecialchars($order_id)?>">
                                <input type='hidden' name='dish_id' value="<?= htmlspecialchars($row['dish_id'])?>">
                                <input type='submit' value='Удалить'>
                     </form> 
               </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

<form action="" method="POST">
  <?php $user_id = $_SESSION['user_id']; ?>
  
  <label for="address">Адрес:</label>
  <input type="text" id="address" name="address" maxlength="100" 
         value="<?php echo isset($_COOKIE["address_$user_id"]) ? decrypt($_COOKIE["address_$user_id"], $encryptionKey) : ''; ?>" required onchange="uncheckRemember()">

  <label for="payment-method">Способ оплаты:</label>
  <input type="radio" id="payment-cash" name="payment-method" value="cash" onclick="toggleCardFields(false); uncheckRemember()"
         <?php echo (isset($_COOKIE["payment_method_$user_id"]) && decrypt($_COOKIE["payment_method_$user_id"], $encryptionKey) === 'cash') ? 'checked' : ''; ?>>
  <label for="payment-cash">Наличными</label>
  <input type="radio" id="payment-card" name="payment-method" value="card" onclick="toggleCardFields(true); uncheckRemember()"
         <?php echo (isset($_COOKIE["payment_method_$user_id"]) && decrypt($_COOKIE["payment_method_$user_id"], $encryptionKey) === 'card') ? 'checked' : ''; ?>>
  <label for="payment-card">Картой</label>

  <div id="card-fields" style="display: <?php echo (isset($_COOKIE["payment_method_$user_id"]) && decrypt($_COOKIE["payment_method_$user_id"], $encryptionKey) === 'card') ? 'block' : 'none'; ?>;">
    <label for="card-number">Номер карты:</label>
    <input type="text" id="card-number" name="card-number" maxlength="19" pattern="\d{4} \d{4} \d{4} \d{4}" placeholder="XXXX XXXX XXXX XXXX"
           value="<?php echo isset($_COOKIE["card_number_$user_id"]) ? decrypt($_COOKIE["card_number_$user_id"], $encryptionKey) : ''; ?>" onchange="uncheckRemember(); validateCardNumber()">
    
    <label for="card-expiry">Действует до:</label>
    <input type="text" id="card-expiry" name="card-expiry" maxlength="5" pattern="^(0[1-9]|1[0-2])\/\d{2}" placeholder="MM/YY"
           value="<?php echo isset($_COOKIE["card_expiry_$user_id"]) ? decrypt($_COOKIE["card_expiry_$user_id"], $encryptionKey) : ''; ?>" onchange="uncheckRemember(); validateExpiryDate()">
    
    <label for="card-cvv">CVV:</label>
    <input type="text" id="card-cvv" name="card-cvv" maxlength="3" pattern="\d{3}" placeholder="XXX"
           value="<?php echo isset($_COOKIE["card_cvv_$user_id"]) ? decrypt($_COOKIE["card_cvv_$user_id"], $encryptionKey) : ''; ?>" onchange="uncheckRemember(); validateCVV()">
  </div>

  <label for="remember">
    <input type="checkbox" id="remember" name="remember" onchange="handleRemember()"> Запомнить
  </label>

  
</form>

<script>
  function validateCardNumber() {
    const cardNumber = document.getElementById("card-number").value.replace(/\s+/g, "");
    if (!/^\d{16}$/.test(cardNumber)) {
      alert("Номер карты должен содержать цифры.");
      return false;
    }
    return true;
  }

  function validateExpiryDate() {
    const expiryDate = document.getElementById("card-expiry").value;
    const regex = /^(0[1-9]|1[0-2])\/\d{2}$/;
    if (!regex.test(expiryDate)) {
      alert("Дата должна быть в формате MM/YY, где MM — от 01 до 12.");
      return false;
    }
    return true;
  }

  function validateCVV() {
    const cvv = document.getElementById("card-cvv").value;
    if (!/^\d{3}$/.test(cvv)) {
      alert("CVV должен состоять из 3 цифр.");
      return false;
    }
    return true;
  }

  function checkRememberCookie() {
    const rememberCheckbox = document.getElementById('remember');
    
    // Проверяем, если чекбокс выключен
    if (!rememberCheckbox.checked) {
      // Отправляем AJAX-запрос на сервер для удаления cookies
      fetch('deleteCookie.php', { method: 'POST' })
        .then(response => {
          if (response.ok) {
            console.log("Cookies удалены");
          } else {
            console.error("Ошибка при удалении cookies");
          }
        })
        .catch(error => console.error("Ошибка сети:", error));
    }
    
    return true;
  }

  function toggleCardFields(show) {
    document.getElementById("card-fields").style.display = show ? "block" : "none";
  }

  function handleRemember() {
    const remember = document.getElementById('remember').checked;
    if (remember) {
      const formData = new FormData();
      
      // Заполняем данные для отправки
      formData.append('remember', remember);
      formData.append('address', document.getElementById('address').value);
      formData.append('payment-method', document.querySelector('input[name="payment-method"]:checked').value);

      if (document.getElementById('payment-card').checked) {
        formData.append('card-number', document.getElementById('card-number').value);
        formData.append('card-expiry', document.getElementById('card-expiry').value);
        formData.append('card-cvv', document.getElementById('card-cvv').value);
      }

      // Отправляем данные для сохранения в куки
      fetch('saveCookie.php', {
        method: 'POST',
        body: formData
      });
    }
  }

  function uncheckRemember() {
    const rememberCheckbox = document.getElementById('remember');
    if (rememberCheckbox.checked) {
      rememberCheckbox.checked = false;
    }
  }

  function validateExpiryDate() {
    const expiryField = document.getElementById('card-expiry');
    const expiryValue = expiryField.value;
    
    if (expiryValue.length >= 2) {
      const month = expiryValue.substring(0, 2);
      
      // Проверяем, что месяц в диапазоне от 01 до 12
      if (parseInt(month) < 1 || parseInt(month) > 12) {
        alert("Месяц должен быть в диапазоне от 01 до 12.");
        expiryField.value = "";  // Очищаем поле при неверном значении
      }
    }
  }
</script>

    <!-- Кнопка для оформления заказа -->
    <form action="order.php" method="post" onsubmit="return checkRememberCookie()">
        <input type="hidden" name="dish_id" value="<?= htmlspecialchars($order_id) ?>">
        <input type="submit" value="Заказать">
    </form>
</div>
</body>
</html>

<?php
$stmt->close();

?>
