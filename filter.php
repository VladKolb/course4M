<?php
include 'db.php';

$table = $_POST['table'] ?? '';
$query = '';
$params = [];
$types = '';

if ($table == 'client') {
    $order_count_from = $_POST['order_count_from'] ?? '';
    $order_count_to = $_POST['order_count_to'] ?? '';

    if ($order_count_from !== '' && $order_count_to !== '') {
        $query = 'SELECT * FROM client WHERE order_count BETWEEN ? AND ?';
        $types = 'ii';
        $params = [$order_count_from, $order_count_to];
    }

} elseif ($table == 'order_table') {
    $client_id_from = $_POST['client_id_from'] ?? '';
    $client_id_to = $_POST['client_id_to'] ?? '';
    $delivery_date_from = $_POST['delivery_date_from'] ?? '';
    $delivery_date_to = $_POST['delivery_date_to'] ?? '';
    $delivery_cost_from = $_POST['delivery_cost_from'] ?? '';
    $delivery_cost_to = $_POST['delivery_cost_to'] ?? '';

    $query_parts = [];
    
    if ($client_id_from !== '' && $client_id_to !== '') {
        $query_parts[] = 'client_id BETWEEN ? AND ?';
        $types .= 'ii';
        $params[] = $client_id_from;
        $params[] = $client_id_to;
    }
    
    if ($delivery_date_from !== '' && $delivery_date_to !== '') {
        $query_parts[] = 'delivery_date BETWEEN ? AND ?';
        $types .= 'ss';
        $params[] = $delivery_date_from;
        $params[] = $delivery_date_to;
    }
    
    if ($delivery_cost_from !== '' && $delivery_cost_to !== '') {
        $query_parts[] = 'delivery_cost BETWEEN ? AND ?';
        $types .= 'dd';
        $params[] = $delivery_cost_from;
        $params[] = $delivery_cost_to;
    }

    if (!empty($query_parts)) {
        $query = 'SELECT * FROM order_table WHERE ' . implode(' AND ', $query_parts);
    }
}

if ($query) {
    $stmt = $mysqli->prepare($query);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // Дальше обрабатывайте $results для отображения на странице
}

$mysqli->close();
?>
