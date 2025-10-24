<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

requireAuth();

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

// Check if user owns this petition
if ($petition['userId'] !== $user['id']) {
    header('Location: petition-details.php?id=' . $petitionId);
    exit;
}

$pageTitle = 'Edit Petition';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $holderName = $_POST['holderName'] ?? '';
    $email = $_POST['email'] ?? '';
    $imageUrl = $_POST['imageUrl'] ?? $petition['ImageUrl'];
    
    if (empty($title) || empty($description) || empty($endDate) || empty($holderName) || empty($email)) {
        $error = 'Please fill in all required fields';
    } elseif (strtotime($endDate) < time()) {
        $error = 'End date must be in the future';
    } else {
        $stmt = $conn->prepare("UPDATE petitions SET TitleP = ?, DescriptionP = ?, EndDateP = ?, HolderNameP = ?, Email = ?, ImageUrl = ? WHERE IDP = ?");
        $stmt->bind_param("ssssssi", $title, $description, $endDate, $holderName, $email, $imageUrl, $petitionId);
        
        if ($stmt->execute()) {
            header('Location: petition-details.php?id=' . $petitionId);
            exit;
        } else {
            $error = 'Failed to update petition. Please try again.';
        }
        
        $stmt->close();
    }
}

closeDBConnection($conn);
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-3xl">
        <div class="mb-6">
            <a href="petition-details.php?id=<?php echo $petitionId; ?>" class="text-purple-800 hover:text-purple-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to petition
            </a>
        </div>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Edit Petition</h1>
            <p class="text-gray-600">Update your petition details</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-8">
            <form method="POST" action="modify-petition.php?id=<?php echo $petitionId; ?>" enctype="multipart/form-data">
                <div class="mb-6">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Petition Title *</label>
                    <input type="text" id="title" name="title" required maxlength="500" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($petition['TitleP']); ?>">
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea id="description" name="description" required rows="8" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($petition['DescriptionP']); ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Petition Image (Optional)</label>
                    <?php if ($petition['ImageUrl']): ?>
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Current image:</p>
                            <img src="<?php echo htmlspecialchars($petition['ImageUrl']); ?>" alt="Current image" class="max-w-xs rounded-lg">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Upload a new image to replace the current one (Max 5MB)</p>
                    <input type="hidden" id="imageUrl" name="imageUrl" value="<?php echo htmlspecialchars($petition['ImageUrl'] ?? ''); ?>">
                    <div id="imagePreview" class="mt-4 hidden">
                        <img id="previewImg" src="" alt="Preview" class="max-w-xs rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="holderName" class="block text-sm font-medium text-gray-700 mb-2">Your Name *</label>
                        <input type="text" id="holderName" name="holderName" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['holderName']) ? htmlspecialchars($_POST['holderName']) : htmlspecialchars($petition['HolderNameP']); ?>">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Contact Email *</label>
                        <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($petition['Email']); ?>">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="endDate" class="block text-sm font-medium text-gray-700 mb-2">End Date *</label>
                    <input type="date" id="endDate" name="endDate" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo isset($_POST['endDate']) ? htmlspecialchars($_POST['endDate']) : htmlspecialchars($petition['EndDateP']); ?>">
                </div>

                <div class="flex gap-3">
                    <button type="submit" id="submitBtn" class="flex-1 py-3 bg-purple-800 text-white rounded-md hover:bg-purple-900 transition font-medium">
                        Update Petition
                    </button>
                    <a href="petition-details.php?id=<?php echo $petitionId; ?>" class="px-6 py-3 border border-gray-300 rounded-md hover:bg-gray-50 transition text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('image').addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('imagePreview').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
    
    // Upload image
    const formData = new FormData();
    formData.append('image', file);
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading image...';
    
    try {
        const response = await fetch('api/upload.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.url) {
            document.getElementById('imageUrl').value = data.url;
        } else {
            alert('Failed to upload image: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error uploading image:', error);
        alert('Failed to upload image');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update Petition';
    }
});
</script>

</body>
</html>
