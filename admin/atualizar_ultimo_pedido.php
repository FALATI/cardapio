<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

if (isset($_GET['id'])) {
    $_SESSION['last_order_id'] = (int)$_GET['id'];
}