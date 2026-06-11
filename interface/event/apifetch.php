<?php
require __DIR__ . '/../../connection.php';
require __DIR__ . '/controllerEvent.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$controller = new controllerEvent($conn);

$stmt_event = $controller->fetchEvent($page);

$total_event = $controller->countEvent();

$total_pages = ceil($total_event / 7);

?>