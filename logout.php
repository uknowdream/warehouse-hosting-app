<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (logged_in()) audit_log('Logout', (current_user()['email'] ?? '') . ' keluar sistem');
session_destroy();
header('Location: login.php');
exit;
