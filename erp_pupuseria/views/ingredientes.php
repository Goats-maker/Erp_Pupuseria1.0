<?php
// views/ingredientes.php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit; }
require_once '../config/database.php';

$mensaje = '';

// Process ingredient registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_ingrediente'])) {
    $nombre = $_POST['nombre_ingrediente'];
    $unidad = $_POST['unidad_medida'];
    $costo = floatval($_POST['costo']);
    $stock_act = floatval($_POST['stock_actual']);
    $stock_min = floatval($_POST['stock_minimo']);

    if (!empty($nombre)) {
        $stmt = $pdo->prepare("INSERT INTO ingredientes (nombre_ingrediente, unidad_medida, costo, stock_actual, stock_minimo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $unidad, $costo, $stock_act, $stock_min]);
        $mensaje = "✅ Ingredient registered successfully.";
    }
}

// Query ingredients
$ingredientes = $pdo->query("SELECT * FROM ingredientes ORDER BY nombre_ingrediente ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERP - Ingredients</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-dark: #1e2532; --bg-light: #f4f7f9; --accent: #f59e0b; --text-dark: #333; --text-light: #fff; --danger: #ef4444; --success: #10b981;}
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-light); }
        .sidebar { width: 250px; background-color: var(--bg-dark); color: var(--text-light); padding: 20px 0; }
        .menu-title { font-size: 11px; text-transform: uppercase; color: #6b7280; padding: 10px 20px; }
        .menu-item { padding: 12px 20px; color: #d1d5db; text-decoration: none; display: block; font-size: 14px; }
        .menu-item.active { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent); }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .form-container { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .grid-inputs { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 10px; }
        input, select { padding: 10px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 14px; width: 100%; }
        .btn { background: var(--accent); color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background: #f9fafb; color: #6b7280; text-transform: uppercase; font-size: 12px; }
        .alerta { color: var(--danger); font-weight: bold; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="menu-title">Main</div>
        <a href="dashboard.php" class="menu-item">Dashboard</a>
        <a href="punto_de_venta.php" class="menu-item">Point of Sale</a>
        <div class="menu-title">Catalogs</div>
        <a href="productos.php" class="menu-item">Menu / Products</a>
        <a href="ingredientes.php" class="menu-item active">Ingredients</a>
        <a href="recetas.php" class="menu-item">Recipes</a>
        <div class="menu-title">System</div>
        <a href="../logout.php" class="menu-item" style="color: var(--danger);">Log Out</a>
    </div>

    <div class="main-content">
        <h1>Ingredient Control (Raw Materials)</h1>
        <p style="color:#4b5563; font-size:14px; font-weight:600; margin-bottom:10px;">
            Signed in as: <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></strong> (<?php echo htmlspecialchars($_SESSION['rol']); ?>)
        </p>
        <?php if($mensaje): ?> <p style="color: var(--success); margin: 10px 0; font-weight: bold;"><?php echo $mensaje; ?></p> <?php endif; ?>
        
        <div class="form-container">
            <h3>Register New Ingredient</h3>
            <form action="ingredientes.php" method="POST">
                <div class="grid-inputs">
                    <input type="text" name="nombre_ingrediente" placeholder="E.g. Quesillo" required>
                    <select name="unidad_medida">
                        <optgroup label="Weight">
                            <option value="g">Grams (g)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="lb">Pounds (lb)</option>
                            <option value="oz">Ounces (oz)</option>
                        </optgroup>
                        <optgroup label="Volume">
                            <option value="ml">Milliliters (ml)</option>
                            <option value="L">Liters (L)</option>
                        </optgroup>
                        <optgroup label="Count">
                            <option value="units">Units</option>
                        </optgroup>
                    </select>
                    <input type="number" step="0.01" name="costo" placeholder="Unit Cost ($)" required>
                    <input type="number" step="0.001" name="stock_actual" placeholder="Starting Stock" required>
                    <input type="number" step="0.001" name="stock_minimo" placeholder="Minimum Stock" required>
                </div>
                <button type="submit" name="guardar_ingrediente" class="btn" style="margin-top: 15px;">+ Add Ingredient</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Cost</th>
                    <th>Current Stock</th>
                    <th>Minimum Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ingredientes as $ing): ?>
                <tr>
                    <td>#<?php echo $ing['id_ingrediente']; ?></td>
                    <td><strong><?php echo htmlspecialchars($ing['nombre_ingrediente']); ?></strong></td>
                    <td><?php echo $ing['unidad_medida']; ?></td>
                    <td>$<?php echo number_format($ing['costo'], 2); ?></td>
                    <td><?php echo number_format($ing['stock_actual'], 3); ?></td>
                    <td><?php echo number_format($ing['stock_minimo'], 3); ?></td>
                    <td>
                        <?php if($ing['stock_actual'] <= $ing['stock_minimo']): ?>
                            <span class="alerta">⚠️ Low Stock</span>
                        <?php else: ?>
                            <span style="color: var(--success); font-weight: bold;">✅ OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
