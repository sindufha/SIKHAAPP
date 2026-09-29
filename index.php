<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(dashboardUrl());
}
redirect(appUrl('login.php'));
