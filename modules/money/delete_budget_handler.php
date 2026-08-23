<?php
// ===================================================================
// delete_budget_handler.php
// Handles deletion of a monthly budget.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "money_model.php";

// -------------------------------------------------------------
// Check whether a budget ID was provided
// -------------------------------------------------------------
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header(
        "Location: budget.php?error=" .
        urlencode("Invalid budget ID.")
    );

    exit;
}

$budget_id = (int)$_GET["id"];


// -------------------------------------------------------------
// Make sure the budget exists and belongs to the logged-in user
// -------------------------------------------------------------
$budget = getBudgetById(
    $conn,
    $budget_id,
    $logged_in_user_id
);

if (!$budget) {

    header(
        "Location: budget.php?error=" .
        urlencode("Budget not found.")
    );

    exit;
}


// -------------------------------------------------------------
// Remember the month before deleting
// -------------------------------------------------------------
$budget_month = $budget["budget_month"];


// -------------------------------------------------------------
// Delete the budget
// -------------------------------------------------------------
$success = deleteBudget(
    $conn,
    $budget_id,
    $logged_in_user_id
);


// -------------------------------------------------------------
// Redirect back to Budget page
// -------------------------------------------------------------
if ($success) {

    header(
        "Location: budget.php?month=" .
        urlencode(date("Y-m", strtotime($budget_month))) .
        "&success=" .
        urlencode("Budget deleted successfully.")
    );

} else {

    header(
        "Location: budget.php?month=" .
        urlencode(date("Y-m", strtotime($budget_month))) .
        "&error=" .
        urlencode("Unable to delete the budget.")
    );
}

exit;
?>