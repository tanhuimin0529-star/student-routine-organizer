<?php
// ===================================================================
// edit_handler.php
// Processes updates made to an existing money transaction.
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
// Get submitted data
// -------------------------------------------------------------
$transaction_id =
    isset($_POST["transaction_id"])
    ? (int)$_POST["transaction_id"]
    : 0;

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
// Check transaction ownership
// -------------------------------------------------------------
$transaction = getTransactionById(
    $conn,
    $transaction_id,
    $logged_in_user_id
);


if (!$transaction) {

    header(
        "Location: index.php?success=" .
        urlencode("Transaction not found.")
    );

    exit;
}


// -------------------------------------------------------------
// Validate submitted data
// -------------------------------------------------------------
$errors = validateTransactionInput(
    $transaction_type,
    $category,
    $amount,
    $transaction_date
);


if (!empty($errors)) {

    $message = implode(
        " ",
        $errors
    );


    header(
        "Location: edit.php?id=" .
        $transaction_id .
        "&error=" .
        urlencode($message)
    );

    exit;
}


// -------------------------------------------------------------
// Update transaction
// -------------------------------------------------------------
$success = updateTransaction(
    $conn,
    $transaction_id,
    $logged_in_user_id,
    $transaction_type,
    $category,
    (float)$amount,
    $transaction_date,
    $description
);


// -------------------------------------------------------------
// Redirect
// -------------------------------------------------------------
if ($success) {

    header(
        "Location: index.php?success=" .
        urlencode(
            "Transaction updated successfully."
        )
    );

    exit;

} else {

    header(
        "Location: edit.php?id=" .
        $transaction_id .
        "&error=" .
        urlencode(
            "Unable to update transaction. Please try again."
        )
    );

    exit;
}
?>