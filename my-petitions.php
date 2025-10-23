<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

requireAuth();

$user = getCurrentUser();
$pageTitle = 'My Petitions';

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

closeDBConnection($conn);

function formatDate($dateString) {
    return date('F j, Y', strtotime($dateString));
}

function isExpired($endDate) {
    return strtotime($endDate) < time();
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">My Petitions</h1>
            <p class="text-gray-600">Manage petitions you've created and signed</p>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="border-b">
                <div class="flex">
                    <button onclick="switchTab('created')" id="createdTab" class="px-6 py-4 font-medium border-b-2 border-blue-600 text-blue-600">
                        Created Petitions (<?php echo count($createdPetitions); ?>)
                    </button>
                    <button onclick="switchTab('signed')" id="signedTab" class="px-6 py-4 font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                        Signed Petitions (<?php echo count($signedPetitions); ?>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Created Petitions -->
        <div id="createdContent" class="grid gap-6">
            <?php if (empty($createdPetitions)): ?>
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No petitions created yet</h3>
                    <p class="text-gray-600 mb-4">Start making a difference by creating your first petition</p>
                    <a href="add-petition.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Create Petition
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($createdPetitions as $petition): ?>
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($petition['TitleP']); ?></h3>
                                    <p class="text-gray-600 line-clamp-2"><?php echo htmlspecialchars(substr($petition['DescriptionP'], 0, 200)) . (strlen($petition['DescriptionP']) > 200 ? '...' : ''); ?></p>
                                </div>
                                <?php if (isExpired($petition['EndDateP'])): ?>
                                    <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm">Closed</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        <span><?php echo number_format($petition['signatureCount']); ?> <?php echo $petition['signatureCount'] === 1 ? 'signature' : 'signatures'; ?></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span>Ends <?php echo formatDate($petition['EndDateP']); ?></span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="petition-details.php?id=<?php echo $petition['IDP']; ?>" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition text-center">
                                        View
                                    </a>
                                    <a href="modify-petition.php?id=<?php echo $petition['IDP']; ?>" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition text-center">
                                        Edit
                                    </a>
                                    <button onclick="deletePetition(<?php echo $petition['IDP']; ?>)" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Signed Petitions -->
        <div id="signedContent" class="grid gap-6 hidden">
            <?php if (empty($signedPetitions)): ?>
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No petitions signed yet</h3>
                    <p class="text-gray-600 mb-4">Browse petitions and sign those that matter to you</p>
                    <a href="index.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Browse Petitions
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($signedPetitions as $petition): ?>
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($petition['TitleP']); ?></h3>
                                    <p class="text-gray-600 line-clamp-2"><?php echo htmlspecialchars(substr($petition['DescriptionP'], 0, 200)) . (strlen($petition['DescriptionP']) > 200 ? '...' : ''); ?></p>
                                </div>
                                <?php if (isExpired($petition['EndDateP'])): ?>
                                    <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm">Closed</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Signed</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        <span><?php echo number_format($petition['signatureCount']); ?> <?php echo $petition['signatureCount'] === 1 ? 'signature' : 'signatures'; ?></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span>Ends <?php echo formatDate($petition['EndDateP']); ?></span>
                                    </div>
                                    <div class="text-gray-500">
                                        by <?php echo htmlspecialchars($petition['HolderNameP']); ?>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="petition-details.php?id=<?php echo $petition['IDP']; ?>" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-center">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    const createdTab = document.getElementById('createdTab');
    const signedTab = document.getElementById('signedTab');
    const createdContent = document.getElementById('createdContent');
    const signedContent = document.getElementById('signedContent');
    
    if (tab === 'created') {
        createdTab.classList.add('border-blue-600', 'text-blue-600');
        createdTab.classList.remove('border-transparent', 'text-gray-600');
        signedTab.classList.add('border-transparent', 'text-gray-600');
        signedTab.classList.remove('border-blue-600', 'text-blue-600');
        createdContent.classList.remove('hidden');
        signedContent.classList.add('hidden');
    } else {
        signedTab.classList.add('border-blue-600', 'text-blue-600');
        signedTab.classList.remove('border-transparent', 'text-gray-600');
        createdTab.classList.add('border-transparent', 'text-gray-600');
        createdTab.classList.remove('border-blue-600', 'text-blue-600');
        signedContent.classList.remove('hidden');
        createdContent.classList.add('hidden');
    }
}

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
            location.reload();
        }
    })
    .catch(error => {
        alert('Error deleting petition');
        console.error(error);
    });
}
</script>

</body>
</html>
