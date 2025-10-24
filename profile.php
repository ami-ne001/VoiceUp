<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

requireAuth();

$user = getCurrentUser();
$pageTitle = 'Profile';
$error = '';
$success = '';

$conn = getDBConnection();

// Fetch profile
$stmt = $conn->prepare("SELECT name, bio, location FROM profiles WHERE userId = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create default profile
    $stmt2 = $conn->prepare("INSERT INTO profiles (userId, name, bio, location) VALUES (?, ?, '', '')");
    $stmt2->bind_param("is", $user['id'], $user['name']);
    $stmt2->execute();
    $stmt2->close();
    
    $profile = [
        'name' => $user['name'],
        'bio' => '',
        'location' => ''
    ];
} else {
    $profile = $result->fetch_assoc();
}
$stmt->close();

// Get user stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM petitions WHERE userId = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$createdCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_signatures WHERE userId = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$signedCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

closeDBConnection($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $location = $_POST['location'] ?? '';
    
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("UPDATE profiles SET name = ?, bio = ?, location = ? WHERE userId = ?");
        $stmt->bind_param("sssi", $name, $bio, $location, $user['id']);
        
        if ($stmt->execute()) {
            // Update session name
            $_SESSION['user_name'] = $name;
            
            // Update user name in users table
            $stmt2 = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt2->bind_param("si", $name, $user['id']);
            $stmt2->execute();
            $stmt2->close();
            
            $profile['name'] = $name;
            $profile['bio'] = $bio;
            $profile['location'] = $location;
            
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Profile</h1>
            <p class="text-gray-600">Manage your account information</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                <p class="text-green-600 text-sm"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Info -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Personal Information</h2>
                    
                    <form method="POST" action="profile.php">
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" id="name" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo htmlspecialchars($profile['name']); ?>">
                        </div>

                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" id="email" name="email" disabled class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500" value="<?php echo htmlspecialchars($user['email']); ?>">
                            <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                        </div>

                        <div class="mb-6">
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                            <input type="text" id="location" name="location" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?php echo htmlspecialchars($profile['location']); ?>" placeholder="e.g., New York, USA">
                        </div>

                        <div class="mb-6">
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                            <textarea id="bio" name="bio" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Tell us a bit about yourself..."><?php echo htmlspecialchars($profile['bio']); ?></textarea>
                        </div>

                        <button type="submit" class="w-full py-3 bg-purple-800 text-white rounded-md hover:bg-purple-900 transition font-medium">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stats Sidebar -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center justify-center mb-6">
                        <div class="w-24 h-24 bg-purple-800 rounded-full flex items-center justify-center text-white text-3xl font-bold">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 text-center mb-2"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="text-sm text-gray-600 text-center mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Activity</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-purple-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span class="text-sm text-gray-700">Petitions Created</span>
                            </div>
                            <span class="text-lg font-bold text-gray-900"><?php echo $createdCount; ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm text-gray-700">Petitions Signed</span>
                            </div>
                            <span class="text-lg font-bold text-gray-900"><?php echo $signedCount; ?></span>
                        </div>
                    </div>
                    <a href="my-petitions.php" class="block w-full mt-4 py-2 text-center border border-gray-300 rounded-md hover:bg-gray-50 transition">
                        View My Petitions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
