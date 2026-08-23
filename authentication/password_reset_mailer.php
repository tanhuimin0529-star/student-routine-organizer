<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/password_reset_functions.php';

function passwordResetLoadSmtpConfig() {
    $config_path = __DIR__ . '/../config/smtp_config.php';

    if (!is_file($config_path)) {
        return null;
    }

    $config = require $config_path;
    return is_array($config) ? $config : null;
}

function passwordResetLogMailError($exception, $config = null, $sensitive_values = array()) {
    $message = str_replace(array("\r", "\n"), ' ', $exception->getMessage());

    if (is_array($config)) {
        foreach (array('password', 'username', 'sender_email') as $key) {
            if (!empty($config[$key]) && is_string($config[$key])) {
                $message = str_ireplace($config[$key], '[redacted]', $message);
            }
        }
    }

    foreach ($sensitive_values as $sensitive_value) {
        if (is_string($sensitive_value) && $sensitive_value !== '') {
            $message = str_ireplace($sensitive_value, '[redacted]', $message);
        }
    }

    // Keep log entries useful but bounded, and never include the reset token.
    $message = substr($message, 0, 500);
    error_log(
        '[Password reset email] '
        . get_class($exception)
        . ' code ' . (int) $exception->getCode()
        . ': ' . $message
    );
}

function passwordResetBuildEmailLink($raw_token, $config, $reset_page = 'reset_password.php') {
    if (!passwordResetTokenHasValidFormat($raw_token)) {
        throw new RuntimeException('Invalid reset token format.');
    }

    $base_url = isset($config['application_base_url'])
        ? rtrim(trim((string) $config['application_base_url']), '/')
        : '';

    if ($base_url === '' || filter_var($base_url, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('Password reset application URL is not configured.');
    }

    $parts = parse_url($base_url);
    if (
        !is_array($parts)
        || !isset($parts['scheme'], $parts['host'])
        || !in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true)
    ) {
        throw new RuntimeException('Password reset application URL is invalid.');
    }

    if (!in_array($reset_page, array('reset_password.php', 'admin_reset_password.php'), true)) {
        throw new RuntimeException('Password reset page is invalid.');
    }

    return $base_url . '/authentication/' . $reset_page . '?token=' . rawurlencode($raw_token);
}

function passwordResetEncryptionSetting($value) {
    $value = strtolower(trim((string) $value));

    if ($value === 'tls') {
        return PHPMailer::ENCRYPTION_STARTTLS;
    }

    if ($value === 'ssl' || $value === 'smtps') {
        return PHPMailer::ENCRYPTION_SMTPS;
    }

    if ($value === 'none' || $value === '') {
        return '';
    }

    throw new RuntimeException('Unsupported SMTP encryption setting.');
}

function sendPasswordResetEmailForRole($recipient_email, $raw_token, $account_role) {
    $config = passwordResetLoadSmtpConfig();

    try {
        if (!in_array($account_role, array('student', 'admin'), true)) {
            throw new RuntimeException('Unsupported password reset account role.');
        }

        if (!is_array($config) || empty($config['enabled'])) {
            throw new RuntimeException('SMTP delivery is not enabled.');
        }

        $host = isset($config['host']) ? trim((string) $config['host']) : '';
        $port = isset($config['port']) ? filter_var($config['port'], FILTER_VALIDATE_INT) : false;
        $username = isset($config['username']) ? trim((string) $config['username']) : '';
        $password = isset($config['password']) ? (string) $config['password'] : '';
        $sender_email = isset($config['sender_email'])
            ? trim((string) $config['sender_email'])
            : '';
        $sender_name = isset($config['sender_name'])
            ? trim((string) $config['sender_name'])
            : 'Student Routine Organizer';

        if (
            $host === ''
            || $port === false
            || $port < 1
            || $port > 65535
            || $username === ''
            || $password === ''
            || !filter_var($sender_email, FILTER_VALIDATE_EMAIL)
            || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)
        ) {
            throw new RuntimeException('SMTP configuration is incomplete or invalid.');
        }

        $is_admin = $account_role === 'admin';
        $reset_page = $is_admin ? 'admin_reset_password.php' : 'reset_password.php';
        $reset_link = passwordResetBuildEmailLink($raw_token, $config, $reset_page);
        $safe_link = htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8');
        $account_description = $is_admin
            ? 'administrator account'
            : 'account';

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;

        $encryption = passwordResetEncryptionSetting(
            isset($config['encryption']) ? $config['encryption'] : 'tls'
        );
        $mail->SMTPSecure = $encryption;
        if ($encryption === '') {
            $mail->SMTPAutoTLS = false;
        }

        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($sender_email, $sender_name);
        $mail->addAddress($recipient_email);
        $mail->isHTML(true);
        $mail->Subject = $is_admin
            ? 'Student Routine Organizer - Admin Password Reset'
            : 'Student Routine Organizer - Password Reset';
        $mail->Body = '<p>A password reset was requested for your Student Routine Organizer '
            . $account_description . '.</p>'
            . '<p><a href="' . $safe_link . '">Reset your password</a></p>'
            . '<p>This secure link expires in 30 minutes and can only be used once.</p>'
            . '<p>If you did not request this reset, you can safely ignore this email.</p>';
        $mail->AltBody = "A password reset was requested for your Student Routine Organizer "
            . $account_description . ".\n\n"
            . "Reset your password: " . $reset_link . "\n\n"
            . "This secure link expires in 30 minutes and can only be used once.\n"
            . "If you did not request this reset, you can safely ignore this email.";

        return $mail->send();
    } catch (Throwable $exception) {
        passwordResetLogMailError($exception, $config, array($raw_token, $recipient_email));
        return false;
    }
}

function sendStudentPasswordResetEmail($recipient_email, $raw_token) {
    return sendPasswordResetEmailForRole($recipient_email, $raw_token, 'student');
}

function sendAdminPasswordResetEmail($recipient_email, $raw_token) {
    return sendPasswordResetEmailForRole($recipient_email, $raw_token, 'admin');
}
