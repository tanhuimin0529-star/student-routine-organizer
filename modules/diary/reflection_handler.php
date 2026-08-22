<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_reflection_content.php';

function diaryReflectionCurrentMonth() {
    return (new DateTimeImmutable('first day of this month'))->format('Y-m');
}

function diaryReflectionValidateMonth($value) {
    if (!is_string($value) || preg_match('/\A[1-9][0-9]{3}-(0[1-9]|1[0-2])\z/D', $value) !== 1) {
        return null;
    }

    $month = DateTimeImmutable::createFromFormat('!Y-m', $value);
    $month_errors = DateTimeImmutable::getLastErrors();
    $has_errors = is_array($month_errors)
        && ($month_errors['warning_count'] > 0 || $month_errors['error_count'] > 0);

    if (!$month || $has_errors || $month->format('Y-m') !== $value) {
        return null;
    }

    return $month;
}

function returnFromDiaryReflection($month, $message, $type = 'error') {
    $safe_month = diaryReflectionValidateMonth($month);
    $redirect_month = $safe_month
        ? $safe_month->format('Y-m')
        : diaryReflectionCurrentMonth();

    $_SESSION['diary_reflection_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );

    header(
        'Location: index.php?month=' . rawurlencode($redirect_month) . '#monthly-reflection',
        true,
        303
    );
    exit();
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnFromDiaryReflection(
        diaryReflectionCurrentMonth(),
        'Please use the Monthly Reflection form to save your reflection.'
    );
}

$submitted_month = isset($_POST['month']) && is_string($_POST['month'])
    ? trim($_POST['month'])
    : '';
$reflection_month = diaryReflectionValidateMonth($submitted_month);

if ($reflection_month === null) {
    returnFromDiaryReflection(
        diaryReflectionCurrentMonth(),
        'Please choose a valid reflection month.'
    );
}

$submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$session_token = isset($_SESSION['diary_reflection_csrf_token'])
    && is_string($_SESSION['diary_reflection_csrf_token'])
        ? $_SESSION['diary_reflection_csrf_token']
        : '';

if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
    returnFromDiaryReflection(
        $submitted_month,
        'Your form session expired. Please try again.'
    );
}

// Rotate the token after a valid submission so it cannot be replayed.
unset($_SESSION['diary_reflection_csrf_token']);

$submitted_content = isset($_POST['content']) && is_string($_POST['content'])
    ? $_POST['content']
    : '';
$content_result = diaryReflectionContentValidate($submitted_content);

if (!$content_result['valid']) {
    returnFromDiaryReflection($submitted_month, $content_result['error']);
}

$normalized_month = $reflection_month->format('Y-m-01');
$saved = saveDiaryMonthlyReflection(
    $conn,
    $logged_in_user_id,
    $normalized_month,
    $content_result['sanitized']
);

if (!$saved) {
    returnFromDiaryReflection(
        $submitted_month,
        'Your monthly reflection could not be saved right now. Please try again.'
    );
}

returnFromDiaryReflection(
    $submitted_month,
    'Monthly reflection saved successfully.',
    'success'
);