<?php
// logout.php
session_start();

// 1. Completely empty the session array
$_SESSION = array();

// 2. Destroy the session cookie in the client's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Prevent the browser from caching this redirect
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

// 5. Redirect to a clean login page
header("Location: ../login.php");
exit();
