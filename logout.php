<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(appUrl('login.php'));
}

verifyCsrf();
if (isLoggedIn()) {
    logAudit($pdo, 'LOGOUT', 'User logout');
}

destroySession();
redirect(appUrl('login.php'));
