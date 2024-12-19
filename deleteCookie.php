<?php
session_start();
$user_id = $_SESSION['user_id'];

setcookie("address_$user_id", "", time() - 3600, "/");
setcookie("payment_method_$user_id", "", time() - 3600, "/");
setcookie("card_number_$user_id", "", time() - 3600, "/");
setcookie("card_expiry_$user_id", "", time() - 3600, "/");
setcookie("card_cvv_$user_id", "", time() - 3600, "/");

?>
