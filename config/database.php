<?php
// Ubah sesuai database hosting/cPanel Anda.
// Untuk Vercel/hosting modern, isi lewat Environment Variables:
// DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET.
$db_host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'warehouse_inventory';
$db_user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '';
$db_charset = getenv('DB_CHARSET') ?: 'utf8mb4';
