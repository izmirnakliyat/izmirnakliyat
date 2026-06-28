<?php
require_once __DIR__ . '/includes/require_admin_web.php';

if (!isset($_GET['id'])) {
    header('Location: modules_blog_block.php?silme=hata');
    exit;
}
$id = intval($_GET['id']);
$stmt = $conn->prepare("DELETE FROM blog_blocks WHERE id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    header('Location: modules_blog_block.php?silme=ok');
    exit;
} else {
    header('Location: modules_blog_block.php?silme=hata');
    exit;
} 