<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user = getCurrentUser();
$conn = getDBConnection();

// GET profile
if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT name, bio, location FROM profiles WHERE userId = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create default profile if it doesn't exist
        $stmt2 = $conn->prepare("INSERT INTO profiles (userId, name, bio, location) VALUES (?, ?, '', '')");
        $stmt2->bind_param("is", $user['id'], $user['name']);
        $stmt2->execute();
        $stmt2->close();
        
        $profile = [
            'name' => $user['name'],
            'bio' => '',
            'location' => '',
            'email' => $user['email']
        ];
    } else {
        $profile = $result->fetch_assoc();
        $profile['email'] = $user['email'];
    }
    
    echo json_encode(['profile' => $profile]);
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

// UPDATE profile
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("UPDATE profiles SET name = ?, bio = ?, location = ? WHERE userId = ?");
    $stmt->bind_param("sssi", $data['name'], $data['bio'], $data['location'], $user['id']);
    
    if ($stmt->execute()) {
        // Update session name
        $_SESSION['user_name'] = $data['name'];
        
        // Update user name in users table
        $stmt2 = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt2->bind_param("si", $data['name'], $user['id']);
        $stmt2->execute();
        $stmt2->close();
        
        $profile = [
            'name' => $data['name'],
            'bio' => $data['bio'],
            'location' => $data['location'],
            'email' => $user['email']
        ];
        
        echo json_encode(['profile' => $profile]);
    } else {
        echo json_encode(['error' => 'Failed to update profile']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
closeDBConnection($conn);
?>
