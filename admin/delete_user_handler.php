<?php
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/admin_model.php';

function adminDeleteTextLength($value) {
    return function_exists('mb_strlen')
        ? mb_strlen((string) $value, 'UTF-8')
        : strlen((string) $value);
}

function adminDeleteSafeContext() {
    $search = isset($_POST['search']) && is_string($_POST['search'])
        ? trim($_POST['search'])
        : '';
    $role = isset($_POST['role']) && is_string($_POST['role'])
        ? $_POST['role']
        : '';

    if (adminDeleteTextLength($search) > 100) {
        $search = '';
    }

    if (!in_array($role, array('student', 'admin'), true)) {
        $role = '';
    }

    return array('search' => $search, 'role' => $role);
}

function returnFromAdminDelete($type, $message, $context = array()) {
    $_SESSION['admin_users_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );

    $query = array();
    if (!empty($context['search'])) {
        $query['search'] = $context['search'];
    }
    if (!empty($context['role'])) {
        $query['role'] = $context['role'];
    }

    $destination = 'users.php';
    if (!empty($query)) {
        $destination .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    header('Location: ' . $destination, true, 303);
    exit();
}

$context = adminDeleteSafeContext();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnFromAdminDelete(
        'error',
        'Please use the Registered Users page to delete an account.',
        $context
    );
}

$submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$session_token = isset($_SESSION['admin_user_delete_csrf_token'])
    && is_string($_SESSION['admin_user_delete_csrf_token'])
        ? $_SESSION['admin_user_delete_csrf_token']
        : '';

if (
    $session_token === ''
    || $submitted_token === ''
    || !hash_equals($session_token, $submitted_token)
) {
    returnFromAdminDelete(
        'error',
        'Your form session expired. Please try again.',
        $context
    );
}

$target_user_id = filter_input(
    INPUT_POST,
    'user_id',
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1))
);

if ($target_user_id === false || $target_user_id === null) {
    returnFromAdminDelete(
        'error',
        'The selected user could not be deleted.',
        $context
    );
}

$delete_result = deleteRegisteredUser(
    $conn,
    (int) $target_user_id,
    (int) $_SESSION['user_id']
);

if ($delete_result === 'success') {
    $_SESSION['admin_user_delete_csrf_token'] = bin2hex(random_bytes(32));
    returnFromAdminDelete('success', 'User deleted successfully.', $context);
}

if ($delete_result === 'self') {
    returnFromAdminDelete(
        'error',
        'You cannot delete your own administrator account.',
        $context
    );
}

if ($delete_result === 'last_admin') {
    returnFromAdminDelete(
        'error',
        'The last administrator account cannot be deleted.',
        $context
    );
}

if ($delete_result === 'not_found') {
    returnFromAdminDelete(
        'error',
        'The selected user could not be deleted.',
        $context
    );
}

returnFromAdminDelete(
    'error',
    'The user could not be deleted right now. Please try again.',
    $context
);
?>
