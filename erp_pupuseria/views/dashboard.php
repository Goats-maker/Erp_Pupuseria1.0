<?php
// views/dashboard.php
session_start();

// 1. Security check: if there is no session, kick them out (redirect to login)
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

// 2. Include the secure PDO connection
require_once '../config/database.php';

// --- DYNAMIC QUERIES FOR THE DASHBOARD ---

// KPI 1 and 2: Today's total sales and today's order count
$ventas_hoy = 0.00;
$ordenes_hoy = 0;
try {
    $sql_hoy = "SELECT SUM(total) AS total_ventas, COUNT(id_pedido) AS total_ordenes 
                FROM pedidos 
                WHERE DATE(fecha_pedido) = CURDATE()";
    $stmt_hoy = $pdo->query($sql_hoy);
    $res_hoy = $stmt_hoy->fetch();
    
    $ventas_hoy = $res_hoy['total_ventas'] ?? 0.00;
    $ordenes_hoy = $res_hoy['total_ordenes'] ?? 0;
} catch (PDOException $e) {
    // On failure, silently store or handle the dev error
}

// KPI 3: Low stock alerts for ingredients (raw materials)
$ingredientes_bajos_count = 0;
$nombres_bajos = "All in order";
try {
    // Count how many are running low
    $sql_stock = "SELECT COUNT(*) AS total FROM ingredientes WHERE stock_actual <= stock_minimo";
    $ingredientes_bajos_count = $pdo->query($sql_stock)->fetch()['total'];

    if ($ingredientes_bajos_count > 0) {
        // Fetch the names of the first 2 ingredients running low
        $sql_nombres = "SELECT nombre_ingrediente FROM ingredientes WHERE stock_actual <= stock_minimo LIMIT 2";
        $res_nombres = $pdo->query($sql_nombres)->fetchAll();
        $arr_nombres = array_column($res_nombres, 'nombre_ingrediente');
        $nombres_bajos = implode(', ', $arr_nombres);
    }
} catch (PDOException $e) {
    // Error handling
}

// KPI 4: Total pupusas sold today
$pupusas_vendidas = 0;
try {
    $sql_pupusas = "SELECT SUM(dp.cantidad) AS total_pupusas 
                    FROM detalles_pedidos dp
                    JOIN productos p ON dp.id_producto = p.id_producto
                    JOIN pedidos pe ON dp.id_pedido = pe.id_pedido
                    WHERE p.categoria = 'Pupusas' AND DATE(pe.fecha_pedido) = CURDATE()";
    $pupusas_vendidas = $pdo->query($sql_pupusas)->fetch()['total_pupusas'] ?? 0;
} catch (PDOException $e) {
}

// TABLE: Get the last 5 orders placed
$ultimos_pedidos = [];
try {
    $sql_pedidos = "SELECT p.fecha_pedido, p.id_pedido, p.total, p.estado, u.nombre_usuario, u.rol 
                    FROM pedidos p
                    JOIN usuarios u ON p.id_usuario = u.id_usuario
                    ORDER BY p.fecha_pedido DESC 
                    LIMIT 5";
    $ultimos_pedidos = $pdo->query($sql_pedidos)->fetchAll();
} catch (PDOException $e) {
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pupuseria Dona Mary - ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #1e2532;
            --bg-light: #f4f7f9;
            --accent: #f59e0b; 
            --accent-hover: #d97706;
            --text-dark: #333;
            --text-light: #fff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body { display: flex; height: 100vh; background-color: var(--bg-light); color: var(--text-dark); }

        /* Sidebar */
        .sidebar { width: 250px; background-color: var(--bg-dark); color: var(--text-light); padding: 20px 0; display: flex; flex-direction: column; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-header h2 { font-style: italic; color: var(--text-light); }
        .sidebar-header p { font-size: 12px; color: #9ca3af; margin-top: 5px; }
        
        .menu-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; padding: 10px 20px; margin-top: 10px; }
        .menu-item { padding: 12px 20px; color: #d1d5db; text-decoration: none; display: block; font-size: 14px; transition: 0.2s; }
        .menu-item:hover, .menu-item.active { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent); color: var(--text-light); }

        /* Main Content */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-style: italic; }
        .user-info { font-size: 14px; color: #4b5563; font-weight: 600; }
        
        .btn-new { background-color: var(--accent); color: #fff; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; border-radius: 4px; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-new:hover { background-color: var(--accent-hover); }

        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .card h3 { font-size: 12px; color: #6b7280; text-transform: uppercase; margin-bottom: 10px; }
        .card .value { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .card .trend { font-size: 12px; font-weight: 600; }
        .trend.up { color: var(--success); }
        .trend.down { color: var(--danger); }

        /* Table */
        .table-container { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        .table-title { padding: 20px; font-size: 16px; font-weight: bold; border-bottom: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f9fafb; padding: 15px 20px; font-size: 12px; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px 20px; font-size: 14px; border-bottom: 1px solid #e5e7eb; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .badge.completado { background-color: #d1fae5; color: #065f46; }
        .badge.pendiente { background-color: #fef3c7; color: #92400e; }
        .badge.cancelada { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>PupuseriaDonaMary</h2>
            <p>ERP · DOWNTOWN BRANCH</p>
        </div>
        
        <div class="menu-title">Main</div>
        <a href="dashboard.php" class="menu-item active">Dashboard</a>
        <a href="#" class="menu-item">Point of Sale</a>
        
        <div class="menu-title">Catalogs</div>
        <a href="productos.php" class="menu-item">Menu / Products</a>
        <a href="#" class="menu-item">Ingredients</a>
        <a href="#" class="menu-item">Recipes</a>
        
        <div class="menu-title">Analysis</div>
        <a href="#" class="menu-item">Reports</a>
        
        <div class="menu-title">System</div>
        <a href="#" class="menu-item">Users</a>
        <a href="../index.php" class="menu-item" style="color: var(--danger); margin-top: 20px;">Back To Home🏠</a>

        <a href="../logout.php" class="menu-item" style="color:var(--danger); margin-top:auto;">Log Out</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1>Dashboard</h1>
                <div class="user-info">
                    Signed in as: <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'User'); ?></strong> (<?php echo htmlspecialchars($_SESSION['rol'] ?? 'No Role'); ?>)
                </div>
            </div>
            <a href="punto_de_venta.php" class="btn-new">+ NEW ORDER</a>
        </div>

        <div class="kpi-grid">
            <div class="card">
                <h3>Sales Today</h3>
                <div class="value">$ <?php echo number_format($ventas_hoy, 2); ?></div>
                <div class="trend up">▲ Real-time data</div>
            </div>
            <div class="card">
                <h3>Orders Today</h3>
                <div class="value"><?php echo $ordenes_hoy; ?></div>
                <div class="trend up">▲ Recorded at checkout</div>
            </div>
            <div class="card">
                <h3>Low Stock (Ingredients)</h3>
                <div class="value" style="color: <?php echo ($ingredientes_bajos_count > 0) ? 'var(--danger)' : 'var(--success)'; ?>;">
                    <?php echo $ingredientes_bajos_count; ?>
                </div>
                <div class="trend <?php echo ($ingredientes_bajos_count > 0) ? 'down' : 'up'; ?>">
                    <?php echo htmlspecialchars($nombres_bajos); ?>
                </div>
            </div>
            <div class="card">
                <h3>Pupusas Sold Today</h3>
                <div class="value"><?php echo $pupusas_vendidas; ?></div>
                <div class="trend up">★ Units cooked</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-title">Latest Processed Transactions</div>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date & Time</th>
                        <th>Served By</th>
                        <th>Total Charged</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($ultimos_pedidos) > 0): ?>
                        <?php foreach ($ultimos_pedidos as $pedido): ?>
                            <tr>
                                <td>#<?php echo $pedido['id_pedido']; ?></td>
                                <td><?php echo date('d/m/Y g:i a', strtotime($pedido['fecha_pedido'])); ?></td>
                                <td><?php echo htmlspecialchars($pedido['nombre_usuario']); ?> <span style="color:#9ca3af; font-size:12px;">(<?php echo htmlspecialchars($pedido['rol']); ?>)</span></td>
                                <td><strong>$ <?php echo number_format($pedido['total'], 2); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo strtolower($pedido['estado']); ?>">
                                        <?php echo htmlspecialchars($pedido['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #6b7280; padding: 30px;">
                                No sales have been recorded today yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
