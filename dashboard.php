<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

checkAuthentication();

$pageTitle = "Dashboard";
include __DIR__ . '/includes/header.php';

// small helper used in activity time display (added to avoid undefined function error)
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($string as $k => & $v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Get sales data for today
$todaySales = $pdo->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM sales WHERE DATE(created_at) = CURDATE()")->fetch();

// Get low stock products
$lowStockProducts = $pdo->query("SELECT name, quantity FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 5")->fetchAll();

// Get top selling products today
$topSelling = $pdo->query("
    SELECT p.name, SUM(si.quantity) as total_quantity 
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN products p ON si.product_id = p.id
    WHERE DATE(s.created_at) = CURDATE()
    GROUP BY p.name
    ORDER BY total_quantity DESC
    LIMIT 5
")->fetchAll();

// Recent activities
$activities = $pdo->query("
    SELECT a.activity_type, a.description, a.created_at, u.username 
    FROM activity_logs a
    JOIN admins u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <div class="date-display"><?php echo date('F j, Y'); ?></div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #4CAF50;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3>Today's Sales</h3>
                <p><?php echo $todaySales['count']; ?> transactions</p>
                <h2>₱<?php echo number_format($todaySales['total'], 2); ?></h2>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #2196F3;">
                <i class="fas fa-pills"></i>
            </div>
            <div class="stat-info">
                <h3>Low Stock</h3>
                <p><?php echo count($lowStockProducts); ?> items</p>
                <h2>Need Reorder</h2>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #FF9800;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3>Monthly Sales</h3>
                <p>Current Month</p>
                <h2>₱<?php 
                    $monthlySales = $pdo->query("SELECT SUM(total_amount) as total FROM sales WHERE MONTH(created_at) = MONTH(CURDATE())")->fetch();
                    echo number_format($monthlySales['total'], 2); 
                ?></h2>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #9C27B0;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Customers</h3>
                <p>Total Registered</p>
                <h2><?php 
                    $customerCount = $pdo->query("SELECT COUNT(*) as count FROM customers")->fetch();
                    echo number_format($customerCount['count']); 
                ?></h2>
            </div>
        </div>
    </div>
    
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Top Selling Products Today</h3>
            <div class="bar-chart-container">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <h3>Recent Activities</h3>
            <div class="activity-list">
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php 
                                $icon = '';
                                if (strpos($activity['activity_type'], 'login') !== false) $icon = 'sign-in-alt';
                                elseif (strpos($activity['activity_type'], 'sale') !== false) $icon = 'shopping-cart';
                                else $icon = 'history';
                            ?>
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <div class="activity-details">
                            <p class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></p>
                            <p class="activity-meta">
                                <span class="activity-user"><?php echo htmlspecialchars($activity['username']); ?></span>
                                <span class="activity-time"><?php echo time_elapsed_string($activity['created_at']); ?></span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="tables-grid">
        <div class="table-card">
            <h3>Low Stock Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockProducts as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>
                                <span class="status-badge warning">Low Stock</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="table-card">
            <h3>Top Selling Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topSelling as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo $product['total_quantity']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Top Products Chart
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProductsChart = new Chart(topProductsCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($p) { return "'".addslashes($p['name'])."'"; }, $topSelling)); ?>],
            datasets: [{
                label: 'Quantity Sold',
                data: [<?php echo implode(',', array_column($topSelling, 'total_quantity')); ?>],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
