<?php
  // Detect current page for active link styling
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="h-screen w-[240px] fixed top-0 left-0 bg-gray-900 text-white flex flex-col px-4 py-6 shadow-lg">
  <h2 class="text-2xl font-bold text-center mb-8 text-white">HardwarePOS</h2>

  

  <a href="../admin/manage_users.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     hover:bg-gray-800">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M5.121 17.804A6 6 0 0112 15a6 6 0 016.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Manage Users
  </a>

  <a href="../admin/set_product_prices.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'set_product_prices.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2" />
    </svg>
    Set Product Prices
  </a>

  <a href="../admin/sales.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'sales.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 3v18h18M16 9l-4 4-4-4"/>
    </svg>
    Sales
  </a>

  <a href="../admin/analytics.php"
     class="flex items-center gap-3 py-3 px-4 rounded-md transition 
     <?= $currentPage === 'analytics.php' ? 'bg-teal-600' : 'hover:bg-gray-800' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M11 11V3h2v8h-2zm4 0V6h2v5h-2zM5 11V9h2v2H5z"/>
    </svg>
    Analytics
  </a>

  <a href="../admin/upload_logs.php"
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

