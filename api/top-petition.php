<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$conn = getDBConnection();

$query = "
    SELECT p.*, 
    (SELECT COUNT(*) FROM signatures s WHERE s.IDP = p.IDP) as signatureCount
    FROM petitions p
    HAVING signatureCount > 0
    ORDER BY signatureCount DESC
    LIMIT 1
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    $petition = $result->fetch_assoc();
    echo json_encode([
        'petition' => $petition,
        'signatureCount' => $petition['signatureCount']
    ]);
} else {
    echo json_encode([
        'petition' => null,
        'signatureCount' => 0
    ]);
}

closeDBConnection($conn);
?>
