<?php
// index.php - Entry point router.
// This used to be a static, hard-coded demo page with no session logic,
// which is why users got stuck here: it never actually checked whether
// anyone was logged in, and protected pages redirected back to it.
// Now it simply sends visitors to the right place.
session_start();

if (isset($_SESSION['id_usuario'])) {
    header('Location: views/dashboard.php');
} else {
    header('Location: login.php');
}
exit;
