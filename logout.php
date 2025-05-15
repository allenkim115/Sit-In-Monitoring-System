<?php
session_start();
session_unset();
session_destroy();

// Redirect to the appropriate login page
if (isset($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], '/student/') !== false) {
        header('Location: login.php');
    } else if (strpos($_SERVER['HTTP_REFERER'], '/admin/') !== false) {
        header('Location: login.php');
    } else {
        header('Location: login.php');
    }
} else {
    // Default redirect if referer is not available
    header('Location: login.php');
}
exit;
?>