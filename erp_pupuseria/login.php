<?php
// login.php (Login Screen)
session_start();
require_once 'config/database.php';

$error = '';



// Process the form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = trim($_POST['usuario'] ?? '');
    $password_input = $_POST['contrasena'] ?? '';

    if (!empty($nombre_usuario) && !empty($password_input)) {
        // Look up the user in the DB
        $stmt = $pdo->prepare('SELECT id_usuario, nombre_usuario, contrasena_hash, rol FROM usuarios WHERE nombre_usuario = ? LIMIT 1');
        $stmt->execute([$nombre_usuario]);
        $user = $stmt->fetch();

        // Check whether the user exists and the (hashed) password matches
        if ($user && password_verify($password_input, $user['contrasena_hash'])) {

            // --- OLD SESSION DETECTOR AND CLEANER ---
            session_unset(); // Clears any leftover variables from previous sessions

            // Save data into the new session cleanly
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
            $_SESSION['rol'] = $user['rol'];

            // Redirect to the dashboard
            header('Location: views/dashboard.php');
            exit;
        } else {
            // Wrong username or wrong password: reject the login attempt
            $error = 'Incorrect credentials.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP Dona Mary</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-dark: #1e2532; --accent: #f59e0b; --accent-hover: #d97706; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            overflow: hidden;
            background-color: #000; /* Black background in case the video takes time to load */
        }

        /* 🔥 BACKGROUND VIDEO STYLES */
        .video-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: cover; /* Makes the video fill the screen without distorting */
            z-index: -2; /* Sends it to the very back */
            filter: brightness(50%); /* Darkens it a bit so the form stands out more */
        }

        /* Extra dark layer in case the video is too bright */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }

        /* Slightly tweak the card to give it an elegant translucent touch */
        .login-card { 
            background: rgba(255, 255, 255, 0.92); 
            backdrop-filter: blur(10px); /* Blur effect behind the card */
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
            z-index: 10;
        }

        h2 { margin-bottom: 5px; font-style: italic; color: var(--bg-dark); }
        p { color: #4b5563; font-size: 14px; margin-bottom: 25px; font-weight: 500; }
        .form-group { text-align: left; margin-bottom: 15px; }
        label { font-size: 12px; font-weight: 600; color: #4b5563; text-transform: uppercase; }
        input { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: #fff; }
        .btn-login { width: 100%; background: var(--accent); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 15px; transition: 0.2s; }
        .btn-login:hover { background: var(--accent-hover); }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; font-weight: 600; }
    </style>
</head>
<body>

    <video autoplay muted loop playsinline class="video-bg">
        <source src="views/assets/video.mp4" type="video/mp4">
        Your browser does not support background videos.
    </video>
    
    <div class="overlay"></div>

    <div class="login-card">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:50px; height:50px; color:#f59e0b; margin: 0 auto 10px;">
            <circle cx="12" cy="13" r="8" fill="#f59e0b" opacity="0.2"/>
            <ellipse cx="12" cy="14" rx="7" ry="5" fill="none" stroke="#f59e0b" stroke-width="2"/>
            <path d="M10 6 Q 11 4 10 2 M13 7 Q 14 4 13 3" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" fill="none"/>
        </svg>
        <h2>Pupuseria Dona Mary</h2>
        <p>Sign in to access the ERP</p>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="usuario" placeholder="E.g. mary_admin" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="contrasena" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">LOG IN TO THE SYSTEM</button>
        </form>
    </div>

</body>
</html>
