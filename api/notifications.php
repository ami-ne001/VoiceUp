<?php
require_once __DIR__ . '/../config/session.php';

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
$readStatusFile = __DIR__ . '/../data/read_status.json';

// Create data directory if it doesn't exist
$dataDir = dirname($notificationsFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Get current user ID (or use session ID for anonymous users)
$userId = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $userId = $user['id'];
} else {
    // For anonymous users, use session ID
    $userId = session_id();
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

// Load read status for current user
function loadReadStatus() {
    global $readStatusFile, $userId;
    if (file_exists($readStatusFile)) {
        $content = file_get_contents($readStatusFile);
        $allReadStatus = json_decode($content, true) ?: [];
        return $allReadStatus[$userId] ?? [];
    }
    return [];
}

// Save read status for current user
function saveReadStatus($readStatus) {
    global $readStatusFile, $userId;
    $allReadStatus = [];
    if (file_exists($readStatusFile)) {
        $content = file_get_contents($readStatusFile);
        $allReadStatus = json_decode($content, true) ?: [];
    }
    $allReadStatus[$userId] = $readStatus;
    file_put_contents($readStatusFile, json_encode($allReadStatus));
}

// Check if notification is read by current user
function isNotificationRead($notificationId) {
    $readStatus = loadReadStatus();
    return in_array($notificationId, $readStatus);
}

// GET - Get recent notifications with user-specific read status
if ($method === 'GET') {
    $notifications = loadNotifications();
    
    // Add read status for current user to each notification
    foreach ($notifications as &$notification) {
        $notification['read'] = isNotificationRead($notification['id']);
    }
    
    echo json_encode(['notifications' => $notifications]);
    exit;
}

// POST - Add new notification or mark as read
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] === 'markRead' && isset($data['notificationId'])) {
        // Mark notification as read for current user
        $readStatus = loadReadStatus();
        if (!in_array($data['notificationId'], $readStatus)) {
            $readStatus[] = $data['notificationId'];
            saveReadStatus($readStatus);
        }
        echo json_encode(['success' => true]);
    } elseif (isset($data['petitionId']) && isset($data['title']) && isset($data['holder'])) {
        // Add new notification
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