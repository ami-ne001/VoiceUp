<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-white shadow-sm border-b">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="index.php" class="text-xl font-semibold text-gray-900">
                    Petition Platform
                </a>
                <div class="hidden md:flex items-center gap-4">
                    <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900'; ?> transition">
                        Home
                    </a>
                    <?php if ($user): ?>
                    <a href="my-petitions.php" class="<?php echo $currentPage === 'my-petitions.php' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900'; ?> transition">
                        My Petitions
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if ($user): ?>
                    <a href="add-petition.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Start a Petition
                    </a>
                    <div class="relative group">
                        <button class="flex items-center gap-2 px-3 py-2 rounded-md hover:bg-gray-100 transition">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Profile
                            </a>
                            <a href="my-petitions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                My Petitions
                            </a>
                            <a href="api/auth.php?action=logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                Sign Out
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="px-4 py-2 text-gray-700 hover:text-gray-900 transition">
                        Sign In
                    </a>
                    <a href="signup.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
