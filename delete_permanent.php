<?php
require_once 'config.php';
$id = intval($_GET['id']);

// Hapus dokumen terkait
$docs = $mysqli->query("SELECT jalur_file FROM dokumen WHERE pensiunan_id = $id");
while ($doc = $docs->fetch_assoc()) {
    if (file_exists($doc['jalur_file'])) unlink($doc['jalur_file']);
}
$mysqli->query("DELETE FROM dokumen WHERE pensiunan_id = $id");
$mysqli->query("DELETE FROM pensiunan WHERE id = $id");
header("Location: arsip.php");
