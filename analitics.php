<?php 
session_start();
include "db.php"; 

$restoraunt_admin = isset($_SESSION['restoraunt_admin']) ? $_SESSION['restoraunt_admin'] : false;
$restoraunt_id = isset($_SESSION['restoraunt_id']) ? $_SESSION['restoraunt_id'] : 1;
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>График</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
   <a href="index.php">На главную</a>
    <h1>Заказы</h1>
    <canvas id="myChart" width="400" height="200"></canvas>

    <?php
      if($restoraunt_admin){
         $sql = "SELECT * FROM dish_table WHERE restoraunt_id = " . $restoraunt_id;
      } else{
         $sql = "SELECT * FROM dish_table";
      }
      
      $result = $mysqli->query($sql);
      if (!$result) {
         die("Ошибка выполнения запроса для получения данных из таблицы: " . $mysqli->error);    
      } else{ 
         $labels = [];
         $values = [];
         
         while ($row = $result->fetch_assoc()){
            if(!$restoraunt_admin){
               $labels[] = $row['dish_name'];


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
            $values[] = $procent;
            }
            elseif($restoraunt_admin){
               $labels[] = $row['dish_name'];

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
               WHERE a.dish_id IN (SELECT dish_id FROM dish_table WHERE restoraunt_id = " . $restoraunt_id . ") AND a.dish_id = ". $row['dish_id'];
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
               $values[] = $procent;
            }
         }
         $data = [
            'labels' => $labels,
            'values' => $values
        ];
      }



   
    echo '<script>
            const chartData = ' . json_encode($data) . ';
          </script>';
    ?>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const ctx = document.getElementById('myChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Продажи',
                        data: chartData.values,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
