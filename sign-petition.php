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

// Get signature count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM signatures WHERE IDP = ?");
$stmt->bind_param("i", $petitionId);
$stmt->execute();
$result = $stmt->get_result();
$signatureCount = $result->fetch_assoc()['count'];
$stmt->close();

closeDBConnection($conn);

$pageTitle = 'Sign Petition';
$isExpired = strtotime($petition['EndDateP']) < time();
$error = '';
$success = '';

if ($isExpired) {
    header('Location: petition-details.php?id=' . $petitionId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $country = $_POST['country'] ?? '';
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($country)) {
        $error = 'Please fill in all fields';
    } else {
        $conn = getDBConnection();
        
        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO signatures (IDP, LastNameS, FirstNameS, CountryS, DateS, TimeS, EmailS) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $petitionId, $lastName, $firstName, $country, $date, $time, $email);
        
        if ($stmt->execute()) {
            // Track user's signature if logged in
            if ($user) {
                $stmt2 = $conn->prepare("INSERT IGNORE INTO user_signatures (userId, petitionId) VALUES (?, ?)");
                $stmt2->bind_param("ii", $user['id'], $petitionId);
                $stmt2->execute();
                $stmt2->close();
            }
            
            header('Location: petition-details.php?id=' . $petitionId);
            exit;
        } else {
            $error = 'Failed to add signature. Please try again.';
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}

function formatDate($dateString) {
    return date('F j, Y', strtotime($dateString));
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="mb-6">
            <a href="petition-details.php?id=<?php echo $petitionId; ?>" class="text-purple-800 hover:text-purple-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to petition
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($petition['TitleP']); ?></h1>
                    
                    <div class="mb-6 p-6 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($signatureCount); ?></p>
                                <p class="text-gray-600"><?php echo $signatureCount === 1 ? 'Signature' : 'Signatures'; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Ends on</p>
                                <p class="font-semibold text-gray-900"><?php echo formatDate($petition['EndDateP']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Sign this Petition</h2>
            <p class="text-gray-600 mb-6">Add your voice to support this cause</p>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="sign-petition.php?id=<?php echo $petitionId; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" id="firstName" name="firstName" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ($user ? htmlspecialchars(explode(' ', $user['name'])[0]) : ''); ?>">
                    </div>

                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ($user && strpos($user['name'], ' ') !== false ? htmlspecialchars(substr($user['name'], strpos($user['name'], ' ') + 1)) : ''); ?>">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ($user ? htmlspecialchars($user['email']) : ''); ?>">
                </div>

                <div class="mb-6">
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                    <input type="text" id="country" name="country" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>" placeholder="e.g., United States, United Kingdom">
                </div>

                <div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-md">
                    <p class="text-sm text-purple-800">
                        <strong>Note:</strong> Your signature will be publicly visible on the petition page. By signing, you agree to support this cause.
                    </p>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-3 bg-purple-800 text-white rounded-md hover:bg-purple-900 transition font-medium">
                        Sign Petition
                    </button>
                    <a href="petition-details.php?id=<?php echo $petitionId; ?>" class="px-6 py-3 border border-gray-300 rounded-md hover:bg-gray-50 transition text-center">
                        Cancel
                    </a>
                </div>
            </form>
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
</script>

</body>
</html>
