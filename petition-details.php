<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

$user = getCurrentUser();
$petitionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$petitionId) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

// Fetch petition
$stmt = $conn->prepare("SELECT * FROM petitions WHERE IDP = ?");
$stmt->bind_param("i", $petitionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
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
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="mb-6">
            <a href="index.php" class="text-purple-800 hover:text-purple-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to all petitions
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <!-- Main Content -->
            <div class="lg:col-span-3">
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
                                <a href="sign-petition.php?id=<?php echo $petition['IDP']; ?>" class="flex-1 py-3 bg-purple-800 text-white rounded-md hover:bg-purple-900 transition font-medium text-center">
                                    Sign this Petition
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($isOwner): ?>
                                <a href="modify-petition.php?id=<?php echo $petition['IDP']; ?>" class="px-6 py-3 border border-gray-300 rounded-md hover:bg-gray-50 transition">
                                    Edit
                                </a>
                                <button onclick="deletePetition(<?php echo $petition['IDP']; ?>)" class="px-6 py-3 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                                    Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Signatures Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Recent Signatures
                    </h3>
                    <div id="recentSignaturesContent">
                        <div class="space-y-3">
                            <div class="animate-pulse">
                                <div class="h-4 bg-gray-200 rounded mb-2"></div>
                                <div class="h-3 bg-gray-200 rounded mb-1"></div>
                                <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                            </div>
                            <div class="animate-pulse">
                                <div class="h-4 bg-gray-200 rounded mb-2"></div>
                                <div class="h-3 bg-gray-200 rounded mb-1"></div>
                                <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Signatures (Below both sections) -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">All Signatures (<?php echo count($signatures); ?>)</h2>
            
            <?php if (empty($signatures)): ?>
                <p class="text-gray-600 text-center py-8">No signatures yet. Be the first to sign!</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($signatures as $signature): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($signature['FirstNameS'] . ' ' . $signature['LastNameS']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($signature['CountryS']); ?> • <?php echo formatDateTime($signature['DateS'], $signature['TimeS']); ?></p>
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
// Load recent signatures for this petition
function loadRecentSignatures() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `api/signatures.php?petitionId=${<?php echo $petitionId; ?>}`, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            const container = document.getElementById('recentSignaturesContent');
            
            if (data.signatures && data.signatures.length > 0) {
                // Get only the first 5 signatures
                const recentSignatures = data.signatures.slice(0, 5);
                let html = '<div class="space-y-3">';
                
                recentSignatures.forEach(signature => {
                    const date = new Date(signature.DateS + 'T' + signature.TimeS);
                    const timeAgo = getTimeAgo(date);
                    
                    html += `
                        <div class="border-l-4 border-purple-800 pl-3 py-2">
                            <div class="font-medium text-sm text-gray-900">
                                ${signature.FirstNameS} ${signature.LastNameS}
                            </div>
                            <div class="text-xs text-gray-600">
                                ${signature.CountryS}
                            </div>
                            <div class="text-xs text-gray-500">
                                ${timeAgo}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center text-gray-500 py-4">
                        <p class="text-sm">No signatures yet</p>
                    </div>
                `;
            }
        }
    };
    xhr.send();
}

function getTimeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}d ago`;
    return date.toLocaleDateString();
}

// Auto-refresh recent signatures every 30 seconds
function startAutoRefresh() {
    loadRecentSignatures();
    setInterval(loadRecentSignatures, 30000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
});

function deletePetition(id) {
    if (!confirm('Are you sure you want to delete this petition? This action cannot be undone.')) {
        return;
    }
    
    fetch(`api/petitions.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
        } else {
            window.location.href = 'index.php';
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
    
    fetch(`api/signatures.php?petitionId=${petitionId}&signatureId=${signatureId}`, {
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
