<?php
// views/recetas.php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit; }
require_once '../config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asociar_ingrediente'])) {
    $id_producto = intval($_POST['id_producto']);
    $id_ingrediente = intval($_POST['id_ingrediente']);
    $cantidad = floatval($_POST['cantidad_ingrediente']);

    if ($id_producto > 0 && $id_ingrediente > 0 && $cantidad > 0) {
        $stmt = $pdo->prepare("INSERT INTO recetas (id_producto, id_ingrediente, cantidad_ingrediente) VALUES (?, ?, ?)");
        $stmt->execute([$id_producto, $id_ingrediente, $cantidad]);
        $mensaje = "🔗 Ingredient successfully linked to the recipe.";
    }
}

// Load lists for selectors and table
$productos = $pdo->query("SELECT id_producto, nombre_producto FROM productos ORDER BY nombre_producto ASC")->fetchAll();
$ingredientes = $pdo->query("SELECT id_ingrediente, nombre_ingrediente, unidad_medida FROM ingredientes ORDER BY nombre_ingrediente ASC")->fetchAll();

$recetas_completas = $pdo->query("
    SELECT r.id_receta, p.nombre_producto, i.nombre_ingrediente, r.cantidad_ingrediente, i.unidad_medida 
    FROM recetas r
    JOIN productos p ON r.id_producto = p.id_producto
    JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
    ORDER BY p.nombre_producto ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERP - Recipes & Formulas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-dark: #1e2532; --bg-light: #f4f7f9; --accent: #f59e0b; --text-dark: #333; --text-light: #fff; --success: #10b981;}
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-light); }
        .sidebar { width: 250px; background-color: var(--bg-dark); color: var(--text-light); padding: 20px 0; }
        .menu-title { font-size: 11px; text-transform: uppercase; color: #6b7280; padding: 10px 20px; }
        .menu-item { padding: 12px 20px; color: #d1d5db; text-decoration: none; display: block; font-size: 14px; }
        .menu-item.active { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent); }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .form-container { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .grid-inputs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px; }
        select, input { padding: 10px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 14px; }
        .btn { background: var(--accent); color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background: #f9fafb; color: #6b7280; text-transform: uppercase; font-size: 12px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="menu-title">Main</div>
        <a href="dashboard.php" class="menu-item">Dashboard</a>
        <a href="punto_de_venta.php" class="menu-item">Point of Sale</a>
        <div class="menu-title">Catalogs</div>
        <a href="productos.php" class="menu-item">Menu / Products</a>
        <a href="recetas.php" class="menu-item active">Recipes</a>
        <div class="menu-title">System</div>
        <a href="../logout.php" class="menu-item" style="color: var(--danger);">Log Out</a>
    </div>

    <div class="main-content">
        <h1>Recipe & Ingredient Association</h1>
        <p style="color:#4b5563; font-size:14px; font-weight:600; margin-bottom:10px;">
            Signed in as: <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></strong> (<?php echo htmlspecialchars($_SESSION['rol']); ?>)
        </p>
        <?php if($mensaje): ?> <p style="color: var(--success); margin: 10px 0; font-weight: bold;"><?php echo $mensaje; ?></p> <?php endif; ?>

        <div class="form-container">
            <h3>Define Ingredient Consumption per Product</h3>
            <form action="recetas.php" method="POST">
                <div class="grid-inputs">
                    <div>
                        <label>Select Final Product:</label>
                        <select name="id_producto" style="margin-top:5px;" required>
                            <?php foreach($productos as $p): ?>
                                <option value="<?php echo $p['id_producto']; ?>"><?php echo htmlspecialchars($p['nombre_producto']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Raw Material Consumed:</label>
                        <select name="id_ingrediente" style="margin-top:5px;" required>
                            <?php foreach($ingredientes as $i): ?>
                                <option value="<?php echo $i['id_ingrediente']; ?>"><?php echo htmlspecialchars($i['nombre_ingrediente']); ?> (<?php echo $i['unidad_medida']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Amount Required per Unit:</label>
                        <input type="number" step="0.001" name="cantidad_ingrediente" placeholder="E.g. 0.050 (50 grams/pounds)" style="margin-top:5px;" required>
                    </div>
                </div>
                <button type="submit" name="asociar_ingrediente" class="btn" style="margin-top:20px;">🔗 Link to Recipe</button>
            </form>
        </div>

        <h3>Cost Structure / General Recipe Book</h3>
        <table style="margin-top:15px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Ingredient Used</th>
                    <th>Amount Needed per Unit</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($recetas_completas) > 0): ?>
                    <?php foreach($recetas_completas as $rec): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($rec['nombre_producto']); ?></strong></td>
                        <td><?php echo htmlspecialchars($rec['nombre_ingrediente']); ?></td>
                        <td><?php echo number_format($rec['cantidad_ingrediente'], 3) . ' ' . $rec['unidad_medida']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#9ca3af;">No recipes have been structured for the pupusas yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
