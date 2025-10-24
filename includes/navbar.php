<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
  <div class="container mx-auto px-12 py-4 flex items-center justify-between">
    <!-- Logo -->
    <a href="index.php" class="flex items-center gap-2">
      <img src="assets/images/VoiceUp_logo.png" alt="VoiceUp Logo" class="h-10 w-auto object-contain">
    </a>

    <!-- Navigation -->
    <nav class="flex items-center pl-24 gap-4 font-medium">
      <a href="index.php"
         class="px-4 py-2 rounded-lg transition 
            <?= $currentPage === 'index.php' ? 'bg-purple-50 text-purple-800 font-semibold' : 'hover:bg-gray-50' ?>">
        Home
      </a>

      <?php if ($user): ?>
        <a href="my-petitions.php"
           class="px-4 py-2 rounded-lg transition 
            <?= $currentPage === 'my-petitions.php' ? 'bg-purple-50 text-purple-800 font-semibold' : 'hover:bg-gray-50' ?>">
          My Petitions
        </a>

        <a href="add-petition.php"
           class="px-4 py-2 rounded-lg transition 
            <?= $currentPage === 'add-petition.php' ? 'bg-purple-50 text-purple-800 font-semibold' : 'hover:bg-gray-50' ?>">
          Start a Petition
        </a>
      <?php endif; ?>
    </nav>

    <!-- User actions -->
    <div class="flex items-center gap-3 font-medium">
      <?php if ($user): ?>
        <a href="profile.php"
           class="px-4 py-2 border border-gray-300 rounded-lg transition 
              <?= $currentPage === 'profile.php' ? 'bg-purple-50 text-purple-800 border-purple-300 font-semibold' : 'hover:bg-gray-50' ?>">
          Profile
        </a>

        <a href="api/auth.php?action=logout"
           class="px-4 py-2 bg-purple-800 text-white rounded-lg hover:bg-purple-900 transition">
          Sign Out
        </a>
      <?php else: ?>
        <a href="login.php"
           class="px-4 py-2 border border-gray-300 rounded-lg transition 
              <?= $currentPage === 'login.php' ? 'bg-purple-50 text-purple-800 border-purple-300 font-semibold' : 'hover:bg-gray-50' ?>">
          Sign In
        </a>

        <a href="signup.php"
           class="px-4 py-2 bg-purple-800 text-white rounded-lg hover:bg-purple-900 transition">
          Sign Up
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
