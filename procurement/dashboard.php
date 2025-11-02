<?php
  // Simple placeholders — replace with real DB queries as needed
  $totalPRs = 128;
  $pendingApproval = 7;
  $poCreated = 54;
  $itemsReceived = 312;

  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Procurement Officer Dashboard - Cabatangan Hardware</title>
  <link href="../assets/third_party/tailwind.min.css" rel="stylesheet">
  <script src="../assets/third_party/feather.min.js"></script>
  <style>
    /* ensure main content is offset by the fixed sidebar width */
    main { margin-left: 240px; padding: 24px; }
  </style>
</head>
<body class="bg-gray-100 font-sans antialiased">

  <?php include __DIR__ . '/includesProc/sidebar.php'; ?>

  <main>
    <header class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-gray-800">Procurement Officer Dashboard</h1>
        <p class="text-sm text-gray-500">Overview of purchase requests, orders and receipts</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="px-4 py-2 bg-teal-600 text-white rounded shadow hover:bg-teal-700">New Purchase Request</button>
        <button class="px-4 py-2 bg-white border rounded hover:bg-gray-50">Filters</button>
      </div>
    </header>

    <!-- KPI Cards -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded shadow flex items-center gap-4">
        <div class="p-3 bg-teal-100 text-teal-700 rounded">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <div class="text-sm text-gray-500">Total PRs</div>
          <div class="text-xl font-bold text-gray-800"><?= number_format($totalPRs) ?></div>
        </div>
      </div>

      <div class="bg-white p-4 rounded shadow flex items-center gap-4">
        <div class="p-3 bg-yellow-100 text-yellow-700 rounded">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg>
        </div>
        <div>
          <div class="text-sm text-gray-500">Pending Approval</div>
          <div class="text-xl font-bold text-gray-800"><?= number_format($pendingApproval) ?></div>
        </div>
      </div>

      <div class="bg-white p-4 rounded shadow flex items-center gap-4">
        <div class="p-3 bg-indigo-100 text-indigo-700 rounded">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M7 17h10"/></svg>
        </div>
        <div>
          <div class="text-sm text-gray-500">POs Created</div>
          <div class="text-xl font-bold text-gray-800"><?= number_format($poCreated) ?></div>
        </div>
      </div>

      <div class="bg-white p-4 rounded shadow flex items-center gap-4">
        <div class="p-3 bg-green-100 text-green-700 rounded">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h2l3 8h8l3-8h2M12 3v7"/></svg>
        </div>
        <div>
          <div class="text-sm text-gray-500">Items Received</div>
          <div class="text-xl font-bold text-gray-800"><?= number_format($itemsReceived) ?></div>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Purchase Requests -->
      <div class="bg-white rounded shadow p-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-800">Recent Purchase Requests</h2>
          <a href="pr_list.php" class="text-sm text-teal-600 hover:underline">View all</a>
        </div>

        <!-- Replace the static rows below with DB-generated rows -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-gray-600 border-b">
              <tr>
                <th class="py-2 px-3">PR #</th>
                <th class="py-2 px-3">Date</th>
                <th class="py-2 px-3">Requested By</th>
                <th class="py-2 px-3">Total Items</th>
                <th class="py-2 px-3">Status</th>
                <th class="py-2 px-3">Action</th>
              </tr>
            </thead>
            <tbody class="text-gray-700">
              <tr class="hover:bg-gray-50">
                <td class="py-2 px-3">PR-2025-0012</td>
                <td class="py-2 px-3">2025-10-30</td>
                <td class="py-2 px-3">R. Santos</td>
                <td class="py-2 px-3">5</td>
                <td class="py-2 px-3"><span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">Pending</span></td>
                <td class="py-2 px-3"><a href="view_pr.php?id=12" class="text-teal-600 hover:underline">Open</a></td>
              </tr>
              <tr class="hover:bg-gray-50">
                <td class="py-2 px-3">PR-2025-0011</td>
                <td class="py-2 px-3">2025-10-28</td>
                <td class="py-2 px-3">M. Cruz</td>
                <td class="py-2 px-3">2</td>
                <td class="py-2 px-3"><span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Approved</span></td>
                <td class="py-2 px-3"><a href="view_pr.php?id=11" class="text-teal-600 hover:underline">Open</a></td>
              </tr>
              <!-- ... dynamic rows ... -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick Actions & Recent Activity -->
      <div class="space-y-6">
        <div class="bg-white rounded shadow p-4">
          <h3 class="text-md font-semibold text-gray-800 mb-3">Quick Actions</h3>
          <div class="grid grid-cols-2 gap-3">
            <a href="create_pr.php" class="block p-3 bg-teal-600 text-white rounded text-center">Create PR</a>
            <a href="suppliers.php" class="block p-3 bg-white border rounded text-center">Manage Suppliers</a>
            <a href="purchase_orders.php" class="block p-3 bg-white border rounded text-center">Purchase Orders</a>
            <a href="goods_received.php" class="block p-3 bg-white border rounded text-center">Goods Received</a>
          </div>
        </div>

        <div class="bg-white rounded shadow p-4">
          <h3 class="text-md font-semibold text-gray-800 mb-3">Recent Activity</h3>
          <ul class="text-sm text-gray-700 space-y-2">
            <li class="flex items-start gap-3">
              <span class="text-xs text-gray-400">10m</span>
              <div>PR-2025-0012 submitted by R. Santos</div>
            </li>
            <li class="flex items-start gap-3">
              <span class="text-xs text-gray-400">3h</span>
              <div>PO-2025-009 generated for PR-2025-0010</div>
            </li>
            <li class="flex items-start gap-3">
              <span class="text-xs text-gray-400">1d</span>
              <div>Items received for PO-2025-007</div>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <footer class="mt-8 text-sm text-gray-500">
      © <?= date('Y') ?> Cabatangan Hardware — Procurement Officer
    </footer>
  </main>

  <script>
    // initialize icons if feather is loaded
    if (window.feather) { feather.replace(); }
  </script>
</body>
</html>