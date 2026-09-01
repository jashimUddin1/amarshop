<?php
require_once'auth/config.php';

// ১. ডাটাবেজ থেকে সব Collections ফেচ করা
$stmt = $pdo->query("SELECT * FROM collections ORDER BY id ASC");
$db_collections = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_collections = [];

foreach ($db_collections as $col) {
    $slug = $col['slug'];

    /* 
     * ডাটাবেজের 'products' টেবিল থেকে ডায়নামিকভাবে প্রোডাক্ট ফেচ করা।
     * শর্ত: আপনার products টেবিলে 'collection_slug' বা 'collection_id' কলাম থাকলে 
     * এটি যেকোনো আনলিমিটেড Custom Collection এর সাথে অটোমেটিক ম্যাচ করবে।
    */
    $p_stmt = $pdo->prepare("SELECT * FROM products WHERE collection_name = :slug ORDER BY id DESC LIMIT 12");
    $p_stmt->execute([':slug' => $slug]);
    $products = $p_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ২. মূল অ্যারে বিল্ড করা
    $all_collections[] = [
        'title'         => $col['title'],
        'slug'          => $slug,
        'products'      => $products, // ১০০% ডায়নামিক প্রোডাক্ট লিস্ট
        'badge'         => $col['badge'] ?? '',
        'badge_bg'      => $col['badge_bg'] ?? 'bg-danger',
        'percentage_bg' => $col['percentage_bg'] ?? 'bg-dark'
    ];
}

// -----------------------------------------------------
// DEBUG: স্ক্রিনে প্রিন্ট করে দেখার জন্য নিচের অংশটুকু
// -----------------------------------------------------
echo "<pre style='background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 8px; font-family: monospace;'>";
echo "=== DATABASE COLLECTIONS FETCHED ===\n";
print_r($db_collections);

print_r($all_collections);
echo "</pre>";
exit(); // পেজের বাকি অংশ লোড হওয়া বন্ধ রাখবে যেন স্পষ্ট দেখা যায়
?>