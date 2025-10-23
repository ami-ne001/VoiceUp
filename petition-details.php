<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

$user = getCurrentUser();
$petitionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$petitionId) {
    header('Location: /php-version/index.php');
    exit;
}

$conn = getDBConnection();

// Fetch petition
$stmt = $conn->prepare("SELECT * FROM petitions WHERE IDP = ?");
$stmt->bind_param("i", $petitionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: /php-version/index.php');
    exit;
}

$petition = $result->fetch_assoc();
$stmt->close();

// Fetch signatures
$stmt = $conn->prepare("SELECT * FROM signatures WHERE IDP = ? ORDER BY DateS DESC, TimeS DESC");
$stmt->bind_param("i", $petitionId);
$stmt->execute();
$result = $stmt->get_result();

$signatures = [];
while ($row = $result->fetch_assoc()) {
    $signatures[] = $row;
}
$stmt->close();

closeDBConnection($conn);

$pageTitle = $petition['TitleP'];
$isOwner = $user && $user['id'] == $petition['userId'];
$isExpired = strtotime($petition['EndDateP']) < time();

function formatDate($dateString) {
    return date('F j, Y', strtotime($dateString));
}

function formatDateTime($dateString, $timeString) {
    return date('M j, Y g:i A', strtotime($dateString . ' ' . $timeString));
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="mb-6">
            <a href="/php-version/index.php" class="text-blue-600 hover:text-blue-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to all petitions
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <?php if ($petition['ImageUrl']): ?>
                <img src="<?php echo htmlspecialchars($petition['ImageUrl']); ?>" alt="Petition image" class="w-full h-64 object-cover">
            <?php endif; ?>

            <div class="p-8">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($petition['TitleP']); ?></h1>
                        <p class="text-gray-600">Started by <?php echo htmlspecialchars($petition['HolderNameP']); ?> on <?php echo formatDate($petition['DateAddedP']); ?></p>
                    </div>
                    <?php if ($isExpired): ?>
                        <span class="px-4 py-2 bg-gray-200 text-gray-700 rounded-full">Closed</span>
                    <?php endif; ?>
                </div>

                <div class="mb-6 p-6 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format(count($signatures)); ?></p>
                            <p class="text-gray-600"><?php echo count($signatures) === 1 ? 'Signature' : 'Signatures'; ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Ends on</p>
                            <p class="font-semibold text-gray-900"><?php echo formatDate($petition['EndDateP']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Description</h2>
                    <div class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($petition['DescriptionP']); ?></div>
                </div>

                <div class="flex gap-3">
                    <?php if (!$isExpired): ?>
                        <a href="/php-version/sign-petition.php?id=<?php echo $petition['IDP']; ?>" class="flex-1 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition font-medium text-center">
                            Sign this Petition
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($isOwner): ?>
                        <a href="/php-version/modify-petition.php?id=<?php echo $petition['IDP']; ?>" class="px-6 py-3 border border-gray-300 rounded-md hover:bg-gray-50 transition">
                            Edit
                        </a>
                        <button onclick="deletePetition(<?php echo $petition['IDP']; ?>)" class="px-6 py-3 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                            Delete
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Signatures (<?php echo count($signatures); ?>)</h2>
            
            <?php if (empty($signatures)): ?>
                <p class="text-gray-600 text-center py-8">No signatures yet. Be the first to sign!</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($signatures as $signature): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($signature['FirstNameS'], 0, 1) . substr($signature['LastNameS'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($signature['FirstNameS'] . ' ' . $signature['LastNameS']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($signature['CountryS']); ?> • <?php echo formatDateTime($signature['DateS'], $signature['TimeS']); ?></p>
                                </div>
                            </div>
                            <?php if ($user && $user['email'] === $signature['EmailS']): ?>
                                <button onclick="deleteSignature(<?php echo $petitionId; ?>, <?php echo $signature['IDS']; ?>)" class="text-red-600 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deletePetition(id) {
    if (!confirm('Are you sure you want to delete this petition? This action cannot be undone.')) {
        return;
    }
    
    fetch(`/php-version/api/petitions.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
        } else {
            window.location.href = '/php-version/index.php';
        }
    })
    .catch(error => {
        alert('Error deleting petition');
        console.error(error);
    });
}

function deleteSignature(petitionId, signatureId) {
    if (!confirm('Are you sure you want to remove your signature?')) {
        return;
    }
    
    fetch(`/php-version/api/signatures.php?petitionId=${petitionId}&signatureId=${signatureId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
        } else {
            location.reload();
        }
    })
    .catch(error => {
        alert('Error removing signature');
        console.error(error);
    });
}
</script>

</body>
</html>
