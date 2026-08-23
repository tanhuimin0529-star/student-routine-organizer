<?php
// ===================================================================
// budget.php
// Monthly Budget Management page for the Money Tracker module.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "money_model.php";

$errors = array();
$success = "";
$error_message = "";

// -------------------------------------------------------------
// Selected month
// -------------------------------------------------------------
$selected_month = isset($_GET["month"])
    ? $_GET["month"]
    : date("Y-m");

// Convert YYYY-MM into YYYY-MM-01 for database storage
$budget_month = $selected_month . "-01";


// -------------------------------------------------------------
// Handle Add / Update Budget
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category = isset($_POST["category"])
        ? trim($_POST["category"])
        : "";

    $monthly_limit = isset($_POST["monthly_limit"])
        ? trim($_POST["monthly_limit"])
        : "";

    $posted_month = isset($_POST["budget_month"])
        ? trim($_POST["budget_month"])
        : date("Y-m");

    $budget_month_for_db = $posted_month . "-01";

    // Only expense categories can be used for budgets
    if (!in_array($category, $expense_categories)) {
        $errors[] = "Please select a valid expense category.";
    }

    $validation_errors = validateBudgetInput(
        $category,
        $monthly_limit,
        $budget_month_for_db
    );

    $errors = array_merge(
        $errors,
        $validation_errors
    );

    if (empty($errors)) {

        $saved = saveBudget(
            $conn,
            $logged_in_user_id,
            $category,
            (float)$monthly_limit,
            $budget_month_for_db
        );

        if ($saved) {

            header(
                "Location: budget.php?month=" .
                urlencode($posted_month) .
                "&success=" .
                urlencode("Budget saved successfully.")
            );

            exit;

        } else {

            $errors[] =
                "Unable to save the budget. Please try again.";
        }
    }
}


// -------------------------------------------------------------
// Messages from redirects
// -------------------------------------------------------------
if (isset($_GET["success"])) {
    $success = $_GET["success"];
}

if (isset($_GET["error"])) {
    $error_message = $_GET["error"];
}


// -------------------------------------------------------------
// Load budgets for selected month
// -------------------------------------------------------------
$budgets = getBudgetsForUser(
    $conn,
    $logged_in_user_id,
    $budget_month
);


// -------------------------------------------------------------
// Calculate budget statistics
// -------------------------------------------------------------
$total_budget = 0;
$total_spent = 0;

foreach ($budgets as &$budget) {

    $spent = getMonthlyExpenseByCategory(
        $conn,
        $logged_in_user_id,
        $budget["category"],
        $budget["budget_month"]
    );

    $budget["spent"] = $spent;

    $budget["remaining"] =
        (float)$budget["monthly_limit"] - $spent;

    if ((float)$budget["monthly_limit"] > 0) {

        $budget["percentage"] = round(
            (
                $spent /
                (float)$budget["monthly_limit"]
            ) * 100
        );

    } else {

        $budget["percentage"] = 0;
    }

    $total_budget +=
        (float)$budget["monthly_limit"];

    $total_spent += $spent;
}

unset($budget);

$total_remaining =
    $total_budget - $total_spent;
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Monthly Budget - Student Routine Organizer
    </title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        /* =========================
           Navbar
        ========================= */

        .navbar {
            background: white;
            padding: 20px 40px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.05);
        }

        .navbar h2 {
            font-size: 20px;
        }

        .navbar a {
            text-decoration: none;
            color: #6d5dfc;
            font-weight: bold;
            margin-left: 20px;
        }

        /* =========================
           Main Container
        ========================= */

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 30px;

            gap: 20px;
        }

        .page-header p {
            margin-top: 6px;
            color: #6b7280;
        }

        /* =========================
           Messages
        ========================= */

        .success {
            background: #dcfce7;
            color: #166534;

            padding: 14px;

            border-radius: 8px;

            margin-bottom: 25px;
        }

        .error-box {
            background: #fee2e2;
            color: #991b1b;

            padding: 14px 18px;

            border-radius: 8px;

            margin-bottom: 25px;
        }

        .error-box ul {
            margin-left: 20px;
        }

        /* =========================
           Cards
        ========================= */

        .card {
            background: white;

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 6px 20px rgba(0,0,0,0.06);

            margin-bottom: 30px;
        }

        .card h2 {
            margin-bottom: 20px;
        }

        /* =========================
           Month Selector
        ========================= */

        .month-form {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        input,
        select {
            padding: 11px 12px;

            border:
                1px solid #d1d5db;

            border-radius: 8px;

            font-size: 14px;
        }

        /* =========================
           Buttons
        ========================= */

        .btn {
            display: inline-block;

            padding: 11px 18px;

            border-radius: 8px;

            border: none;

            cursor: pointer;

            font-weight: bold;

            text-decoration: none;

            font-family: Arial, sans-serif;
        }

        .btn-primary {
            background: #6d5dfc;
            color: white;
        }

        .btn-primary:hover {
            background: #5848e5;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;

            padding: 7px 12px;

            font-size: 13px;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        /* =========================
           Summary
        ========================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 30px;
        }

        .summary-card {
            background: white;

            padding: 25px;

            border-radius: 16px;

            box-shadow:
                0 6px 20px rgba(0,0,0,0.06);
        }

        .summary-title {
            color: #6b7280;

            font-size: 14px;

            margin-bottom: 10px;
        }

        .summary-value {
            font-size: 27px;
            font-weight: bold;
        }

        .budget-color {
            color: #6d5dfc;
        }

        .spent-color {
            color: #dc2626;
        }

        .remaining-color {
            color: #16a34a;
        }

        /* =========================
           Budget Form
        ========================= */

        .budget-form {
            display: grid;

            grid-template-columns:
                1fr 1fr 1fr auto;

            gap: 15px;

            align-items: end;
        }

        .form-group label {
            display: block;

            font-size: 13px;

            font-weight: bold;

            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
        }

        /* =========================
           Budget Table
        ========================= */

        table {
            width: 100%;

            border-collapse: collapse;
        }

        th {
            text-align: left;

            background: #f9fafb;

            padding: 14px;

            font-size: 13px;

            color: #6b7280;
        }

        td {
            padding: 14px;

            border-top:
                1px solid #e5e7eb;
        }

        .progress-container {
            width: 150px;
            height: 10px;

            background: #e5e7eb;

            border-radius: 10px;

            overflow: hidden;

            margin-bottom: 5px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
        }

        .progress-good {
            background: #22c55e;
        }

        .progress-warning {
            background: #f59e0b;
        }

        .progress-danger {
            background: #ef4444;
        }

        .over-budget {
            color: #dc2626;
            font-weight: bold;
        }

        .under-budget {
            color: #16a34a;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;

            padding: 45px 20px;

            color: #6b7280;
        }

        .empty-state .icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        /* =========================
           Responsive
        ========================= */

        @media (max-width: 850px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .budget-form {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .card {
                overflow-x: auto;
            }

            .navbar {
                padding: 20px;
            }
        }

    </style>

</head>

<body>

<!-- =========================
     Navigation
========================= -->

<nav class="navbar">

    <h2>
        💰 Money Tracker
    </h2>

    <div>

        <a href="../../dashboard/dashboard.php">
            Home
        </a>

        <a href="index.php">
            Transactions
        </a>

        <a href="budget.php">
            Budget
        </a>

        <a href="../../authentication/logout.php">
            Logout
        </a>

    </div>

</nav>


<div class="container">

    <!-- =========================
         Header
    ========================== -->

    <div class="page-header">

        <div>

            <h1>
                📊 Monthly Budget
            </h1>

            <p>
                Set spending limits and track your monthly expenses.
            </p>

        </div>


        <form
            method="GET"
            action="budget.php"
            class="month-form"
        >

            <input
                type="month"
                name="month"
                value="<?php
                    echo htmlspecialchars(
                        $selected_month
                    );
                ?>"
                required
            >

            <button
                type="submit"
                class="btn btn-primary"
            >
                View Month
            </button>

        </form>

    </div>


    <!-- =========================
         Success Message
    ========================== -->

    <?php if ($success !== ""): ?>

        <div class="success">

            <?php
            echo htmlspecialchars(
                $success
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         Redirect Error Message
    ========================== -->

    <?php if ($error_message !== ""): ?>

        <div class="error-box">

            <?php
            echo htmlspecialchars(
                $error_message
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         Validation Errors
    ========================== -->

    <?php if (!empty($errors)): ?>

        <div class="error-box">

            <strong>
                Please correct the following:
            </strong>

            <ul>

                <?php foreach ($errors as $error): ?>

                    <li>

                        <?php
                        echo htmlspecialchars(
                            $error
                        );
                        ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- =========================
         Summary Cards
    ========================== -->

    <div class="summary-grid">

        <!-- Total Budget -->

        <div class="summary-card">

            <div class="summary-title">
                🎯 Total Monthly Budget
            </div>

            <div class="summary-value budget-color">

                RM <?php
                echo number_format(
                    $total_budget,
                    2
                );
                ?>

            </div>

        </div>


        <!-- Total Spent -->

        <div class="summary-card">

            <div class="summary-title">
                💸 Total Spent
            </div>

            <div class="summary-value spent-color">

                RM <?php
                echo number_format(
                    $total_spent,
                    2
                );
                ?>

            </div>

        </div>


        <!-- Remaining -->

        <div class="summary-card">

            <div class="
                summary-value
                <?php
                    echo
                        $total_remaining >= 0
                        ? "remaining-color"
                        : "spent-color";
                ?>
            ">

                <div
                    class="summary-title"
                    style="color:#6b7280;"
                >
                    💰 Remaining
                </div>

                RM <?php
                echo number_format(
                    $total_remaining,
                    2
                );
                ?>

            </div>

        </div>

    </div>


    <!-- =========================
         Set Budget Form
    ========================== -->

    <div class="card">

        <h2>
            Set Budget
        </h2>


        <form
            method="POST"
            action="budget.php?month=<?php
                echo urlencode(
                    $selected_month
                );
            ?>"
            class="budget-form"
        >

            <!-- Category -->

            <div class="form-group">

                <label>
                    Expense Category
                </label>

                <select
                    name="category"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    <?php
                    foreach (
                        $expense_categories
                        as $category
                    ):
                    ?>

                        <option
                            value="<?php
                                echo htmlspecialchars(
                                    $category
                                );
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $category
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Budget Amount -->

            <div class="form-group">

                <label>
                    Monthly Limit (RM)
                </label>

                <input
                    type="number"
                    name="monthly_limit"

                    min="0.01"
                    step="0.01"

                    placeholder="e.g. 500.00"

                    required
                >

            </div>


            <!-- Budget Month -->

            <div class="form-group">

                <label>
                    Budget Month
                </label>

                <input
                    type="month"
                    name="budget_month"

                    value="<?php
                        echo htmlspecialchars(
                            $selected_month
                        );
                    ?>"

                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                Save Budget
            </button>

        </form>

    </div>


    <!-- =========================
         Budget Overview
    ========================== -->

    <div class="card">

        <h2>

            Budget Overview —

            <?php
            echo date(
                "F Y",
                strtotime(
                    $budget_month
                )
            );
            ?>

        </h2>


        <?php if (empty($budgets)): ?>

            <!-- Empty State -->

            <div class="empty-state">

                <div class="icon">
                    📊
                </div>

                <h3>
                    No budgets set for this month
                </h3>

                <p style="margin-top:8px;">
                    Set your first category budget above.
                </p>

            </div>


        <?php else: ?>

            <!-- Budget Table -->

            <table>

                <thead>

                    <tr>
                        <th>Category</th>
                        <th>Budget</th>
                        <th>Spent</th>
                        <th>Remaining</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>

                </thead>


                <tbody>

                <?php foreach ($budgets as $budget): ?>

                    <?php

                    $percentage =
                        $budget["percentage"];

                    $display_percentage =
                        min(
                            100,
                            max(
                                0,
                                $percentage
                            )
                        );


                    if ($percentage >= 100) {

                        $progress_class =
                            "progress-danger";

                    } elseif ($percentage >= 75) {

                        $progress_class =
                            "progress-warning";

                    } else {

                        $progress_class =
                            "progress-good";
                    }

                    ?>


                    <tr>

                        <!-- Category -->

                        <td>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $budget["category"]
                                );
                                ?>

                            </strong>

                        </td>


                        <!-- Budget -->

                        <td>

                            RM <?php
                            echo number_format(
                                $budget["monthly_limit"],
                                2
                            );
                            ?>

                        </td>


                        <!-- Spent -->

                        <td>

                            RM <?php
                            echo number_format(
                                $budget["spent"],
                                2
                            );
                            ?>

                        </td>


                        <!-- Remaining -->

                        <td>

                            <span
                                class="<?php
                                    echo
                                        $budget["remaining"] >= 0
                                        ? "under-budget"
                                        : "over-budget";
                                ?>"
                            >

                                RM <?php
                                echo number_format(
                                    $budget["remaining"],
                                    2
                                );
                                ?>

                            </span>

                        </td>


                        <!-- Progress -->

                        <td>

                            <div class="progress-container">

                                <div
                                    class="
                                        progress-bar
                                        <?php
                                            echo $progress_class;
                                        ?>
                                    "
                                    style="
                                        width:
                                        <?php
                                            echo $display_percentage;
                                        ?>%;
                                    "
                                ></div>

                            </div>

                            <small>

                                <?php
                                echo $percentage;
                                ?>% used

                            </small>

                        </td>


                        <!-- Delete -->

                        <td>

                            <a
                                href="delete_budget_handler.php?id=<?php
                                    echo $budget["budget_id"];
                                ?>&month=<?php
                                    echo urlencode(
                                        $selected_month
                                    );
                                ?>"

                                class="btn btn-delete"

                                onclick="
                                    return confirm(
                                        'Are you sure you want to delete this budget?'
                                    );
                                "
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>

</body>

</html>