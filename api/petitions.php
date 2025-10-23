<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

// GET all petitions
if ($method === 'GET' && !isset($_GET['id'])) {
    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM signatures s WHERE s.IDP = p.IDP) as signatureCount
              FROM petitions p
              ORDER BY p.DateAddedP DESC";
    
    $result = $conn->query($query);
    $petitions = [];
    
    while ($row = $result->fetch_assoc()) {
        $petitions[] = $row;
    }
    
    echo json_encode(['petitions' => $petitions]);
    closeDBConnection($conn);
    exit;
}

// GET single petition
if ($method === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM petitions WHERE IDP = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Petition not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    
    $petition = $result->fetch_assoc();
    $stmt->close();
    
    // Get signatures
    $stmt = $conn->prepare("SELECT * FROM signatures WHERE IDP = ? ORDER BY DateS DESC, TimeS DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $signatures = [];
    while ($row = $result->fetch_assoc()) {
        $signatures[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'petition' => $petition,
        'signatures' => $signatures,
        'signatureCount' => count($signatures)
    ]);
    
    closeDBConnection($conn);
    exit;
}

// CREATE petition
if ($method === 'POST') {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Unauthorized']);
        closeDBConnection($conn);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $user = getCurrentUser();
    
    $stmt = $conn->prepare("INSERT INTO petitions (TitleP, DescriptionP, EndDateP, HolderNameP, Email, ImageUrl, userId) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", 
        $data['TitleP'], 
        $data['DescriptionP'], 
        $data['EndDateP'], 
        $data['HolderNameP'], 
        $data['Email'], 
        $data['ImageUrl'], 
        $user['id']
    );
    
    if ($stmt->execute()) {
        $petitionId = $conn->insert_id;
        
        // Track user's petition
        $stmt2 = $conn->prepare("INSERT INTO user_petitions (userId, petitionId) VALUES (?, ?)");
        $stmt2->bind_param("ii", $user['id'], $petitionId);
        $stmt2->execute();
        $stmt2->close();
        
        // Get the created petition
        $stmt3 = $conn->prepare("SELECT * FROM petitions WHERE IDP = ?");
        $stmt3->bind_param("i", $petitionId);
        $stmt3->execute();
        $result = $stmt3->get_result();
        $petition = $result->fetch_assoc();
        $stmt3->close();
        
        echo json_encode(['petition' => $petition]);
    } else {
        echo json_encode(['error' => 'Failed to create petition']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

// UPDATE petition
if ($method === 'PUT') {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Unauthorized']);
        closeDBConnection($conn);
        exit;
    }
    
    parse_str(file_get_contents('php://input'), $_PUT);
    $data = json_decode(file_get_contents('php://input'), true);
    $user = getCurrentUser();
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Check if petition exists and belongs to user
    $stmt = $conn->prepare("SELECT userId FROM petitions WHERE IDP = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Petition not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    
    $petition = $result->fetch_assoc();
    $stmt->close();
    
    if ($petition['userId'] !== $user['id']) {
        echo json_encode(['error' => 'Not authorized to modify this petition']);
        closeDBConnection($conn);
        exit;
    }
    
    // Update petition
    $stmt = $conn->prepare("UPDATE petitions SET TitleP = ?, DescriptionP = ?, EndDateP = ?, HolderNameP = ?, Email = ?, ImageUrl = ? WHERE IDP = ?");
    $stmt->bind_param("ssssssi", 
        $data['TitleP'], 
        $data['DescriptionP'], 
        $data['EndDateP'], 
        $data['HolderNameP'], 
        $data['Email'], 
        $data['ImageUrl'], 
        $id
    );
    
    if ($stmt->execute()) {
        // Get updated petition
        $stmt2 = $conn->prepare("SELECT * FROM petitions WHERE IDP = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $updatedPetition = $result->fetch_assoc();
        $stmt2->close();
        
        echo json_encode(['petition' => $updatedPetition]);
    } else {
        echo json_encode(['error' => 'Failed to update petition']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

// DELETE petition
if ($method === 'DELETE') {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Unauthorized']);
        closeDBConnection($conn);
        exit;
    }
    
    $user = getCurrentUser();
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Check if petition exists and belongs to user
    $stmt = $conn->prepare("SELECT userId FROM petitions WHERE IDP = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Petition not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    
    $petition = $result->fetch_assoc();
    $stmt->close();
    
    if ($petition['userId'] !== $user['id']) {
        echo json_encode(['error' => 'Not authorized to delete this petition']);
        closeDBConnection($conn);
        exit;
    }
    
    // Delete petition (signatures will be deleted automatically due to CASCADE)
    $stmt = $conn->prepare("DELETE FROM petitions WHERE IDP = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete petition']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
closeDBConnection($conn);
?>
