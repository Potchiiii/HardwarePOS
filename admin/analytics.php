<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../db.php';

// Ollama API Configuration (Local)
define('OLLAMA_API_URL', 'http://localhost:11434/api/generate'); // Default Ollama endpoint
define('OLLAMA_MODEL', 'tinyllama'); // Your local model

// Get date range
$from_date = isset($_GET['from']) ? $_GET['from'] : date('Y-01-01');
$to_date = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// Fetch comprehensive sales data for AI analysis
function getSalesDataForAI($pdo, $from_date, $to_date) {
    $data = [];
    
    // Overall sales summary
    $summary_query = "SELECT 
        COUNT(DISTINCT id) as total_transactions,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_transaction,
        MIN(total_amount) as min_transaction,
        MAX(total_amount) as max_transaction,
        DATE(MIN(sale_date)) as first_sale,
        DATE(MAX(sale_date)) as last_sale
        FROM inventory_logs 
        WHERE sale_date BETWEEN ? AND ?";
    $stmt = $pdo->prepare($summary_query);
    $stmt->execute([$from_date, $to_date . ' 23:59:59']);
    $data['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Daily sales trend
    $daily_query = "SELECT 
        DATE(sale_date) as date,
        COUNT(*) as transactions,
        SUM(total_amount) as revenue
        FROM inventory_logs 
        WHERE sale_date BETWEEN ? AND ?
        GROUP BY DATE(sale_date)
        ORDER BY date";
    $stmt = $pdo->prepare($daily_query);
    $stmt->execute([$from_date, $to_date . ' 23:59:59']);
    $data['daily_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Category performance
    $category_query = "SELECT 
        i.category,
        COUNT(DISTINCT s.id) as transactions,
        SUM(si.quantity) as units_sold,
        SUM(si.quantity * si.price) as revenue
        FROM inventory i
        JOIN inventory_log_items si ON i.id = si.inventory_id
        JOIN inventory_logs s ON si.sale_id = s.id
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY i.category
        ORDER BY revenue DESC
        LIMIT 8";
    $stmt = $pdo->prepare($category_query);
    $stmt->execute([$from_date, $to_date . ' 23:59:59']);
    $data['category_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top products
    $products_query = "SELECT 
        i.name,
        i.brand,
        i.category,
        SUM(si.quantity) as units_sold,
        SUM(si.quantity * si.price) as revenue
        FROM inventory i
        JOIN inventory_log_items si ON i.id = si.inventory_id
        JOIN inventory_logs s ON si.sale_id = s.id
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY i.id
        ORDER BY revenue DESC
        LIMIT 10";
    $stmt = $pdo->prepare($products_query);
    $stmt->execute([$from_date, $to_date . ' 23:59:59']);
    $data['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cashier performance
    $cashier_query = "SELECT 
        u.username as cashier,
        COUNT(*) as transactions,
        SUM(s.total_amount) as revenue,
        AVG(s.total_amount) as avg_transaction
        FROM inventory_logs s
        JOIN users u ON s.user_id = u.id
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY u.id, u.username
        ORDER BY revenue DESC
        LIMIT 10";
    $stmt = $pdo->prepare($cashier_query);
    $stmt->execute([$from_date, $to_date . ' 23:59:59']);
    $data['cashier_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Current inventory status
    $inventory_query = "SELECT 
        category,
        COUNT(*) as total_products,
        SUM(quantity) as total_stock,
        AVG(per_unit) as avg_price
        FROM inventory
        GROUP BY category";
    $data['inventory_status'] = $pdo->query($inventory_query)->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

// Call Ollama API for analysis (Local)
function getAIAnalysis($sales_data, $user_question = null) {
    $data_summary = "You are an expert business analyst. Analyze this sales data. All currency amounts are in Philippine peso (PHP, ₱).\n\n";
    $data_summary .= "Period: " . $sales_data['summary']['first_sale'] . " to " . $sales_data['summary']['last_sale'] . "\n";
    $data_summary .= "Total Revenue: ₱" . number_format($sales_data['summary']['total_revenue'], 2) . "\n";
    $data_summary .= "Total Transactions: " . $sales_data['summary']['total_transactions'] . "\n";
    $data_summary .= "Average Transaction: ₱" . number_format($sales_data['summary']['avg_transaction'], 2) . "\n\n";
    
    $data_summary .= "Category Performance:\n";
    foreach ($sales_data['category_performance'] as $cat) {
        $data_summary .= "- {$cat['category']}: ₱" . number_format($cat['revenue'], 2) . " ({$cat['units_sold']} units)\n";
    }
    
    $data_summary .= "\nTop Products:\n";
    foreach ($sales_data['top_products'] as $prod) {
        $data_summary .= "- {$prod['brand']} {$prod['name']}: ₱" . number_format($prod['revenue'], 2) . " ({$prod['units_sold']} units)\n";
    }
    
    $data_summary .= "\nCashier Performance:\n";
    foreach ($sales_data['cashier_performance'] as $cashier) {
        $data_summary .= "- {$cashier['cashier']}: ₱" . number_format($cashier['revenue'], 2) . " ({$cashier['transactions']} transactions)\n";
    }
    
    if ($user_question) {
        $full_prompt = $data_summary . "\n\nQuestion: " . $user_question . "\n\nProvide a clear, detailed analysis answering this question based on the data above. Format your response with clear sections and bullet points.";
    } else {
        $full_prompt = $data_summary . "\n\nAs a business analyst, provide:\n";
        $full_prompt .= "1. Key insights and trends\n";
        $full_prompt .= "2. Performance highlights\n";
        $full_prompt .= "3. Areas of concern or opportunity\n";
        $full_prompt .= "4. Actionable recommendations\n\n";
        $full_prompt .= "Format your response in clear sections with headers. Be specific and data-driven.";
    }
    
    $request_data = [
        'model' => OLLAMA_MODEL,
        'prompt' => $full_prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.5,
            'num_predict' => 512,
        ]
    ];
    
    $ch = curl_init(OLLAMA_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Fail fast if Ollama isn't reachable
    curl_setopt($ch, CURLOPT_TIMEOUT, 90); // 90 second timeout for AI analysis
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return "Error: Connection failed - " . $curl_error . "\n\nMake sure Ollama is running (ollama serve) and the model is installed (ollama pull " . OLLAMA_MODEL . ")";
    }
    
    if ($http_code !== 200) {
        return "Error: API returned HTTP code $http_code. Make sure Ollama is running on http://localhost:11434";
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['response'])) {
        return $result['response'];
    }
    
    if (isset($result['error'])) {
        return "Error: " . $result['error'];
    }
    
    return "Error: Unable to parse AI response. Response: " . substr($response, 0, 200);
}

// Handle AJAX request for AI analysis
if (isset($_POST['get_ai_analysis'])) {
    // Clear any output buffers and suppress errors in JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    header('Content-Type: application/json');
    
    try {
        $sales_data = getSalesDataForAI($pdo, $from_date, $to_date);
        $user_question = isset($_POST['question']) ? $_POST['question'] : null;
        $analysis = getAIAnalysis($sales_data, $user_question);
        
        // Check if analysis contains an error
        if (strpos($analysis, 'Error:') === 0) {
            echo json_encode(['success' => false, 'error' => $analysis]);
        } else {
            echo json_encode(['success' => true, 'analysis' => $analysis]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
    }
    
    ob_end_flush();
    exit();
}

// Get all the existing data
$today_sales_query = "SELECT COALESCE(SUM(total_amount), 0) as today_sales FROM inventory_logs WHERE DATE(sale_date) = CURDATE()";
$today_sales = $pdo->query($today_sales_query)->fetch()['today_sales'];

$this_month_query = "SELECT COALESCE(SUM(total_amount), 0) as month_sales FROM inventory_logs WHERE YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())";
$this_month_sales = $pdo->query($this_month_query)->fetch()['month_sales'];

$last_month_query = "SELECT COALESCE(SUM(total_amount), 0) as last_month_sales FROM inventory_logs WHERE YEAR(sale_date) = YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(sale_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)";
$last_month_sales = $pdo->query($last_month_query)->fetch()['last_month_sales'];

$growth = 0;
if ($last_month_sales > 0) {
    $growth = (($this_month_sales - $last_month_sales) / $last_month_sales) * 100;
}

$ytd_query = "SELECT COALESCE(SUM(total_amount), 0) as ytd_sales FROM inventory_logs WHERE YEAR(sale_date) = YEAR(CURDATE())";
$ytd_sales = $pdo->query($ytd_query)->fetch()['ytd_sales'];

$avg_transaction_query = "SELECT COALESCE(AVG(total_amount), 0) as avg_transaction FROM inventory_logs WHERE sale_date BETWEEN ? AND ?";
$avg_transaction_stmt = $pdo->prepare($avg_transaction_query);
$avg_transaction_stmt->execute([$from_date, $to_date . ' 23:59:59']);
$avg_transaction = $avg_transaction_stmt->fetch()['avg_transaction'];

$monthly_sales_query = "SELECT MONTH(sale_date) as month, MONTHNAME(sale_date) as month_name, COALESCE(SUM(total_amount), 0) as total_sales FROM inventory_logs WHERE YEAR(sale_date) = YEAR(CURDATE()) GROUP BY MONTH(sale_date), MONTHNAME(sale_date) ORDER BY MONTH(sale_date)";
$monthly_data = $pdo->query($monthly_sales_query)->fetchAll();

$category_sales_query = "SELECT i.name, i.brand, COALESCE(SUM(si.quantity * si.price), 0) as total_sales FROM inventory_log_items si LEFT JOIN inventory i ON i.id = si.inventory_id LEFT JOIN inventory_logs s ON si.sale_id = s.id WHERE s.sale_date BETWEEN ? AND ? GROUP BY i.id, i.name, i.brand HAVING total_sales > 0 ORDER BY total_sales DESC";
$category_sales_stmt = $pdo->prepare($category_sales_query);
$category_sales_stmt->execute([$from_date, $to_date . ' 23:59:59']);
$category_data = $category_sales_stmt->fetchAll();

$top_products_query = "SELECT i.name, i.brand, COALESCE(SUM(si.quantity), 0) as total_quantity FROM inventory i LEFT JOIN inventory_log_items si ON i.id = si.inventory_id LEFT JOIN inventory_logs s ON si.sale_id = s.id WHERE s.sale_date BETWEEN ? AND ? GROUP BY i.id, i.name, i.brand HAVING total_quantity > 0 ORDER BY total_quantity DESC LIMIT 5";
$top_products_stmt = $pdo->prepare($top_products_query);
$top_products_stmt->execute([$from_date, $to_date . ' 23:59:59']);
$top_products = $top_products_stmt->fetchAll();

$recent_transactions_query = "SELECT s.sale_date, s.id as sale_id, s.total_amount, u.username as cashier, GROUP_CONCAT(CONCAT(i.brand, ' - ', i.name, ' (', si.quantity, ')') SEPARATOR ', ') as items FROM inventory_logs s JOIN users u ON s.user_id = u.id LEFT JOIN inventory_log_items si ON s.id = si.sale_id LEFT JOIN inventory i ON si.inventory_id = i.id WHERE s.sale_date BETWEEN ? AND ? GROUP BY s.id, s.sale_date, s.total_amount, u.username ORDER BY s.sale_date DESC LIMIT 10";
$recent_transactions_stmt = $pdo->prepare($recent_transactions_query);
$recent_transactions_stmt->execute([$from_date, $to_date . ' 23:59:59']);
$recent_transactions = $recent_transactions_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Report & AI Analytics (Ollama Local)</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      display: flex;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f9f9;
      color: #333;
      min-height: 100vh;
    }
    .content {
      margin-left: 220px;
      padding: 30px;
      flex: 1;
    }
    h2.page-title {
      margin-bottom: 20px;
      font-size: 24px;
    }
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

    /* AI Analyst Section */
    .ai-analyst-section {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 40px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      color: white;
    }
    .ai-analyst-section h3 {
      margin-bottom: 20px;
      font-size: 22px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .ai-analyst-section h3::before {
      content: "🤖";
      font-size: 28px;
    }
    .ai-analyst-section h3::after {
      content: "LOCAL";
      font-size: 11px;
      background: rgba(255,255,255,0.3);
      padding: 4px 8px;
      border-radius: 4px;
      margin-left: auto;
    }
    .ai-chat-container {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .ai-question-input {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }
    .ai-question-input input {
      flex: 1;
      padding: 12px;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 8px;
      font-size: 14px;
      background: rgba(255,255,255,0.9);
      color: #333;
    }
    .ai-question-input input::placeholder {
      color: #999;
    }
    .ai-question-input button {
      padding: 12px 24px;
      background: white;
      color: #667eea;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .ai-question-input button:hover {
      transform: scale(1.05);
    }
    .ai-question-input button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    .ai-response {
      background: white;
      color: #333;
      padding: 20px;
      border-radius: 8px;
      line-height: 1.6;
      max-height: 500px;
      overflow-y: auto;
    }
    .ai-response h4 {
      color: #667eea;
      margin-top: 15px;
      margin-bottom: 10px;
    }
    .ai-response ul {
      margin-left: 20px;
      margin-top: 10px;
    }
    .ai-response li {
      margin-bottom: 8px;
    }
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Skeleton loader styles */
    .ai-skeleton { padding: 4px 0; }
    .skeleton {
      display: block;
      width: 100%;
      height: 12px;
      background: linear-gradient(90deg, #eeeeee 25%, #f5f5f5 37%, #eeeeee 63%);
      background-size: 400% 100%;
      border-radius: 6px;
      animation: skeleton-shimmer 1.2s ease-in-out infinite;
    }
    .skeleton-title { height: 18px; margin: 12px 0 8px; }
    .skeleton-line { height: 12px; margin: 8px 0; }
    @keyframes skeleton-shimmer {
      0% { background-position: 100% 0; }
      100% { background-position: -100% 0; }
    }
    .quick-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .quick-action-btn {
      padding: 8px 16px;
      background: rgba(255,255,255,0.2);
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 20px;
      color: white;
      cursor: pointer;
      font-size: 13px;
      transition: background 0.2s;
    }
    .quick-action-btn:hover {
      background: rgba(255,255,255,0.3);
    }

    .charts {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-bottom: 20px;
    }
    .chart-wrapper {
      width: 100%;
      margin: 0;
      min-height: 280px;
    }
    canvas {
      background: #fff;
      border-radius: 6px;
      padding: 6px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      max-height: 280px;
    }
    .section {
      margin-bottom: 25px;
    }
    .section h3 {
      margin-bottom: 15px;
      font-size: 18px;
    }
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
  </style>
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>

  <div class="content">
    <h2 class="page-title">Sales Report & AI Analytics</h2>

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
    </div>

    <!-- AI Business Analyst Section -->
    <div class="ai-analyst-section">
      <h3>AI Business Analyst</h3>
      <div class="ai-chat-container">
        <div class="ai-question-input">
          <input type="text" id="aiQuestion" placeholder="Ask the AI analyst anything about your sales data...">
          <button id="askAI">Analyze</button>
        </div>
        <div class="quick-actions">
          <button class="quick-action-btn" data-question="Give me a comprehensive business analysis"> Full Analysis</button>
          <button class="quick-action-btn" data-question="What recommendations do you have to increase sales?"> Recommendations</button>
          <button class="quick-action-btn" data-question="Analyze the daily sales trends"> Trends</button>
        </div>
      </div>
      <div id="aiResponse" class="ai-response" style="display:none;">
        <p>Ask me anything about your sales data!</p>
      </div>
    </div>

    <div class="filter-bar">
      <form method="GET" style="display: flex; gap: 15px; align-items: center;">
        <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($from_date); ?>"></label>
        <label>To   <input type="date" name="to" value="<?php echo htmlspecialchars($to_date); ?>"></label>
        <button type="submit">Apply</button>
      </form>
    </div>

    <div class="charts">
      <div class="chart-wrapper">
        <canvas id="monthlyLineChart" height="200"></canvas>
      </div>
      <div class="chart-wrapper">
        <canvas id="categoryPieChart" height="200"></canvas>
      </div>
      <div class="chart-wrapper">
        <canvas id="topProductsBar" height="200"></canvas>
      </div>
    </div>

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
  const monthlyData = <?php echo json_encode($monthly_data); ?>;
  const categoryData = <?php echo json_encode($category_data); ?>;
  const topProductsData = <?php echo json_encode($top_products); ?>;

  // Chart code (same as before)
  const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const monthlySales = new Array(12).fill(0);
  const monthlyLabels = [];
  
  monthlyData.forEach(item => {
    monthlySales[item.month - 1] = parseFloat(item.total_sales);
  });
  
  const currentMonth = new Date().getMonth();
  for (let i = 0; i <= Math.max(currentMonth, Math.max(...monthlyData.map(item => item.month - 1))); i++) {
    monthlyLabels.push(monthNames[i]);
  }
  
  new Chart(document.getElementById('monthlyLineChart'), {
    type: 'line',
    data: {
      labels: monthlyLabels,
      datasets: [{
        label: 'Sales (₱)',
        data: monthlySales.slice(0, monthlyLabels.length),
        borderColor: '#2980b9',
        tension: 0.4,
        fill: false
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: { display: true, text: 'Monthly Sales Trend' }
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

  const itemLabels = categoryData.map(item => `${item.brand} - ${item.name}`);
  const itemSales = categoryData.map(item => parseFloat(item.total_sales));
  const itemColors = ['#1abc9c','#e67e22','#3498db','#9b59b6','#e74c3c','#f39c12','#2ecc71','#e74c3c','#3498db','#f39c12'];

  new Chart(document.getElementById('categoryPieChart'), {
    type: 'pie',
    data: {
      labels: itemLabels.length > 0 ? itemLabels : ['No Data'],
      datasets: [{
        data: itemSales.length > 0 ? itemSales : [1],
        backgroundColor: itemColors.slice(0, Math.max(itemLabels.length, 1))
      }]
    },
    options: {
      responsive: true,
      plugins: { 
        legend: { position: 'bottom' },
        title: { display: true, text: 'Sales by Item' }
      }
    }
  });

  const productLabels = topProductsData.map(item => `${item.brand} - ${item.name}`);
  const productQuantities = topProductsData.map(item => parseInt(item.total_quantity));

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
          ticks: { stepSize: 1 }
        }
      },
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Top Selling Products' }
      }
    }
  });

  // AI Analyst functionality
  const aiQuestionInput = document.getElementById('aiQuestion');
  const askAIBtn = document.getElementById('askAI');
  const aiResponseDiv = document.getElementById('aiResponse');
  const quickActionBtns = document.querySelectorAll('.quick-action-btn');

  async function getAIAnalysis(question = null) {
    askAIBtn.disabled = true;
    askAIBtn.innerHTML = '<span class="loading-spinner"></span> Analyzing...';
    aiResponseDiv.style.display = 'block';
    aiResponseDiv.innerHTML = `
      <div class="ai-skeleton">
        <div class="skeleton skeleton-title" style="width:55%"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line" style="width:95%"></div>
        <div class="skeleton skeleton-line" style="width:90%"></div>
        <div class="skeleton skeleton-title" style="width:45%;margin-top:12px;"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line" style="width:96%"></div>
        <div class="skeleton skeleton-line" style="width:92%"></div>
        <div class="skeleton skeleton-title" style="width:50%;margin-top:12px;"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line" style="width:98%"></div>
      </div>
    `;

    const formData = new FormData();
    formData.append('get_ai_analysis', '1');
    if (question) {
      formData.append('question', question);
    }

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 100000); // 100s timeout to allow 90s for API
      const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData,
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      const responseText = await response.text();
      console.log('Raw response:', responseText); // Debug log
      
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('JSON Parse Error:', parseError);
        console.error('Response was:', responseText.substring(0, 500));
        aiResponseDiv.innerHTML = '<p style="color:#e74c3c;">Error: Invalid response from server</p><pre style="font-size:11px;background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;">' + responseText.substring(0, 1000) + '</pre>';
        askAIBtn.disabled = false;
        askAIBtn.textContent = 'Analyze';
        return;
      }

      if (data.success) {
        // Format the AI response with proper HTML
        let formattedResponse = data.analysis
          .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
          .replace(/\n\n/g, '</p><p>')
          .replace(/\n/g, '<br>')
          .replace(/^#{1,3}\s+(.+)$/gm, '<h4>$1</h4>');
        
        aiResponseDiv.innerHTML = '<p>' + formattedResponse + '</p>';
      } else {
        aiResponseDiv.innerHTML = '<p style="color:#e74c3c;">Error: ' + (data.error || 'Unknown error occurred') + '</p>';
      }
    } catch (error) {
      console.error('Error:', error);
      if (error.name === 'AbortError') {
        aiResponseDiv.innerHTML = '<p style="color:#e74c3c;">Error: The AI request timed out after 90 seconds.</p><p style="font-size:12px;color:#95a5a6;">Make sure Ollama is running and the model is optimized for your hardware.</p>';
      } else {
        aiResponseDiv.innerHTML = '<p style="color:#e74c3c;">Error: ' + error.message + '</p><p style="font-size:12px;color:#95a5a6;">Check console for details. Make sure Ollama is running on http://localhost:11434</p>';
      }
    }

    askAIBtn.disabled = false;
    askAIBtn.textContent = 'Analyze';
  }

  // Handle ask button click
  askAIBtn.addEventListener('click', () => {
    const question = aiQuestionInput.value.trim();
    if (question) {
      getAIAnalysis(question);
    } else {
      getAIAnalysis();
    }
  });

  // Handle enter key in input
  aiQuestionInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      const question = aiQuestionInput.value.trim();
      if (question) {
        getAIAnalysis(question);
      }
    }
  });

  // Handle quick action buttons
  quickActionBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const question = btn.getAttribute('data-question');
      aiQuestionInput.value = question;
      getAIAnalysis(question);
    });
  });

  </script>
</body>
</html>