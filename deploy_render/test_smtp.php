<?php
/**
 * SMTP Test Script - Delete after testing
 */
require_once 'config.php';

echo "Testing SMTP Configuration\n";
echo "==========================\n\n";

echo "SMTP_HOST: " . SMTP_HOST . "\n";
echo "SMTP_PORT: " . SMTP_PORT . "\n";
echo "SMTP_USERNAME: " . SMTP_USERNAME . "\n";
echo "SMTP_PASSWORD: " . (empty(SMTP_PASSWORD) ? "(empty)" : "(set)") . "\n";
echo "SMTP_FROM_EMAIL: " . SMTP_FROM_EMAIL . "\n\n";

if (empty(SMTP_PASSWORD)) {
    echo "❌ ERROR: SMTP_PASSWORD is empty!\n";
    echo "Please set the SMTP_PASSWORD environment variable.\n";
    exit(1);
}

echo "Attempting to send test email...\n";

try {
    require_once __DIR__ . '/vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->setFrom(SMTP_FROM_EMAIL, 'SMTP Test');
    $mail->addAddress(SMTP_USERNAME, 'Test'); // Send to yourself
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test - IRECSTEM 2026';
    $mail->Body = '<p>This is a test email from IRECSTEM 2026.</p>';
    $mail->AltBody = 'This is a test email from IRECSTEM 2026.';

    // Enable debug for troubleshooting
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        echo "$str\n";
    };

    $mail->send();
    echo "\n✅ SUCCESS: Email sent!\n";
} catch (Exception $e) {
    echo "\n❌ FAILED: " . $e->getMessage() . "\n";

    // Check for common Gmail errors
    if (strpos($e->getMessage(), '535') !== false) {
        echo "\n💡 This is a Gmail authentication error.\n";
        echo "Your Gmail App Password may be incorrect or expired.\n";
        echo "To get a new App Password:\n";
        echo "1. Go to https://myaccount.google.com/security\n";
        echo "2. Enable 2-Factor Authentication\n";
        echo "3. Go to App Passwords\n";
        echo "4. Create a new App Password for 'Mail'\n";
    }
    exit(1);
}
