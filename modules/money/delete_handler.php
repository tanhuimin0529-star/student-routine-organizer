<?php
// ===================================================================
// delete_handler.php
// Deletes a Money Tracker transaction belonging to the logged-in user.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "money_model.php";


// -------------------------------------------------------------
// Only allow POST requests
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;
}


// -------------------------------------------------------------
// Get transaction ID
// -------------------------------------------------------------
$transaction_id =
    isset($_POST["transaction_id"])
    ? (int)$_POST["transaction_id"]
    : 0;


// -------------------------------------------------------------
// Validate transaction ID
// -------------------------------------------------------------
if ($transaction_id <= 0) {

    header(
        "Location: index.php?error=" .
        urlencode("Invalid transaction.")
    );

    exit;
}


// -------------------------------------------------------------
// Check that transaction belongs to logged-in user
// -------------------------------------------------------------
$transaction = getTransactionById(
    $conn,
    $transaction_id,
    $logged_in_user_id
);


if (!$transaction) {

    header(
        "Location: index.php?error=" .
        urlencode("Transaction not found.")
    );

    exit;
}


// -------------------------------------------------------------
// Delete transaction
// -------------------------------------------------------------
$success = deleteTransaction(
    $conn,
    $transaction_id,
    $logged_in_user_id
);


// -------------------------------------------------------------
// Redirect back to Money Tracker
// -------------------------------------------------------------
if ($success) {

    header(
        "Location: index.php?success=" .
        urlencode("Transaction deleted successfully.")
    );

    exit;

} else {

    header(
        "Location: index.php?error=" .
        urlencode("Unable to delete transaction.")
    );

    exit;
}
?>