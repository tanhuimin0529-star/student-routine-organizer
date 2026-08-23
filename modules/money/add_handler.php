<?php
// ===================================================================
// add_handler.php
// Processes the Add Transaction form.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "money_model.php";


// -------------------------------------------------------------
// Only allow POST requests
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: add.php");
    exit;
}


// -------------------------------------------------------------
// Get submitted form data
// -------------------------------------------------------------
$transaction_type =
    isset($_POST["transaction_type"])
    ? trim($_POST["transaction_type"])
    : "";

$category =
    isset($_POST["category"])
    ? trim($_POST["category"])
    : "";

$amount =
    isset($_POST["amount"])
    ? trim($_POST["amount"])
    : "";

$transaction_date =
    isset($_POST["transaction_date"])
    ? trim($_POST["transaction_date"])
    : "";

$description =
    isset($_POST["description"])
    ? trim($_POST["description"])
    : "";


// -------------------------------------------------------------
// Validate input
// -------------------------------------------------------------
$errors = validateTransactionInput(
    $transaction_type,
    $category,
    $amount,
    $transaction_date
);


// -------------------------------------------------------------
// If validation failed
// -------------------------------------------------------------
if (!empty($errors)) {

    $message = implode(" ", $errors);

    header(
        "Location: add.php?error=" .
        urlencode($message)
    );

    exit;
}


// -------------------------------------------------------------
// Add transaction
// -------------------------------------------------------------
$success = addTransaction(
    $conn,
    $logged_in_user_id,
    $transaction_type,
    $category,
    (float)$amount,
    $transaction_date,
    $description
);


// -------------------------------------------------------------
// Redirect after insert
// -------------------------------------------------------------
if ($success) {

    header(
        "Location: index.php?success=" .
        urlencode("Transaction added successfully.")
    );

    exit;

} else {

    header(
        "Location: add.php?error=" .
        urlencode("Unable to add transaction. Please try again.")
    );

    exit;
}
?>