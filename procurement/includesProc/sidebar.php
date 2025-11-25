<?php
  // Detect current page for active link styling
  $currentPage = basename($_SERVER['PHP_SELF']);
  
  // Get low stock items count
  require_once '../db.php';
  $lowStockCount = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= low_threshold")->fetch()['count'] ?? 0;
?>
<div class="h-screen w-[240px] fixed top-0 left-0 bg-gray-900 text-white flex flex-col px-4 py-6 shadow-lg">
  <h2 class="text-2xl font-bold text-center mb-8 text-white">Cabatangan Hardware</h2>

  <a href="../procurement/dashboard.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'dashboard.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 3h18v4H3V3zm0 8h10v10H3V11zm12 0h6v6h-6V11z"/>
    </svg>
    Dashboard
  </a>

  <a href="../procurement/low_stock.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition relative
     <?= $currentPage === 'low_stock.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Low Stock Items
    <?php if ($lowStockCount > 0): ?>
    <span class="absolute right-2 top-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
      <?= $lowStockCount ?>
    </span>
    <?php endif; ?>
  </a>

  <a href="../procurement/purchase_orders.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'purchase_orders.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Purchase Orders
  </a>

  <a href="../procurement/suppliers.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'suppliers.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    Suppliers
  </a>

  

  <a href="../procurement/inventory_settings.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'inventory_settings.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Inventory Settings
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

