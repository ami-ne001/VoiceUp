<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$notificationsFile = __DIR__ . '/../data/notifications.json';

// Create data directory if it doesn't exist
$dataDir = dirname($notificationsFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Load notifications from file
function loadNotifications() {
    global $notificationsFile;
    if (file_exists($notificationsFile)) {
        $content = file_get_contents($notificationsFile);
        return json_decode($content, true) ?: [];
    }
    return [];
}

// Save notifications to file
function saveNotifications($notifications) {
    global $notificationsFile;
    file_put_contents($notificationsFile, json_encode($notifications));
}

// GET - Get recent notifications
if ($method === 'GET') {
    $notifications = loadNotifications();
    echo json_encode(['notifications' => $notifications]);
    exit;
}

// POST - Add new notification
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['petitionId']) && isset($data['title']) && isset($data['holder'])) {
        $notifications = loadNotifications();
        
        $notification = [
            'id' => $data['petitionId'],
            'title' => $data['title'],
            'holder' => $data['holder'],
            'timestamp' => time()
        ];
        
        // Add to beginning of array
        array_unshift($notifications, $notification);
        
        // Keep only last 5 notifications
        $notifications = array_slice($notifications, 0, 5);
        
        // Save to file
        saveNotifications($notifications);
        
        echo json_encode(['success' => true, 'notification' => $notification]);
    } else {
        echo json_encode(['error' => 'Missing required fields']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
?>