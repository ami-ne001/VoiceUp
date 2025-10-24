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
    <div class="container mx-auto px-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">My Petitions</h1>
            <p class="text-gray-600">Manage petitions you've created and signed</p>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="border-b">
                <div class="flex">
                    <button onclick="switchTab('created')" id="createdTab" class="px-6 py-4 font-medium border-b-2 border-purple-800 text-purple-800">
                        Created Petitions (<?php echo count($createdPetitions); ?>)
                    </button>
                    <button onclick="switchTab('signed')" id="signedTab" class="px-6 py-4 font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                        Signed Petitions (<?php echo count($signedPetitions); ?>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Created Petitions -->
        <div id="createdContent" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($createdPetitions)): ?>
                <div class="col-span-3 bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No petitions created yet</h3>
                    <p class="text-gray-600 mb-4">Start making a difference by creating your first petition</p>
                    <a href="add-petition.php" class="inline-block px-6 py-2 bg-purple-800 text-white rounded-md hover:bg-purple-900 transition">
                        Create Petition
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($createdPetitions as $petition): ?>
                    <?php
                        $image = !empty($petition['ImageUrl']) ? htmlspecialchars($petition['ImageUrl']) : 'assets/images/default_petition.jpg';
                        $expired = isExpired($petition['EndDateP']);
                    ?>
                    <div class="flex flex-col bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <!-- Petition Image -->
                        <div class="relative h-48">
                            <img src="<?= $image ?>" alt="<?= htmlspecialchars($petition['TitleP']) ?>" class="w-full h-full object-cover">
                            <?php if ($expired): ?>
                                <span class="absolute top-3 right-3 bg-gray-800 text-white px-3 py-1 rounded-full text-xs font-semibold">Closed</span>
                            <?php endif; ?>
                        </div>

                        <!-- Petition Info -->
                        <div class="flex-1 flex flex-col justify-between p-5">
                            <div>
                                <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($petition['TitleP']); ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-3">
                                <?= htmlspecialchars(substr($petition['DescriptionP'], 0, 150)) . (strlen($petition['DescriptionP']) > 150 ? '...' : ''); ?>
                                </p>

                                <div class="space-y-1 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user"></i>
                                        <span>by <?= htmlspecialchars($petition['HolderNameP']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar"></i>
                                        <span>Ends <?= formatDate($petition['EndDateP']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-pen"></i>
                                        <span><?= number_format($petition['signatureCount']); ?> signatures</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-5 flex gap-3">
                                <a href="petition-details.php?id=<?= $petition['IDP']; ?>" 
                                class="flex-1 text-center py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition">
                                View
                                </a>
                                <a href="modify-petition.php?id=<?= $petition['IDP']; ?>" 
                                class="flex-1 text-center py-2 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition">
                                Edit
                                </a>
                                <button onclick="deletePetition(<?= $petition['IDP']; ?>)" 
                                class="flex-1 text-center py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                                Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Signed Petitions -->
        <div id="signedContent" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 hidden">
            <?php if (empty($signedPetitions)): ?>
                <div class="col-span-3 bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No petitions signed yet</h3>
                    <p class="text-gray-600 mb-4">Browse petitions and sign those that matter to you</p>
                    <a href="index.php" class="inline-block px-6 py-2 bg-purple-800 text-white rounded-md hover:bg-purple-900 transition">
                        Browse Petitions
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($signedPetitions as $petition): ?>
                    <?php
                        $image = !empty($petition['ImageUrl']) ? htmlspecialchars($petition['ImageUrl']) : 'assets/images/default_petition.jpg';
                        $expired = isExpired($petition['EndDateP']);
                    ?>
                    <div class="flex flex-col bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <!-- Petition Image -->
                        <div class="relative h-48">
                            <img src="<?= $image ?>" alt="<?= htmlspecialchars($petition['TitleP']) ?>" class="w-full h-full object-cover">
                            <?php if ($expired): ?>
                                <span class="absolute top-3 right-3 bg-gray-800 text-white px-3 py-1 rounded-full text-xs font-semibold">Closed</span>
                            <?php endif; ?>
                        </div>

                        <!-- Petition Info -->
                        <div class="flex-1 flex flex-col justify-between p-5">
                            <div>
                                <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($petition['TitleP']); ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-3">
                                <?= htmlspecialchars(substr($petition['DescriptionP'], 0, 150)) . (strlen($petition['DescriptionP']) > 150 ? '...' : ''); ?>
                                </p>

                                <div class="space-y-1 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user"></i>
                                        <span>by <?= htmlspecialchars($petition['HolderNameP']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar"></i>
                                        <span>Ends <?= formatDate($petition['EndDateP']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-pen"></i>
                                        <span><?= number_format($petition['signatureCount']); ?> signatures</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-5 flex gap-3">
                                <a href="petition-details.php?id=<?= $petition['IDP']; ?>" 
                                class="flex-1 text-center py-2 bg-purple-800 text-white rounded-lg font-medium hover:bg-purple-900 transition">
                                View Details
                                </a>
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
        createdTab.classList.add('border-purple-800', 'text-purple-800');
        createdTab.classList.remove('border-transparent', 'text-gray-600');
        signedTab.classList.add('border-transparent', 'text-gray-600');
        signedTab.classList.remove('border-purple-800', 'text-purple-800');
        createdContent.classList.remove('hidden');
        signedContent.classList.add('hidden');
    } else {
        signedTab.classList.add('border-purple-800', 'text-purple-800');
        signedTab.classList.remove('border-transparent', 'text-gray-600');
        createdTab.classList.add('border-transparent', 'text-gray-600');
        createdTab.classList.remove('border-purple-800', 'text-purple-800');
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
