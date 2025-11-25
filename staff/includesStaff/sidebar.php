<?php
  // Detect current page for active link styling
  $currentPage = basename($_SERVER['PHP_SELF']);
  
  // Get unprocessed notifications count
  require_once '../db.php';
  $unprocessedStmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE sent_to_staff = 1 AND processed = 0");
  $unprocessedCount = $unprocessedStmt->fetch()['count'] ?? 0;
  
  // Get unconfirmed expired items count
  $expiredStmt = $pdo->query(
    "SELECT COUNT(*) as count FROM purchase_orders po
     WHERE po.expiry_date IS NOT NULL 
     AND po.expiry_date < CURDATE()
     AND po.quantity > 0"
  );
  $expiredCount = $expiredStmt->fetch()['count'] ?? 0;
?>

<div class="h-screen w-[240px] fixed top-0 left-0 bg-gray-900 text-white flex flex-col px-4 py-6 shadow-lg">
  <h2 class="text-2xl font-bold text-center mb-8 text-white">HardwarePOS</h2>

  <a href="../staff/inventory.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'inventory.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 7h18M3 12h18M3 17h18"/>
    </svg>
    Inventory
  </a>

  <a href="../staff/notifications.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition relative
     <?= $currentPage === 'notifications.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
    </svg>
    Notifications
    <?php if ($unprocessedCount > 0): ?>
    <span class="absolute right-2 top-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
      <?= $unprocessedCount ?>
    </span>
    <?php endif; ?>
  </a>

  <a href="../staff/expired_items.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition relative
     <?= $currentPage === 'expired_items.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Expired Items
    <?php if ($expiredCount > 0): ?>
    <span class="absolute right-2 top-2 bg-orange-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
      <?= $expiredCount ?>
    </span>
    <?php endif; ?>
  </a>

  
  <a href="#" onclick="logout()"
     class="flex items-center gap-3 mt-auto py-3 px-4 rounded-md text-red-400 font-semibold hover:bg-red-700 hover:text-white transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1"/>
    </svg>
    Logout
  </a>
</div>

<script src="../assets/third_party/tailwind.min.js"></script>
<script src="../assets/third_party/sweetalert.min.js"></script>
<script>
  function logout() {
    swal({
      title: "Logout Confirmation",
      text: "Are you sure you want to logout?",
      icon: "warning",
      buttons: ["Cancel", "Logout"],
      dangerMode: true,
    }).then((willLogout) => {
      if (willLogout) {
        window.location.href = "../includes/logout.php";
      }
    });
  }
</script>

