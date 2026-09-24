<?php
// views/punto_de_venta.php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit; }
require_once '../config/database.php';

$mensaje = '';
$tipo_mensaje = '';
$abrir_ticket_id = null; // Variable to control automatically opening the PDF

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_venta'])) {
    $productos_venda = $_POST['productos'] ?? []; 
    $total_venta = floatval($_POST['total_venta'] ?? 0);
    $id_usuario = $_SESSION['id_usuario'];

    if (!empty($productos_venda) && $total_venta > 0) {
        try {
            $pdo->beginTransaction();

            $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (total, estado, id_usuario) VALUES (?, 'Pagada', ?)");
            $stmt_pedido->execute([$total_venta, $id_usuario]);
            $id_pedido_nuevo = $pdo->lastInsertId();

            $stmt_detalle = $pdo->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_receta = $pdo->prepare("SELECT id_ingrediente, cantidad_ingrediente FROM recetas WHERE id_producto = ?");
            $stmt_descontar_ingrediente = $pdo->prepare("UPDATE ingredientes SET stock_actual = stock_actual - ? WHERE id_ingrediente = ?");

            foreach ($productos_venda as $id_prod => $cant) {
                $cantidad = intval($cant);
                if ($cantidad <= 0) continue;

                $stmt_p = $pdo->prepare("SELECT precio FROM productos WHERE id_producto = ?");
                $stmt_p->execute([$id_prod]);
                $precio_u = $stmt_p->fetch()['precio'] ?? 0.00;
                $subtotal = $precio_u * $cantidad;

                $stmt_detalle->execute([$id_pedido_nuevo, $id_prod, $cantidad, $precio_u, $subtotal]);

                $stmt_receta->execute([$id_prod]);
                $ingredientes_receta = $stmt_receta->fetchAll();

                foreach ($ingredientes_receta as $ing) {
                    $descuento_total = $ing['cantidad_ingrediente'] * $cantidad;
                    $stmt_descontar_ingrediente->execute([$descuento_total, $ing['id_ingrediente']]);
                }
            }

            $pdo->commit();
            $mensaje = "✅ Order #$id_pedido_nuevo charged successfully for $" . number_format($total_venta, 2) . " and raw materials deducted!";
            $tipo_mensaje = "success";
            
            // 🔥 ADDITION 1: Save the order ID to automatically open it below
            $abrir_ticket_id = $id_pedido_nuevo; 

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "❌ Error processing the sale: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "⚠️ The order is empty.";
        $tipo_mensaje = "error";
    }
}

$productos = $pdo->query("SELECT * FROM productos ORDER BY categoria DESC, nombre_producto ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Point of Sale - Dona Mary</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-dark: #1e2532; --bg-light: #f4f7f9; --accent: #f59e0b; --text-dark: #333; --text-light: #fff; --success: #10b981; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-light); overflow: hidden; }
        .sidebar { width: 260px; background-color: var(--bg-dark); color: var(--text-light); padding: 20px 0; display: flex; flex-direction: column; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .sidebar-header h2 { font-style: italic; font-size: 18px; }
        .menu-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; padding: 10px 20px; }
        .menu-item { padding: 11px 20px; color: #d1d5db; text-decoration: none; display: block; font-size: 14px; }
        .menu-item:hover, .menu-item.active { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent); color: var(--text-light); }
        .pos-container { flex: 1; display: grid; grid-template-columns: 1.5fr 1fr; height: 100vh; }
        .products-section { padding: 30px; overflow-y: auto; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; margin-top: 20px; }
        .product-card { background: #fff; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); cursor: pointer; border: 2px solid transparent; transition: 0.2s; }
        .product-card:hover { border-color: var(--accent); transform: translateY(-2px); }
        .product-card .name { font-weight: 600; font-size: 14px; }
        .product-card .price { color: var(--accent); font-weight: 700; margin-top: 5px; }
        .order-section { background: #fff; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column; padding: 30px; }
        .order-title { font-size: 18px; font-weight: 700; border-bottom: 2px solid #f3f4f6; padding-bottom: 15px; display: flex; justify-content: space-between; }
        .order-items { flex: 1; overflow-y: auto; margin-top: 15px; }
        .order-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .btn-qty { background: #e5e7eb; border: none; width: 24px; height: 24px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .order-summary { border-top: 2px solid #f3f4f6; padding-top: 20px; }
        .summary-row.total { font-size: 24px; font-weight: 700; display: flex; justify-content: space-between; }
        .btn-checkout { width: 100%; background: var(--success); color: #fff; border: none; padding: 15px; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 15px; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; font-weight: 600; }
        .alert.success { background: #d1fae5; color: #065f46; }
        .alert.error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:35px; height:35px; color:#f59e0b;"><circle cx="12" cy="13" r="8" fill="#f59e0b" opacity="0.2"/><ellipse cx="12" cy="14" rx="7" ry="5" fill="none" stroke="#f59e0b" stroke-width="2"/><path d="M10 6 Q 11 4 10 2 M13 7 Q 14 4 13 3" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
            <h2>Dona Mary</h2>
        </div>
        <div style="padding: 0 20px 15px; font-size: 12px; color: #9ca3af;">
            Operating as:<br>
            <strong style="color:#fff;"><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></strong> · <?php echo htmlspecialchars($_SESSION['rol']); ?>
        </div>
        <div class="menu-title">Main</div>
        <a href="dashboard.php" class="menu-item">Dashboard</a>
        <a href="punto_de_venta.php" class="menu-item active">Point of Sale</a>
        <div class="menu-title">Catalogs</div>
        <a href="productos.php" class="menu-item">Menu / Products</a>
        <a href="ingredientes.php" class="menu-item">Ingredients</a>
        <a href="recetas.php" class="menu-item">Recipes</a>
        <div class="menu-title">Analysis & System</div>
        <a href="reportes.php" class="menu-item">Reports</a>
        <a href="usuarios.php" class="menu-item">Users</a>
        <a href="../logout.php" class="menu-item" style="color:var(--danger); margin-top:auto;">Log Out</a>
    </div>

    <div class="pos-container">
        <div class="products-section">
            <h2>Operating Menu</h2>
            <?php if ($mensaje): ?> <div class="alert <?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div> <?php endif; ?>
            <div class="products-grid">
                <?php foreach ($productos as $p): ?>
                    <div class="product-card" onclick="agregarAlCarrito(<?php echo $p['id_producto']; ?>, '<?php echo addslashes($p['nombre_producto']); ?>', <?php echo $p['precio']; ?>)">
                        <div class="name"><?php echo htmlspecialchars($p['nombre_producto']); ?></div>
                        <div class="price">$<?php echo number_format($p['precio'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="order-section">
            <div class="order-title"><span>Current Order</span><span id="items-count" style="color:var(--accent);">0 items</span></div>
            <form action="punto_de_venta.php" method="POST" id="form-pos" style="display:flex; flex-direction:column; flex:1;">
                <input type="hidden" name="procesar_venta" value="1">
                <input type="hidden" name="total_venta" id="input-total-venta" value="0">
                <div class="order-items" id="carrito-lista">
                    <p style="color:#9ca3af; text-align:center; margin-top:50px;" id="carrito-vacio">The order is empty.</p>
                </div>
                <div class="order-summary">
                    <div class="summary-row total"><span>Total:</span><span id="txt-total">$0.00</span></div>
                    <button type="button" class="btn-checkout" style="background: var(--danger); margin-bottom: 10px;" onclick="limpiarOrden()">🔄 NEW ORDER / CLEAR</button>
                    <button type="submit" class="btn-checkout">CHARGE ORDER</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let carrito = {};
        function agregarAlCarrito(id, nombre, precio) {
            if (carrito[id]) { carrito[id].cantidad++; } else { carrito[id] = { nombre: nombre, precio: precio, bandwidth: 1, cantidad: 1 }; }
            renderCarrito();
        }
        function cambiarCantidad(id, cambio) {
            if (!carrito[id]) return;
            carrito[id].cantidad += cambio;
            if (carrito[id].cantidad <= 0) { delete carrito[id]; }
            renderCarrito();
        }
        function renderCarrito() {
            const lista = document.getElementById('carrito-lista');
            const vacioMsg = document.getElementById('carrito-vacio');
            const txtTotal = document.getElementById('txt-total');
            const inputTotal = document.getElementById('input-total-venta');
            const itemsCount = document.getElementById('items-count');

            lista.querySelectorAll('.order-item').forEach(i => i.remove());
            let html = ''; let total = 0; let totalItems = 0; let keys = Object.keys(carrito);

            if (keys.length === 0) {
                vacioMsg.style.display = 'block'; txtTotal.innerText = '$0.00'; inputTotal.value = '0'; itemsCount.innerText = '0 items'; return;
            }
            vacioMsg.style.display = 'none';
            keys.forEach(id => {
                const item = carrito[id]; const sub = item.precio * item.cantidad; total += sub; totalItems += item.cantidad;
                html += `<div class="order-item">
                    <div><strong>${item.nombre}</strong><br>
                        <button type="button" class="btn-qty" onclick="cambiarCantidad(${id}, -1)">-</button>
                        <span>${item.cantidad}</span>
                        <button type="button" class="btn-qty" onclick="cambiarCantidad(${id}, 1)">+</button>
                    </div>
                    <div style="text-align:right;"><strong>$${sub.toFixed(2)}</strong></div>
                    <input type="hidden" name="productos[${id}]" value="${item.cantidad}">
                </div>`;
            });
            vacioMsg.insertAdjacentHTML('afterend', html);
            txtTotal.innerText = `$${total.toFixed(2)}`; inputTotal.value = total.toFixed(2); itemsCount.innerText = `${totalItems} items`;
        }
        function limpiarOrden() {
            if (Object.keys(carrito).length > 0) {
                if (confirm("Are you sure you want to clear the current order?")) {
                    carrito = {}; 
                    renderCarrito(); 
                }
            }
        }

        // 🔥 ADDITION 2: If the charge was successful, PHP triggers this line to open the PDF in a new tab
        <?php if ($abrir_ticket_id): ?>
            window.open('ticket_pdf.php?id=<?php echo $abrir_ticket_id; ?>', '_blank');
        <?php endif; ?>
    </script>
</body>
</html>
