<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="bg-white border-b sticky top-0 z-10 shadow-sm">
    <div class="container mx-auto px-12 py-5">
        <div class="flex items-center justify-between">
            <!-- Left: Logo + Nav -->
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3">
                    <img src="assets/images/VoiceUp_logo.jpg" alt="VoiceUp Logo" class="h-10 w-auto object-contain">
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-2">
                    <a href="index.php" 
                       class="px-4 py-2 rounded-lg hover:bg-gray-50 <?= $currentPage === 'index.php' ? 'bg-gray-100 font-semibold' : '' ?>">
                        Home
                    </a>

                    <?php if ($user): ?>
                    <a href="my-petitions.php" 
                       class="px-4 py-2 rounded-lg hover:bg-gray-50 <?= $currentPage === 'my-petitions.php' ? 'bg-gray-100 font-semibold' : '' ?>">
                        My Petitions
                    </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Right: User actions -->
            <div class="flex items-center gap-3">
                <?php if ($user): ?>
                    <a href="add-petition.php" 
                       class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
                        Start a Petition
                    </a>
                    <!-- Profile Dropdown (simple) -->
                    <div class="relative group">
                        <button class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                <?= strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-10">
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']); ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['email']); ?></p>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Profile</a>
                            <a href="my-petitions.php" class="block px-4 py-2 text-sm hover:bg-gray-50">My Petitions</a>
                            <a href="api/auth.php?action=logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Sign Out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
                        Sign In
                    </a>
                    <a href="signup.php" class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
