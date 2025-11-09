<?php
// update_leave_status.php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "login";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_id = isset($_POST['leave_id']) ? intval($_POST['leave_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    if ($leave_id > 0 && in_array($status, ['approved', 'rejected'])) {
        $sql = "UPDATE leave_applications SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $leave_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Leave application " . $status . " successfully!";
        } else {
            $response['message'] = "Error updating leave status: " . $conn->error;
        }
    } else {
        $response['message'] = "Invalid leave ID or status.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
