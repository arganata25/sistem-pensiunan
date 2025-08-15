<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
    exit;
}

$id = intval($_GET['id']);

// Ambil data lama
$result_old = $mysqli->query("SELECT * FROM pensiunan WHERE id = $id");
$old_data = $result_old->fetch_assoc();

// Update status jadi arsip
$stmt = $mysqli->prepare("UPDATE pensiunan SET status = 'arsip' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    log_audit('ARCHIVE_PENSIUNAN', 'pensiunan', $id, $old_data);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update']);
}
$stmt->close();
