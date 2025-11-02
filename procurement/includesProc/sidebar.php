<?php
  // Detect current page for active link styling
  $currentPage = basename($_SERVER['PHP_SELF']);
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

  <a href="../procurement/create_pr.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'create_pr.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 4v16m8-8H4"/>
    </svg>
    Create Purchase Request
  </a>

  <a href="../procurement/pr_list.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'pr_list.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 7h18M3 12h18M3 17h18"/>
    </svg>
    Purchase Requests
  </a>

  <a href="../procurement/suppliers.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'suppliers.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M16 11V5a3 3 0 00-6 0v6M5 20h14a2 2 0 002-2v-5a5 5 0 00-5-5H8a5 5 0 00-5 5v5a2 2 0 002 2z"/>
    </svg>
    Suppliers
  </a>

  <a href="../procurement/purchase_orders.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'purchase_orders.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Purchase Orders
  </a>

  <a href="../procurement/goods_received.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'goods_received.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 10h2l3 8h8l3-8h2M12 3v7"/>
    </svg>
    Goods Received
  </a>

  <a href="../procurement/inventory.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'inventory.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 7h18M3 12h18M7 17h10"/>
    </svg>
    Inventory
  </a>

  <a href="../procurement/reports.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'reports.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Reports
  </a>

  <a href="../procurement/upload_logs.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'upload_logs.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Upload Logs
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

