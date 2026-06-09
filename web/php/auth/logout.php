<?php
require_once __DIR__ . '/../includes/auth.php';
authLogout();
header('Location: /php/auth/login.php');
exit;
