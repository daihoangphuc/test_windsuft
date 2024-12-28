<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    if (!isset($_GET['transactionId'])) {
        throw new Exception('Transaction ID is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $transactionId = (int)$_GET['transactionId'];

    // Get transaction details
    $query = "SELECT * FROM taichinh WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    echo json_encode(['success' => true, 'data' => $transaction]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
