<?php
// logout.php
session_start();
session_unset(); // Clears session variables
session_destroy(); // Fully destroys the session
header('Location: login.php'); // Sends you back to a clean login page
exit;
