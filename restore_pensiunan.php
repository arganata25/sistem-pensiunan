<?php
require_once 'config.php';
$id = intval($_GET['id']);
$mysqli->query("UPDATE pensiunan SET status = 'aktif' WHERE id = $id");
header("Location: arsip.php");
