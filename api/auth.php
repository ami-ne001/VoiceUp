<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle logout
if ($action === 'logout') {
    clearUserSession();
    header('Location: ../index.php');
    exit;
}

// Handle signup
if ($method === 'POST' && $action === 'signup') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $name = $data['name'];
    
    $conn = getDBConnection();
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['error' => 'User with this email already exists']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    $stmt->close();
    
    // Create new user
    $stmt = $conn->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $password, $name);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        
        // Create profile
        $stmt2 = $conn->prepare("INSERT INTO profiles (userId, name, bio, location) VALUES (?, ?, '', '')");
        $stmt2->bind_param("is", $userId, $name);
        $stmt2->execute();
        $stmt2->close();
        
        echo json_encode(['success' => true, 'user' => ['id' => $userId, 'email' => $email, 'name' => $name]]);
    } else {
        echo json_encode(['error' => 'Failed to create user']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

// Handle login
if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $email = $data['email'];
    $password = $data['password'];
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT id, email, password, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Invalid email or password']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
        setUserSession($user['id'], $user['email'], $user['name']);
        echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'email' => $user['email'], 'name' => $user['name']]]);
    } else {
        echo json_encode(['error' => 'Invalid email or password']);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    exit;
}

// Handle session check
if ($method === 'GET' && $action === 'session') {
    $user = getCurrentUser();
    echo json_encode(['user' => $user]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>
