<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once '../db.php';

// Get date range from URL parameters or set defaults
$from_date = isset($_GET['from']) ? $_GET['from'] : date('Y-01-01');
$to_date = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// Today's sales
$today_sales_query = "SELECT COALESCE(SUM(total_amount), 0) as today_sales 
                      FROM inventory_logs 
                      WHERE DATE(sale_date) = CURDATE()";
$today_sales_result = $pdo->query($today_sales_query);
$today_sales = $today_sales_result->fetch()['today_sales'];

// This month's sales
$this_month_query = "SELECT COALESCE(SUM(total_amount), 0) as month_sales 
                     FROM inventory_logs 
                     WHERE YEAR(sale_date) = YEAR(CURDATE()) 
                     AND MONTH(sale_date) = MONTH(CURDATE())";
$this_month_result = $pdo->query($this_month_query);
$this_month_sales = $this_month_result->fetch()['month_sales'];

// Last month's sales for growth calculation
$last_month_query = "SELECT COALESCE(SUM(total_amount), 0) as last_month_sales 
                     FROM inventory_logs 
                     WHERE YEAR(sale_date) = YEAR(CURDATE() - INTERVAL 1 MONTH) 
                     AND MONTH(sale_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)";
$last_month_result = $pdo->query($last_month_query);
$last_month_sales = $last_month_result->fetch()['last_month_sales'];

// Calculate growth percentage
$growth = 0;
if ($last_month_sales > 0) {
    $growth = (($this_month_sales - $last_month_sales) / $last_month_sales) * 100;
}

// Year-to-date sales
$ytd_query = "SELECT COALESCE(SUM(total_amount), 0) as ytd_sales 
              FROM inventory_logs 
              WHERE YEAR(sale_date) = YEAR(CURDATE())";
$ytd_result = $pdo->query($ytd_query);
$ytd_sales = $ytd_result->fetch()['ytd_sales'];

// Average per transaction
$avg_transaction_query = "SELECT COALESCE(AVG(total_amount), 0) as avg_transaction 
                          FROM inventory_logs 
                          WHERE sale_date BETWEEN ? AND ?";
$avg_transaction_stmt = $pdo->prepare($avg_transaction_query);
$avg_transaction_stmt->execute([$from_date, $to_date . ' 23:59:59']);
$avg_transaction = $avg_transaction_stmt->fetch()['avg_transaction'];

// Monthly sales data for chart
$monthly_sales_query = "SELECT 
                        MONTH(sale_date) as month,
                        MONTHNAME(sale_date) as month_name,
                        COALESCE(SUM(total_amount), 0) as total_sales
                        FROM inventory_logs 
                        WHERE YEAR(sale_date) = YEAR(CURDATE())
                        GROUP BY MONTH(sale_date), MONTHNAME(sale_date)
                        ORDER BY MONTH(sale_date)";
$monthly_sales_result = $pdo->query($monthly_sales_query);
$monthly_data = $monthly_sales_result->fetchAll();

// Category sales data for pie chart
$category_sales_query = "SELECT 
                         i.category,
                         COALESCE(SUM(si.quantity * si.price), 0) as total_sales
                         FROM inventory i
                         LEFT JOIN inventory_log_items si ON i.id = si.inventory_id
                         LEFT JOIN inventory_logs s ON si.sale_id = s.id
                         WHERE s.sale_date BETWEEN ? AND ?
                         GROUP BY i.category
                         ORDER BY total_sales DESC";
$category_sales_stmt = $pdo->prepare($category_sales_query);
$category_sales_stmt->execute([$from_date, $to_date . ' 23:59:59']);
$category_data = $category_sales_stmt->fetchAll();

// Top selling products
$top_products_query = "SELECT 
                       i.name,
                       i.brand,
                       COALESCE(SUM(si.quantity), 0) as total_quantity
                       FROM inventory i
                       LEFT JOIN inventory_log_items si ON i.id = si.inventory_id
                       LEFT JOIN inventory_logs s ON si.sale_id = s.id
                       WHERE s.sale_date BETWEEN ? AND ?
                       GROUP BY i.id, i.name, i.brand
                       ORDER BY total_quantity DESC
                       LIMIT 5";
$top_products_stmt = $pdo->prepare($top_products_query);
$top_products_stmt->execute([$from_date, $to_date . ' 23:59:59']);
$top_products = $top_products_stmt->fetchAll();

// Recent transactions
$recent_transactions_query = "SELECT 
                              s.sale_date,
                              s.id as sale_id,
                              s.total_amount,
                              u.username as cashier,
                              GROUP_CONCAT(CONCAT(i.brand, ' - ', i.name, ' (', si.quantity, ')') SEPARATOR ', ') as items
                              FROM inventory_logs s
                              JOIN users u ON s.user_id = u.id
                              LEFT JOIN inventory_log_items si ON s.id = si.sale_id
                              LEFT JOIN inventory i ON si.inventory_id = i.id
                              GROUP BY s.id, s.sale_date, s.total_amount, u.username
                              ORDER BY s.sale_date DESC
                              LIMIT 10";
$recent_transactions_result = $pdo->query($recent_transactions_query);
$recent_transactions = $recent_transactions_result->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Report & Analytics</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Reset & Base */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      display: flex;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f9f9;
      color: #333;
      min-height: 100vh;
    }

    /* SIDEBAR (../includes/sidebar.php handles its own styling) */
    /* MAIN CONTENT */
    .content {
      margin-left: 220px;
      padding: 30px;
      flex: 1;
    }
    h2.page-title {
      margin-bottom: 20px;
      font-size: 24px;
    }

    /* Date Filter */
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: center;
      margin-bottom: 30px;
    }
    .filter-bar input[type="date"],
    .filter-bar button {
      padding: 6px 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    .filter-bar button {
      background: #2980b9;
      color: #fff;
      border: none;
      cursor: pointer;
    }
    .filter-bar button:hover {
      background: #2573a6;
    }

    /* Summary Cards */
    .summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    .card {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      text-align: center;
    }
    .card h3 {
      font-size: 20px;
      color: #2c3e50;
    }
    .card p {
      margin-top: 8px;
      color: #7f8c8d;
      font-size: 14px;
    }
    .card .growth {
      margin-top: 10px;
      font-size: 12px;
    }
    .card .positive {
      color: #27ae60;
    }
    .card .negative {
      color: #e74c3c;
    }

    /* Charts */
    .charts {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
      margin-bottom: 40px;
    }
    .chart-wrapper {
      max-width: 600px;
      width: 100%;
      margin: 0 auto;
    }
    canvas {
      background: #fff;
      border-radius: 8px;
      padding: 16px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    /* Section Titles */
    .section {
      margin-bottom: 40px;
    }
    .section h3 {
      margin-bottom: 15px;
      font-size: 18px;
    }

    /* Transactions Table */
    .transactions input[type="text"] {
      width: 100%;
      max-width: 300px;
      padding: 6px 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      border-radius: 8px;
      overflow: hidden;
    }
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    th {
      background: #2980b9;
      color: #fff;
      font-weight: normal;
    }
    tr:hover {
      background: #f5f5f5;
    }

    /* Summary Statistics */
    .summary-stats {
      margin: 40px 0;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .stat-value {
      font-size: 24px;
      font-weight: bold;
      color: #2c3e50;
    }

    .stat-label {
      color: #7f8c8d;
      margin: 8px 0;
      font-size: 14px;
    }

    .stat-change {
      font-size: 13px;
      margin-top: 8px;
    }

    .stat-change.positive {
      color: #27ae60;
    }

    .stat-change.negative {
      color: #e74c3c;
    }
  </style>
</head>
<body>

  <?php include 'includes/sidebar.php'; ?>

  <div class="content">
    <h2 class="page-title">Sales Report & Analytics</h2>

    <!-- Date Range Filter -->
    <div class="filter-bar">
      <form method="GET" style="display: flex; gap: 15px; align-items: center;">
        <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($from_date); ?>"></label>
        <label>To   <input type="date" name="to" value="<?php echo htmlspecialchars($to_date); ?>"></label>
        <button type="submit">Apply</button>
      </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary">
      <div class="card">
        <h3>₱<?php echo number_format($today_sales, 2); ?></h3>
        <p>Today's Sales</p>
      </div>
      <div class="card">
        <h3>₱<?php echo number_format($this_month_sales, 2); ?></h3>
        <p>This Month</p>
        <p class="growth <?php echo $growth >= 0 ? 'positive' : 'negative'; ?>">
          <?php echo $growth >= 0 ? '↑' : '↓'; ?> <?php echo number_format(abs($growth), 1); ?>% vs last month
        </p>
      </div>
      <div class="card">
        <h3>₱<?php echo number_format($ytd_sales, 2); ?></h3>
        <p>Year‑to‑Date</p>
      </div>
      <div class="card">
        <h3>₱<?php echo number_format($avg_transaction, 2); ?></h3>
        <p>Avg. per Transaction</p>
      </div>
      <div class="card">
        <h3>₱0.00</h3>
        <p>Refunds</p>
        <small style="color: #7f8c8d; font-size: 11px;">No refund system</small>
      </div>
    </div>

  

    <!-- Charts: Sales Trend & Category Breakdown -->
    <div class="charts">
      <div class="chart-wrapper">
        <canvas id="monthlyLineChart" height="200"></canvas>
      </div>
      <div class="chart-wrapper">
        <canvas id="categoryPieChart" height="200"></canvas>
      </div>
    </div>

    <!-- Top Selling Products -->
    <div class="section">
      <h3>Top Selling Products</h3>
      <div class="chart-wrapper">
        <canvas id="topProductsBar" height="150"></canvas>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="section transactions">
      <h3>Recent Transactions</h3>
      <input type="text" placeholder="Search invoice, item…">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Transaction</th>
            <th>Items</th>
            <th>Amount (₱)</th>
            <th>Cashier</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent_transactions)): ?>
            <tr><td colspan="5" style="text-align: center; color: #7f8c8d;">No transactions found</td></tr>
          <?php else: ?>
            <?php foreach ($recent_transactions as $transaction): ?>
              <tr>
                <td><?php echo date('Y-m-d', strtotime($transaction['sale_date'])); ?></td>
                <td>TXN-<?php echo str_pad($transaction['sale_id'], 5, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($transaction['items'] ?: 'No items'); ?></td>
                <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($transaction['cashier']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
  // PHP data to JavaScript
  const monthlyData = <?php echo json_encode($monthly_data); ?>;
  const categoryData = <?php echo json_encode($category_data); ?>;
  const topProductsData = <?php echo json_encode($top_products); ?>;

  // Prepare monthly sales data
  const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const monthlySales = new Array(12).fill(0);
  const monthlyLabels = [];
  
  monthlyData.forEach(item => {
    monthlySales[item.month - 1] = parseFloat(item.total_sales);
  });
  
  // Only show months that have data or current month
  const currentMonth = new Date().getMonth();
  for (let i = 0; i <= Math.max(currentMonth, Math.max(...monthlyData.map(item => item.month - 1))); i++) {
    monthlyLabels.push(monthNames[i]);
  }
  
  // Monthly Sales Chart
  new Chart(document.getElementById('monthlyLineChart'), {
    type: 'line',
    data: {
      labels: monthlyLabels,
      datasets: [
        {
          label: 'Sales (₱)',
          data: monthlySales.slice(0, monthlyLabels.length),
          borderColor: '#2980b9',
          tension: 0.4,
          fill: false
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'Monthly Sales Trend'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { 
            callback: function(value) {
              return '₱' + value.toLocaleString();
            }
          }
        }
      }
    }
  });

  // Prepare category data
  const categoryLabels = categoryData.map(item => item.category);
  const categorySales = categoryData.map(item => parseFloat(item.total_sales));
  const categoryColors = ['#1abc9c','#e67e22','#3498db','#9b59b6','#e74c3c','#f39c12'];

  // Sales by Category Pie Chart
  new Chart(document.getElementById('categoryPieChart'), {
    type: 'pie',
    data: {
      labels: categoryLabels.length > 0 ? categoryLabels : ['No Data'],
      datasets: [{
        data: categorySales.length > 0 ? categorySales : [1],
        backgroundColor: categoryColors.slice(0, Math.max(categoryLabels.length, 1))
      }]
    },
    options: {
      responsive: true,
      plugins: { 
        legend: { position: 'bottom' },
        title: {
          display: true,
          text: 'Sales by Category'
        }
      }
    }
  });

  // Prepare top products data
  const productLabels = topProductsData.map(item => `${item.brand} - ${item.name}`);
  const productQuantities = topProductsData.map(item => parseInt(item.total_quantity));

  // Top Selling Products Bar Chart
  new Chart(document.getElementById('topProductsBar'), {
    type: 'bar',
    data: {
      labels: productLabels.length > 0 ? productLabels : ['No Data'],
      datasets: [{
        label: 'Units Sold',
        data: productQuantities.length > 0 ? productQuantities : [0],
        backgroundColor: '#3498db',
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      scales: { 
        y: { 
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      },
      plugins: {
        legend: { display: false },
        title: {
          display: true,
          text: 'Top Selling Products'
        },
        tooltip: {
          callbacks: {
            title: ctx => ctx[0].label,
            label: ctx => `Units Sold: ${ctx.formattedValue}`
          }
        }
      }
    }
  });
</script>


</body>
</html>