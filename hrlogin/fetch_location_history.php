<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'hradminuser') {
    exit(json_encode(['error' => 'Unauthorized']));
}

$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'u797909128_demoproject';
$DATABASE_PASS = 'QK&0/aF@5';
$DATABASE_NAME = 'u797909128_demo';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

$employeeId = $_GET['employee_id'] ?? 0;
$date = $_GET['date'] ?? date('Y-m-d');

$start_date = $date . " 00:00:00";
$end_date = $date . " 23:59:59";

$sql = "SELECT latitude, longitude, captured_at, accuracy 
        FROM location_history 
        WHERE user_id = ? AND captured_at BETWEEN ? AND ? 
        ORDER BY captured_at ASC";

$stmt = $con->prepare($sql);
$stmt->bind_param('iss', $employeeId, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
