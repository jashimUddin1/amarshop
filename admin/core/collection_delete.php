<?php
require_once '../auth/config.php';
require_admin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST is required.']);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$csrf = $_POST['csrf_token'] ?? '';
$expected_csrf = $_SESSION['collection_csrf'] ?? '';
if ($id === false || $id === null || $id <= 0 ||
    !is_string($csrf) || !is_string($expected_csrf) || $expected_csrf === '' ||
    !hash_equals($expected_csrf, $csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid collection delete request. Refresh and try again.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $collection_stmt = $pdo->prepare('SELECT slug FROM collections WHERE id = :id FOR UPDATE');
    $collection_stmt->execute([':id' => $id]);
    $collection = $collection_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$collection) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Collection not found or already deleted.']);
        exit;
    }
    $slug = (string)($collection['slug'] ?? '');
    if ($slug !== '') {
        $pdo->prepare('UPDATE products SET collection_name = \'\' WHERE collection_name = :slug')
            ->execute([':slug' => $slug]);
    }
    $delete_stmt = $pdo->prepare('DELETE FROM collections WHERE id = :id');
    $delete_stmt->execute([':id' => $id]);
    if ($delete_stmt->rowCount() !== 1) {
        throw new RuntimeException('The collection could not be deleted.');
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Collection deleted successfully.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Collection delete failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Collection could not be deleted. Check whether it is still in use, then try again.']);
}