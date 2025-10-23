<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

// GET signatures for a petition
if ($method === 'GET' && isset($_GET['petitionId'])) {
    $petitionId = intval($_GET['petitionId']);
    
    $stmt = $conn->prepare("SELECT * FROM signatures WHERE IDP = ? ORDER BY DateS DESC, TimeS DESC");
    $stmt->bind_param("i", $petitionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $signatures = [];
    while ($row = $result->fetch_assoc()) {
        $signatures[] = $row;
    }
    
    echo json_encode(['signatures' => $signatures]);
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

// CREATE signature
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['IDP']) || !isset($data['FirstNameS']) || !isset($data['LastNameS']) || !isset($data['EmailS']) || !isset($data['CountryS'])) {
        echo json_encode(['error' => 'Missing required fields']);
        closeDBConnection($conn);
        exit;
    }
    
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    $stmt = $conn->prepare("INSERT INTO signatures (IDP, LastNameS, FirstNameS, CountryS, DateS, TimeS, EmailS) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", 
        $data['IDP'], 
        $data['LastNameS'], 
        $data['FirstNameS'], 
        $data['CountryS'], 
        $date, 
        $time, 
        $data['EmailS']
    );
    
    if ($stmt->execute()) {
        $signatureId = $conn->insert_id;
        
        // Track user's signature if logged in
        if (isLoggedIn()) {
            $user = getCurrentUser();
            $stmt2 = $conn->prepare("INSERT IGNORE INTO user_signatures (userId, petitionId) VALUES (?, ?)");
            $stmt2->bind_param("ii", $user['id'], $data['IDP']);
            $stmt2->execute();
            $stmt2->close();
        }
        
        // Get the created signature
        $stmt3 = $conn->prepare("SELECT * FROM signatures WHERE IDS = ?");
        $stmt3->bind_param("i", $signatureId);
        $stmt3->execute();
        $result = $stmt3->get_result();
        $signature = $result->fetch_assoc();
        $stmt3->close();
        
        echo json_encode(['signature' => $signature]);
    } else {
        echo json_encode(['error' => 'Failed to add signature']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

// DELETE signature
if ($method === 'DELETE') {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Unauthorized']);
        closeDBConnection($conn);
        exit;
    }
    
    $user = getCurrentUser();
    $petitionId = isset($_GET['petitionId']) ? intval($_GET['petitionId']) : 0;
    $signatureId = isset($_GET['signatureId']) ? intval($_GET['signatureId']) : 0;
    
    // Check if signature exists and belongs to user
    $stmt = $conn->prepare("SELECT EmailS FROM signatures WHERE IDS = ? AND IDP = ?");
    $stmt->bind_param("ii", $signatureId, $petitionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Signature not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    
    $signature = $result->fetch_assoc();
    $stmt->close();
    
    if ($signature['EmailS'] !== $user['email']) {
        echo json_encode(['error' => 'Not authorized to delete this signature']);
        closeDBConnection($conn);
        exit;
    }
    
    // Delete signature
    $stmt = $conn->prepare("DELETE FROM signatures WHERE IDS = ?");
    $stmt->bind_param("i", $signatureId);
    
    if ($stmt->execute()) {
        // Remove from user's signatures
        $stmt2 = $conn->prepare("DELETE FROM user_signatures WHERE userId = ? AND petitionId = ?");
        $stmt2->bind_param("ii", $user['id'], $petitionId);
        $stmt2->execute();
        $stmt2->close();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete signature']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
closeDBConnection($conn);
?>
