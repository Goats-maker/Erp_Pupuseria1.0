<?php
// views/productos.php
session_start();

// Validate that the user is logged in
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}


require_once '../config/database.php';

// Process product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_producto'])) {
    $id_eliminar = intval($_POST['id_eliminar']);
    
    try {
        $stmt_borrar = $pdo->prepare("DELETE FROM productos WHERE id_producto = ?");
        $stmt_borrar->execute([$id_eliminar]);
        
        $mensaje = "✅ Product successfully removed from the menu.";
        $tipo_mensaje = "success";
        
    } catch (PDOException $e) {
        // Code 23000 = Foreign key constraint (the product already has sales or a recipe)
        if ($e->getCode() == '23000') {
            $mensaje = "❌ You can't delete this product because it already has sales or recipes registered. (The system protects it so it doesn't ruin your reports).";
        } else {
            $mensaje = "❌ Critical error while deleting: " . $e->getMessage();
        }
        $tipo_mensaje = "error";
    }
}

$mensaje = '';
$tipo_mensaje = '';

// LOGIC TO REGISTER A PRODUCT (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = trim($_POST['nombre_producto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $categoria = $_POST['categoria'] ?? '';
    $stock = intval($_POST['stock'] ?? 0);

    if (!empty($nombre) && $precio > 0 && !empty($categoria)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO productos (nombre_producto, descripcion, precio, categoria, stock) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $descripcion, $precio, $categoria, $stock]);
            
            $mensaje = "Product '$nombre' added successfully!";
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "Error saving product: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Please fill in the required fields (Name, Price, and Category).";
        $tipo_mensaje = "error";
    }
}

// LOGIC TO QUERY PRODUCTS (READ)
try {
    $stmt = $pdo->query('SELECT * FROM productos ORDER BY categoria DESC, nombre_producto ASC');
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error querying products: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Pupuseria Dona Mary</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-light); color: var(--text-dark); }

        /* Sidebar (same style as the dashboard) */
        .sidebar { width: 250px; background-color: var(--bg-dark); color: var(--text-light); padding: 20px 0; display: flex; flex-direction: column; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-header h2 { font-style: italic; color: var(--text-light); }
        .sidebar-header p { font-size: 12px; color: #9ca3af; margin-top: 5px; }
        .menu-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; padding: 10px 20px; margin-top: 10px; }
        .menu-item { padding: 12px 20px; color: #d1d5db; text-decoration: none; display: block; font-size: 14px; transition: 0.2s; }
        .menu-item:hover, .menu-item.active { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent); color: var(--text-light); }

        /* Main content */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-style: italic; }

        /* Alerts */
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 5px solid var(--success); }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 5px solid var(--danger); }

        /* Form and split interface */
        .layout-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .panel { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .panel h3 { margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; }

        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 14px; color: #4b5563; font-weight: 600; }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--accent); }
        
        .btn-save { width: 100%; background-color: var(--accent); color: #fff; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 4px; transition: 0.2s; font-size: 14px; }
        .btn-save:hover { background-color: var(--accent-hover); }

        /* Tables */
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f9fafb; padding: 12px 15px; font-size: 11px; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 12px 15px; font-size: 14px; border-bottom: 1px solid #e5e7eb; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: #e5e7eb; color: #374151; }
        .badge.pupusas { background-color: #fef3c7; color: #d97706; }
        .badge.bebidas { background-color: #e0f2fe; color: #0369a1; }

        .btn-delete {
    background-color: var(--danger);
    color: white;
    border: none;
    padding: 7px 12px;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    font-size: 12px;
    transition: 0.2s;
}

.btn-delete:hover {
    background-color: #dc2626;
}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>PupuseriaDonaMary</h2>
            <p>ERP · DOWNTOWN BRANCH</p>
        </div>
        <div class="menu-title">Main</div>
        <a href="dashboard.php" class="menu-item">Dashboard</a>
        <a href="#" class="menu-item">Point of Sale</a>
        <div class="menu-title">Catalogs</div>
        <a href="productos.php" class="menu-item active">Menu / Products</a>
        <a href="#" class="menu-item">Ingredients</a>
        <a href="#" class="menu-title">System</a>
        <a href="../logout.php" class="menu-item" style="color: var(--danger);">Log Out</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h1>Product Menu</h1>
                <div style="color:#4b5563; font-size:14px; font-weight:600; margin-top:5px;">
                    Signed in as: <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></strong> (<?php echo htmlspecialchars($_SESSION['rol']); ?>)
                </div>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="layout-grid">
            <div class="panel">
                <h3>New Product</h3>
                <form action="productos.php" method="POST">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="form-group">
                        <label for="nombre_producto">Product Name *</label>
                        <input type="text" id="nombre_producto" name="nombre_producto" required placeholder="E.g: Chicharron Pupusa">
                    </div>

                    <div class="form-group">
                        <label for="categoria">Category *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Select...</option>
                            <option value="Pupusas">Pupusas</option>
                            <option value="Bebidas">Drinks</option>
                            <option value="Otros">Other (Desserts/Snacks)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="precio">Sale Price (USD) *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0.05" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="stock">Starting Stock (Drinks/combos only)</label>
                        <input type="number" id="stock" name="stock" min="0" value="0">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Description</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Optional details..."></textarea>
                    </div>

                    <button type="submit" class="btn-save">SAVE PRODUCT</button>
                </form>
            </div>

            <div class="panel">
                <h3>Registered Products</h3>
                <?php if (count($productos) > 0): ?>
                    <table>
    <thead>
        <tr>
            <th>Category</th>
            <th>Name</th>
            <th>Price</th>
            <th>Physical Stock</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($productos as $prod): ?>
            <tr>
                <td>
                    <span class="badge <?php echo strtolower($prod['categoria']); ?>">
                        <?php echo htmlspecialchars($prod['categoria']); ?>
                    </span>
                </td>

                <td>
                    <strong>
                        <?php echo htmlspecialchars($prod['nombre_producto']); ?>
                    </strong>
                </td>

                <td>
                    $<?php echo number_format($prod['precio'], 2); ?>
                </td>

                <td>
                    <?php echo ($prod['categoria'] === 'Pupusas')
                        ? 'Raw Material'
                        : $prod['stock'] . ' units'; ?>
                </td>

                <td>
                    <form
                        action="productos.php"
                        method="POST"
                        onsubmit="return confirm(
                            '⚠️ Are you sure you want to delete the product: <?php echo htmlspecialchars($prod['nombre_producto'], ENT_QUOTES); ?>?\\n\\nThis action cannot be undone.'
                        );"
                        style="display: inline;"
                    >

                        <input
                            type="hidden"
                            name="id_eliminar"
                            value="<?php echo $prod['id_producto']; ?>"
                        >

                        <button
                            type="submit"
                            name="eliminar_producto"
                            class="btn-delete"
                        >
                            🗑️ Delete
                        </button>

                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
                <?php else: ?>
                    <p style="color: #6b7280; text-align: center; margin-top: 20px;">No products registered yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
