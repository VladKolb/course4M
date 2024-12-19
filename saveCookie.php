<?php
session_start();

$encryptionKey = "hardpassword";

function encrypt($data, $key){
    return openssl_encrypt($data, "AES-128-ECB", $key);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];  // Получаем ID пользователя из сессии
    
    if (isset($_POST['remember']) && $_POST['remember'] === 'true') {

        setcookie("address_$user_id", encrypt($_POST['address'], $encryptionKey), time() + (86400 * 30), "/");
        setcookie("payment_method_$user_id", encrypt($_POST['payment-method'], $encryptionKey), time() + (86400 * 30), "/");
        
        if ($_POST['payment-method'] === 'card') {
            setcookie("card_number_$user_id", encrypt($_POST['card-number'], $encryptionKey), time() + (86400 * 30), "/");
            setcookie("card_expiry_$user_id", encrypt($_POST['card-expiry'], $encryptionKey), time() + (86400 * 30), "/");
            //setcookie("card_cvv_$user_id", $_POST['card-cvv'], time() + (86400 * 30), "/");
        }
    } else {
        // Удаляем куки, если "Запомнить" не выбран
        setcookie("address_$user_id", '', time() - 3600, "/");
        setcookie("payment_method_$user_id", '', time() - 3600, "/");
        setcookie("card_number_$user_id", '', time() - 3600, "/");
        setcookie("card_expiry_$user_id", '', time() - 3600, "/");
        //setcookie("card_cvv_$user_id", '', time() - 3600, "/");
    }
}
?>
