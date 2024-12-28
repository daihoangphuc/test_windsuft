<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    if (!isset($_GET['documentId'])) {
        throw new Exception('Document ID is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $documentId = (int)$_GET['documentId'];

    // Get document details
    $query = "SELECT * FROM tailieu WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();

    if (!$document) {
        throw new Exception('Document not found');
    }

    echo json_encode(['success' => true, 'data' => $document]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
