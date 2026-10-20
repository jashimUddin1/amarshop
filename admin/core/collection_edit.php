<?php
require_once '../auth/config.php';
require_admin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = (int)($_POST['collection_id'] ?? 0);
    $title         = trim($_POST['title'] ?? '');
    $slug          = trim($_POST['slug'] ?? '');
    $badge         = trim($_POST['badge'] ?? '');
    $badge_bg      = trim($_POST['badge_bg'] ?? 'bg-danger');
    $percentage_bg = trim($_POST['percentage_bg'] ?? 'bg-dark');

    if ($id <= 0 || empty($title) || empty($slug)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data provided!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE collections SET title = :title, slug = :slug, badge = :badge, badge_bg = :badge_bg, percentage_bg = :percentage_bg WHERE id = :id");
        $result = $stmt->execute([
            ':title'          => $title,
            ':slug'           => $slug,
            ':badge'          => $badge,
            ':badge_bg'       => $badge_bg,
            ':percentage_bg'  => $percentage_bg,
            ':id'             => $id
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Collection updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update collection.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}