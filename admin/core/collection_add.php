<?php
require_once '../auth/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $slug          = trim($_POST['slug'] ?? '');
    $badge         = trim($_POST['badge'] ?? '');
    $badge_bg      = trim($_POST['badge_bg'] ?? 'bg-danger');
    $percentage_bg = trim($_POST['percentage_bg'] ?? 'bg-dark');

    if (empty($title) || empty($slug)) {
        echo json_encode(['status' => 'error', 'message' => 'Title and Slug are required!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO collections (title, slug,  badge, badge_bg, percentage_bg) VALUES (:title, :slug, :badge, :badge_bg, :percentage_bg)");
        $result = $stmt->execute([
            ':title'          => $title,
            ':slug'           => $slug,
            ':badge'          => $badge,
            ':badge_bg'       => $badge_bg,
            ':percentage_bg'  => $percentage_bg
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Collection created successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create collection.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}