<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="bg-white border-b sticky top-0 z-10 shadow-sm">
  <div class="container mx-auto px-10 py-4 flex items-center justify-between">
    <!-- Logo -->
    <a href="index.php" class="flex items-center gap-2">
      <img src="assets/images/VoiceUp_logo.jpg" alt="VoiceUp Logo" class="h-10 w-auto object-contain">
    </a>

    <!-- Navigation -->
    <nav class="flex items-center gap-4 text-sm font-medium">
      <a href="index.php"
         class="px-4 py-2 rounded-lg hover:bg-gray-50 <?= $currentPage === 'index.php' ? 'bg-gray-100' : '' ?>">
        Home
      </a>

      <?php if ($user): ?>
        <a href="my-petitions.php"
           class="px-4 py-2 rounded-lg hover:bg-gray-50 <?= $currentPage === 'my-petitions.php' ? 'bg-gray-100' : '' ?>">
          My Petitions
        </a>

        <a href="add-petition.php"
           class="px-4 py-2 rounded-lg hover:bg-gray-50 <?= $currentPage === 'add-petition.php' ? 'bg-gray-100' : '' ?>">
          Start a Petition
        </a>
      <?php endif; ?>
    </nav>

    <!-- User actions -->
    <div class="flex items-center gap-3 text-sm font-medium">
      <?php if ($user): ?>
        <a href="profile.php"
           class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition <?= $currentPage === 'profile.php' ? 'bg-gray-100' : '' ?>">
          Profile
        </a>

        <a href="api/auth.php?action=logout"
           class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
          Sign Out
        </a>
      <?php else: ?>
        <a href="login.php"
           class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition <?= $currentPage === 'login.php' ? 'bg-gray-100' : '' ?>">
          Sign In
        </a>

        <a href="signup.php"
           class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
          Sign Up
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
