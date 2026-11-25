<?php
use PHPMailer\PHPMailer\PHPMailer;

function ensure_auth_settings_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `auth` (
            id TINYINT UNSIGNED NOT NULL,
            smtp_host VARCHAR(255) NOT NULL DEFAULT 'smtp.gmail.com',
            smtp_port SMALLINT UNSIGNED NOT NULL DEFAULT 587,
            smtp_encryption VARCHAR(8) NOT NULL DEFAULT 'tls',
            smtp_user VARCHAR(191) NOT NULL DEFAULT '',
            smtp_pass TEXT NULL,
            smtp_from VARCHAR(191) NOT NULL DEFAULT '',
            smtp_name VARCHAR(120) NOT NULL DEFAULT 'AmarShop',
            updated_by_admin_id BIGINT UNSIGNED NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("INSERT IGNORE INTO `auth` (id) VALUES (1)");
}

function smtp_encryption_key(bool $create): ?string
{
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $privateRoot = $documentRoot !== '' ? dirname($documentRoot) : dirname(__DIR__, 3);
    $keyDirectory = $privateRoot . DIRECTORY_SEPARATOR . '.amarshop-private';
    $keyPath = $keyDirectory . DIRECTORY_SEPARATOR . 'amarshop-smtp.key';

    if (!is_file($keyPath)) {
        if (!$create) {
            return null;
        }
        if (!is_dir($keyDirectory) && !mkdir($keyDirectory, 0700, true) && !is_dir($keyDirectory)) {
            throw new RuntimeException('Cannot create the private SMTP key directory outside the web root.');
        }
        $key = random_bytes(32);
        $handle = @fopen($keyPath, 'x');
        if ($handle === false) {
            if (is_file($keyPath)) {
                return smtp_encryption_key(false);
            }
            throw new RuntimeException('Cannot create the private SMTP encryption key.');
        }
        try {
            if (fwrite($handle, $key) !== strlen($key)) {
                throw new RuntimeException('Cannot write the private SMTP encryption key.');
            }
        } finally {
            fclose($handle);
        }
        @chmod($keyPath, 0600);
        return $key;
    }

    $key = file_get_contents($keyPath);
    if (!is_string($key) || strlen($key) !== 32) {
        throw new RuntimeException('The private SMTP encryption key is missing or invalid.');
    }
    return $key;
}

function encrypt_smtp_password(string $password): string
{
    $key = smtp_encryption_key(true);
    if ($key === null) {
        throw new RuntimeException('The private SMTP encryption key is unavailable.');
    }
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($password, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if (!is_string($ciphertext) || strlen($tag) !== 16) {
        throw new RuntimeException('SMTP password encryption failed.');
    }
    return base64_encode($nonce . $tag . $ciphertext);
}

function decrypt_smtp_password(?string $encoded): string
{
    if ($encoded === null || $encoded === '') {
        return '';
    }
    $payload = base64_decode($encoded, true);
    if (!is_string($payload) || strlen($payload) < 29) {
        throw new RuntimeException('Stored SMTP password data is invalid.');
    }
    $key = smtp_encryption_key(false);
    if ($key === null) {
        throw new RuntimeException('The private SMTP encryption key is missing. Restore it from the private server backup.');
    }
    $password = openssl_decrypt(
        substr($payload, 28),
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        substr($payload, 0, 12),
        substr($payload, 12, 16)
    );
    if (!is_string($password)) {
        throw new RuntimeException('Stored SMTP password could not be decrypted.');
    }
    return $password;
}

function get_smtp_settings(PDO $pdo): array
{
    ensure_auth_settings_table($pdo);
    $settings = $pdo->query('SELECT smtp_host, smtp_port, smtp_encryption, smtp_user, smtp_pass, smtp_from, smtp_name FROM `auth` WHERE id = 1')
        ->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        throw new RuntimeException('SMTP settings could not be loaded.');
    }
    $settings['smtp_port'] = (int)$settings['smtp_port'];
    $settings['smtp_pass'] = decrypt_smtp_password($settings['smtp_pass']);
    return $settings;
}

function configure_smtp_mailer(PHPMailer $mail, array $settings): void
{
    if ($settings['smtp_user'] === '' || $settings['smtp_pass'] === '') {
        throw new RuntimeException('SMTP sender credentials are not configured in Admin Settings.');
    }
    $mail->isSMTP();
    $mail->Host = $settings['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['smtp_user'];
    $mail->Password = $settings['smtp_pass'];
    $mail->Port = $settings['smtp_port'];
    $mail->SMTPSecure = match ($settings['smtp_encryption']) {
        'ssl' => PHPMailer::ENCRYPTION_SMTPS,
        'none' => '',
        default => PHPMailer::ENCRYPTION_STARTTLS,
    };
    $mail->setFrom($settings['smtp_from'], $settings['smtp_name']);
}

function send_email_verification_otp(PDO $pdo, int $userId, string $email, string $name): void
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Email verification is unavailable because PHPMailer is not installed.');
    }
    require_once $autoload;

    $settings = get_smtp_settings($pdo);
    if ($settings['smtp_pass'] === '') {
        throw new RuntimeException('Email verification is unavailable because SMTP is not configured in Admin Settings.');
    }
    $otp = (string) random_int(100000, 999999);
    $expiresAt = date('Y-m-d H:i:s', time() + 600);
    $saveOtp = $pdo->prepare("
        UPDATE users
        SET otp_code = :otp, otp_expires_at = :expires_at
        WHERE id = :id AND email = :email AND email_verified = 0 AND verify_status = 1
    ");
    $saveOtp->execute([
        ':otp' => $otp,
        ':expires_at' => $expiresAt,
        ':id' => $userId,
        ':email' => $email,
    ]);
    if ($saveOtp->rowCount() !== 1) {
        throw new RuntimeException('Email verification could not be started for this account.');
    }

    try {
        $mail = new PHPMailer(true);
        configure_smtp_mailer($mail, $settings);
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mail->Subject = 'Verify Your Email - AmarShop';
        $mail->Body = '<h3>Hello ' . $safeName . ',</h3>'
            . '<p>Your AmarShop email verification code is: '
            . '<strong style="font-size:20px;color:#198754;">' . $otp . '</strong></p>'
            . '<p>This code expires in 10 minutes. If you did not create this account, ignore this email.</p>';
        $mail->AltBody = "Your AmarShop email verification code is: {$otp}\nThis code expires in 10 minutes.";
        $mail->send();
    } catch (Throwable $e) {
        $clearOtp = $pdo->prepare('UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE id = :id AND otp_code = :otp');
        $clearOtp->execute([':id' => $userId, ':otp' => $otp]);
        throw $e;
    }
}
