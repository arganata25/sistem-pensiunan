<?php
require_once 'config.php';
$id = intval($_GET['id']);
$mysqli->query("UPDATE pensiunan SET status = 'arsip' WHERE id = $id");
log_audit('ARCHIVE_PENSIUNAN', 'pensiunan', $id);
$_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data berhasil dipindahkan ke arsip.'];
header("Location: index.php");
exit;
?>
