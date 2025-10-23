<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

// Get petitions created by user
$stmt = $conn->prepare("
    SELECT p.*, 
    (SELECT COUNT(*) FROM signatures s WHERE s.IDP = p.IDP) as signatureCount
    FROM petitions p
    WHERE p.userId = ?
    ORDER BY p.DateAddedP DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$createdPetitions = [];
while ($row = $result->fetch_assoc()) {
    $createdPetitions[] = $row;
}
$stmt->close();

// Get petitions signed by user
$stmt = $conn->prepare("
    SELECT p.*, 
    (SELECT COUNT(*) FROM signatures s WHERE s.IDP = p.IDP) as signatureCount
    FROM petitions p
    INNER JOIN user_signatures us ON p.IDP = us.petitionId
    WHERE us.userId = ?
    ORDER BY us.created_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$signedPetitions = [];
while ($row = $result->fetch_assoc()) {
    $signedPetitions[] = $row;
}
$stmt->close();

echo json_encode([
    'created' => $createdPetitions,
    'signed' => $signedPetitions
]);

closeDBConnection($conn);
?>
