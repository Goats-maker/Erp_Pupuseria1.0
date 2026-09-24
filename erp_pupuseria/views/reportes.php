<?php
// views/reportes.php
session_start();
if (!isset($_SESSION['id_usuario'])) { 
    header('Location: ../login.php'); 
    exit; 
}
require_once '../config/database.php';

// Basic financial consolidation of historical sales
$totales = $pdo->query("SELECT SUM(total) as ingresos, COUNT(id_pedido) as ordenes, AVG(total) as ticket_promedio FROM pedidos")->fetch();

$monto_ingresos = $totales['ingresos'] ?? 0.00;
$total_ordenes = $totales['ordenes'] ?? 0;
$ticket_promedio = $totales['ticket_promedio'] ?? 0.00;

// List the transaction audit history
$historial_ventas = $pdo->query("
    SELECT p.id_pedido, p.fecha_pedido, p.total, u.nombre_usuario, u.rol, p.estado 
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    ORDER BY p.id_pedido DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERP - Management Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-dark: #1e2532; 
            --bg-light: #f4f7f9; 
            --accent: #f59e0b; 
            --text-dark: #333; 
            --text-light: #fff; 
            --success: #10b981; 
            --danger: #ef4444; /* Variable added for the logout button */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-light); }
        
        /* Sidebar fixed with Flexbox to support the bottom button */
        .sidebar { width: 260px; background-color: var(--bg-dark); color: var(--text-light); padding: 20px 0; display: flex; flex-direction: column; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .sidebar-header h2 { font-style: italic; font-size: 18px; }
        
        .menu-title { font-size: 11px; text-transform: uppercase; color: #6b7280; padding: 10px 20px; }
        .menu-item { padding: 11px 20px; color: #d1d5db; text-decoration: none; display: block; font-size: 14px; transition: 0.2s; }
        .menu-item:hover, .menu-item.active { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent); color: var(--text-light); }
        
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .grid-kpi { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .card-kpi { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .card-kpi h4 { font-size: 11px; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px; }
        .card-kpi p { font-size: 26px; font-weight: 700; margin-top: 5px; color: #1f2937; }
        
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background: #f9fafb; color: #6b7280; text-transform: uppercase; font-size: 11px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:35px; height:35px; color:#f59e0b;"><circle cx="12" cy="13" r="8" fill="#f59e0b" opacity="0.2"/><ellipse cx="12" cy="14" rx="7" ry="5" fill="none" stroke="#f59e0b" stroke-width="2"/><path d="M10 6 Q 11 4 10 2 M13 7 Q 14 4 13 3" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
            <h2>Dona Mary</h2>
        </div>
        <div class="menu-title">Main</div>
        <a href="dashboard.php" class="menu-item">Dashboard</a>
        <a href="punto_de_venta.php" class="menu-item">Point of Sale</a>
        
        <div class="menu-title">Catalogs</div>
        <a href="productos.php" class="menu-item">Menu / Products</a>
        <a href="ingredientes.php" class="menu-item">Ingredients</a>
        <a href="recetas.php" class="menu-item">Recipes</a>
        
        <div class="menu-title">Analysis & System</div>
        <a href="reportes.php" class="menu-item active">Reports</a>
        <a href="usuarios.php" class="menu-item">Users</a>
        
        <a href="../logout.php" class="menu-item" style="color: var(--danger); margin-top: auto; font-weight: bold;">❌ Log Out</a>
    </div>

    <div class="main-content">
        <h1>Sales Reports & Intelligence</h1>
        <p style="color:#4b5563; font-size:14px; font-weight:600;">
            Signed in as: <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></strong> (<?php echo htmlspecialchars($_SESSION['rol']); ?>)
        </p>
        
        <div class="grid-kpi" style="margin-top:20px;">
            <div class="card-kpi">
                <h4>Accumulated Gross Revenue</h4>
                <p style="color:var(--success);">$<?php echo number_format($monto_ingresos, 2); ?></p>
            </div>
            <div class="card-kpi">
                <h4>Transaction Volume</h4>
                <p><?php echo $total_ordenes; ?> Orders</p>
            </div>
            <div class="card-kpi">
                <h4>Average Ticket per Customer</h4>
                <p>$<?php echo number_format($ticket_promedio, 2); ?></p>
            </div>
        </div>

        <h3>Historical Order Log</h3>
        <table style="margin-top:15px;">
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Date & Time</th>
                    <th>Processed By</th>
                    <th>Amount Charged</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($historial_ventas) > 0): ?>
                    <?php foreach ($historial_ventas as $v): ?>
                    <tr>
                        <td>#<?php echo $v['id_pedido']; ?></td>
                        <td style="color:#6b7280;"><?php echo date('d/m/Y g:i a', strtotime($v['fecha_pedido'])); ?></td>
                        <td><?php echo htmlspecialchars($v['nombre_usuario']); ?> <span style="color:#9ca3af; font-size:12px;">(<?php echo htmlspecialchars($v['rol']); ?>)</span></td>
                        <td><strong>$<?php echo number_format($v['total'], 2); ?></strong></td>
                        <td><span style="color:var(--success); font-weight:bold;">● <?php echo htmlspecialchars($v['estado']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#9ca3af;">No business transactions are recorded in the system.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
