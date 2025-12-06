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
    <nav class="flex items-center pl-36 gap-4 font-medium">
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
      <!-- Notification Bell -->
      <div class="relative">
        <button id="notificationBell" class="p-2 text-gray-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg transition relative">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
              viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z"/>
          </svg>

          <!-- Notification badge -->
          <span id="notificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
        </button>
        
        <!-- Notification Dropdown -->
        <div id="notificationDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 hidden">
          <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Notifications</h3>
          </div>
          <div id="notificationList" class="max-h-64 overflow-y-auto">
            <div class="p-4 text-center text-gray-500">
              <p>No notifications yet</p>
            </div>
          </div>
        </div>
      </div>

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
