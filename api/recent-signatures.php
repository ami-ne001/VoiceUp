<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$conn = getDBConnection();

// Get the 5 most recent signatures across all petitions
$query = "
    SELECT s.*, p.TitleP as PetitionTitle
    FROM signatures s
    INNER JOIN petitions p ON s.IDP = p.IDP
    ORDER BY s.DateS DESC, s.TimeS DESC
    LIMIT 5
";

$result = $conn->query($query);

$signatures = [];
while ($row = $result->fetch_assoc()) {
    $signatures[] = $row;
}

echo json_encode(['signatures' => $signatures]);

closeDBConnection($conn);
?>
