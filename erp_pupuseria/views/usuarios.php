<?php
// views/usuarios.php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit; }
require_once '../config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre_usuario']);
    $pass = trim($_POST['contrasena']);
    $rol = $_POST['rol'];

    if (!empty($nombre) && !empty($pass)) {
        // Securely encrypt the password
        $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, contrasena_hash, rol) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $pass_hash, $rol]);
            $mensaje = "✅ System user registered successfully.";
        } catch (PDOException $e) {
            $mensaje = "❌ Error: That username is already in use.";
        }
    }
}

$usuarios = $pdo->query("SELECT id_usuario, nombre_usuario, rol, fecha_registro FROM usuarios ORDER BY id_usuario ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERP - Staff Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-dark: #1e2532; --bg-light: #f4f7f9; --accent: #f59e0b; --text-dark: #333; --text-light: #fff; --success: #10b981; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-light); }
        .sidebar { width: 260px; background-color: var(--bg-dark); color: var(--text-light); padding: 20px 0; }
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .sidebar-header h2 { font-style: italic; font-size: 18px; }
        .menu-title { font-size: 11px; text-transform: uppercase; color: #6b7280; padding: 10px 20px; }
        .menu-item { padding: 11px 20px; color: #d1d5db; text-decoration: none; display: block; font-size: 14px; }
        .menu-item.active { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--accent); }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .form-container { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .grid-inputs { display: grid; grid-template-columns: 2fr 2fr 1.5fr; gap: 15px; margin-top: 10px; }
        input, select { padding: 10px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 14px; }
        .btn { background: var(--accent); color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; }
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
        <a href="reportes.php" class="menu-item">Reports</a>
        <a href="usuarios.php" class="menu-item active">Users</a>
        <a href="../logout.php" class="menu-item" style="color: var(--danger); margin-top: 20px;">Log Out</a>
    </div>

    <div class="main-content">
        <h1>Access & User Control</h1>
        <p style="color:#4b5563; font-size:14px; font-weight:600; margin-bottom:10px;">
            Signed in as: <strong><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></strong> (<?php echo htmlspecialchars($_SESSION['rol']); ?>)
        </p>
        <?php if($mensaje): ?> <p style="color: var(--success); margin: 10px 0; font-weight: bold;"><?php echo $mensaje; ?></p> <?php endif; ?>

        <div class="form-container">
            <h3>Register New Staff Member</h3>
            <form action="usuarios.php" method="POST">
                <div class="grid-inputs">
                    <input type="text" name="nombre_usuario" placeholder="Login username" required>
                    <input type="password" name="contrasena" placeholder="Login password" required>
                    <select name="rol">
                        <option value="Administrator">Administrator</option>
                        <option value="Cashier">Cashier / Staff</option>
                    </select>
                </div>
                <button type="submit" name="crear_usuario" class="btn" style="margin-top: 15px;">+ Create Account</button>
            </form>
        </div>

        <h3>Users Authorized for the ERP</h3>
        <table style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>Account ID</th>
                    <th>Staff Name</th>
                    <th>Assigned Role</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>#<?php echo $u['id_usuario']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['nombre_usuario']); ?></strong></td>
                    <td><span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;"><?php echo $u['rol']; ?></span></td>
                    <td style="color:#6b7280;"><?php echo $u['fecha_registro']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
