<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

$pageTitle = 'Browse Petitions';
$user = getCurrentUser();

// Fetch all petitions
$conn = getDBConnection();
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM signatures s WHERE s.IDP = p.IDP) as signatureCount
          FROM petitions p
          ORDER BY p.DateAddedP DESC";
$result = $conn->query($query);
$petitions = [];
while ($row = $result->fetch_assoc()) {
    $petitions[] = $row;
}

// Fetch top petition
$topQuery = "SELECT p.*, 
             (SELECT COUNT(*) FROM signatures s WHERE s.IDP = p.IDP) as signatureCount
             FROM petitions p
             HAVING signatureCount > 0
             ORDER BY signatureCount DESC
             LIMIT 1";
$topResult = $conn->query($topQuery);
$topPetition = $topResult->num_rows > 0 ? $topResult->fetch_assoc() : null;

closeDBConnection($conn);

function formatDate($dateString) {
    return date('M d, Y', strtotime($dateString));
}

function isExpired($endDate) {
    return strtotime($endDate) < time();
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="min-h-screen bg-gray-50">
  <div class="container mx-auto px-12 py-8">
    
    <!-- Page Title -->
    <div class="mb-8 text-center">
      <h1 class="text-4xl font-bold text-gray-900 mb-2">Browse Petitions</h1>
      <p class="text-gray-600">Discover and sign petitions that matter to you</p>
    </div>

    <!-- Top Petition -->
    <?php if ($topPetition): ?>
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 mb-10 text-white">
      <div class="flex items-center gap-2 mb-2">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
        </svg>
        <span class="font-semibold">Trending Petition</span>
      </div>
      <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($topPetition['TitleP']); ?></h2>
      <p class="text-blue-100 mb-4"><?= htmlspecialchars(substr($topPetition['DescriptionP'], 0, 150)) . (strlen($topPetition['DescriptionP']) > 150 ? '...' : ''); ?></p>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
          </svg>
          <span class="text-lg"><?= number_format($topPetition['signatureCount']); ?> signatures</span>
        </div>
        <a href="petition-details.php?id=<?= $topPetition['IDP']; ?>" class="bg-white text-blue-600 px-6 py-2 rounded-md hover:bg-gray-100 transition font-semibold">
          View Petition
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Search + Sort -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
          <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input type="text" id="searchInput" placeholder="Search petitions by title, description, or author..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <select id="sortSelect" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent md:w-48">
          <option value="recent">Most Recent</option>
          <option value="popular">Most Popular</option>
          <option value="ending-soon">Ending Soon</option>
        </select>
      </div>
    </div>

    <!-- Petitions Grid -->
    <div id="petitionsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($petitions)): ?>
            <div class="col-span-3 bg-white rounded-lg shadow-sm p-12 text-center">
            <p class="text-gray-600">No petitions yet. Be the first to start one!</p>
            </div>
        <?php else: ?>
            <?php foreach ($petitions as $petition): ?>
            <?php
                $image = !empty($petition['ImageUrl']) ? htmlspecialchars($petition['ImageUrl']) : 'assets/images/default_petition.jpg';
                $expired = isExpired($petition['EndDateP']);
            ?>
            <div class="petition-card flex flex-col bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow"
                data-title="<?= strtolower($petition['TitleP']); ?>"
                data-description="<?= strtolower($petition['DescriptionP']); ?>"
                data-holder="<?= strtolower($petition['HolderNameP']); ?>"
                data-date="<?= strtotime($petition['DateAddedP']); ?>"
                data-end="<?= strtotime($petition['EndDateP']); ?>"
                data-signatures="<?= $petition['signatureCount']; ?>">

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
                    <a href="sign-petition.php?id=<?= $petition['IDP']; ?>" 
                    class="flex-1 text-center py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition <?= $expired ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' ?>">
                    <?= $expired ? 'Closed' : 'Sign' ?>
                    </a>
                </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
  </div>
</main>

<script>
// Search and sort
const searchInput = document.getElementById('searchInput');
const sortSelect = document.getElementById('sortSelect');
const petitionCards = document.querySelectorAll('.petition-card');

function filterAndSort() {
  const searchTerm = searchInput.value.toLowerCase();
  const sortBy = sortSelect.value;
  let visibleCards = Array.from(petitionCards);

  visibleCards.forEach(card => {
    const title = card.dataset.title;
    const description = card.dataset.description;
    const holder = card.dataset.holder;
    card.style.display = (searchTerm === '' || title.includes(searchTerm) || description.includes(searchTerm) || holder.includes(searchTerm)) ? 'block' : 'none';
  });

  visibleCards = visibleCards.filter(card => card.style.display !== 'none');

  if (sortBy === 'popular') {
    visibleCards.sort((a, b) => parseInt(b.dataset.signatures) - parseInt(a.dataset.signatures));
  } else if (sortBy === 'ending-soon') {
    visibleCards.sort((a, b) => parseInt(a.dataset.end) - parseInt(b.dataset.end));
  } else {
    visibleCards.sort((a, b) => parseInt(b.dataset.date) - parseInt(a.dataset.date));
  }

  const container = document.getElementById('petitionsContainer');
  visibleCards.forEach(card => container.appendChild(card));
}

searchInput.addEventListener('input', filterAndSort);
sortSelect.addEventListener('change', filterAndSort);
</script>
</body>
</html>
