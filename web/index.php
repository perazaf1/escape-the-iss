<?php
require_once __DIR__ . '/php/includes/auth.php';
authStart();

if (!authCheck()) {
    header('Location: /php/auth/login.php');
    exit;
}

header('Location: /php/pages/dashboard.php');
exit;
