<?php
/**
 * PHPMailer Setup Script
 * Checks if PHPMailer is installed in the vendor directory.
 * If not, it attempts to download and extract it.
 */

$vendorPath = __DIR__ . '/../vendor/phpmailer/phpmailer';
$phpMailerEntry = $vendorPath . '/src/PHPMailer.php';

echo "=== PHPMailer Environment Check ===\n";

if (file_exists($phpMailerEntry)) {
    echo "[OK] PHPMailer is already installed at: $vendorPath\n";
    echo "You are ready to send confirmation emails!\n";
    exit(0);
}

echo "[!] PHPMailer not found. Attempting to install...\n";

// Target version
$version = "6.9.1";
$url = "https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v{$version}.tar.gz";

// Create destination
if (!is_dir($vendorPath)) {
    if (!mkdir($vendorPath, 0777, true)) {
        die("[ERROR] Failed to create directory: $vendorPath\n");
    }
}

echo "Downloading PHPMailer v{$version}...\n";

// Use system commands for download and extraction as it's more reliable for binary tars in PHP scripts
$cmd = "curl -L $url | tar xz -C " . escapeshellarg($vendorPath) . " --strip-components=1";
exec($cmd, $output, $returnVar);

if ($returnVar === 0 && file_exists($phpMailerEntry)) {
    echo "[SUCCESS] PHPMailer has been installed successfully!\n";
    echo "Location: $vendorPath\n";
    
    // Ensure logs directory exists too as it's used for fallbacks
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0777, true);
        echo "Created logs directory for email fallbacks.\n";
    }
} else {
    echo "[ERROR] Installation failed. Please check your internet connection or run the following command manually:\n";
    echo "mkdir -p vendor/phpmailer/phpmailer && curl -L $url | tar xz -C vendor/phpmailer/phpmailer --strip-components=1\n";
    exit(1);
}
