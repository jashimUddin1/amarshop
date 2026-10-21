<?php

function ensure_user_addresses_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_addresses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            label VARCHAR(80) NOT NULL DEFAULT 'Home Address',
            address TEXT NOT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_addresses_user (user_id, is_default, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function seed_user_address(PDO $pdo, int $user_id, ?string $legacy_address): void
{
    $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id');
    $count_stmt->execute([':user_id' => $user_id]);
    if ((int)$count_stmt->fetchColumn() > 0 || trim((string)$legacy_address) === '') {
        return;
    }

    $insert_stmt = $pdo->prepare(
        "INSERT INTO user_addresses (user_id, label, address, is_default) VALUES (:user_id, 'Home Address', :address, 1)"
    );
    $insert_stmt->execute([':user_id' => $user_id, ':address' => trim($legacy_address)]);
}
