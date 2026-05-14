<?php
// Ubah sesuai database hosting/cPanel Anda.
// Untuk Vercel/hosting modern, isi lewat Environment Variables.
// Didukung: DB_*, MYSQL_*, TIDB_*, dan PLANETSCALE_*.
$db_host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: getenv('TIDB_HOST') ?: getenv('PLANETSCALE_DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: getenv('TIDB_PORT') ?: '';
$db_name = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: getenv('TIDB_DATABASE') ?: getenv('PLANETSCALE_DB') ?: 'warehouse_inventory';
$db_user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: getenv('TIDB_USER') ?: getenv('PLANETSCALE_DB_USERNAME') ?: 'root';
$db_pass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: getenv('TIDB_PASSWORD') ?: getenv('PLANETSCALE_DB_PASSWORD') ?: '';
$db_charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$db_ssl_ca = getenv('DB_SSL_CA') ?: getenv('MYSQL_ATTR_SSL_CA') ?: getenv('PLANETSCALE_SSL_CERT_PATH') ?: '';
