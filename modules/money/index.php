<?php
// ===================================================================
// index.php
// Main Dashboard / Presentation Layer for the Money Tracker module.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "money_model.php";

// -------------------------------------------------------------
// Get the logged-in user's money data
// -------------------------------------------------------------
$summary = getMoneySummary($conn, $logged_in_user_id);
$transactions = getTransactionsForUser($conn, $logged_in_user_id);

// -------------------------------------------------------------
// Messages
// -------------------------------------------------------------
$success = isset($_GET["success"])
    ? $_GET["success"]
    : "";

$error = isset($_GET["error"])
    ? $_GET["error"]
    : "";
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
        Money Tracker - Student Routine Organizer
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
           Navigation Bar
        ========================= */

        .navbar {
            background: white;

            padding: 20px 40px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            box-shadow:
                0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .navbar h2 {
            font-size: 20px;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .navbar a {
            text-decoration: none;
            color: #6d5dfc;
            font-weight: bold;
        }

        .navbar a:hover {
            color: #5848e5;
        }

        /* =========================
           Main Layout
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

        .page-header h1 {
            margin-bottom: 6px;
        }

        .page-header p {
            color: #6b7280;
        }

        /* =========================
           Buttons
        ========================= */

        .btn {
            display: inline-block;

            padding: 11px 18px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: bold;

            border: none;

            cursor: pointer;

            font-family: Arial, sans-serif;
        }

        .btn-primary {
            background: #6d5dfc;
            color: white;
        }

        .btn-primary:hover {
            background: #5848e5;
        }

        .btn-edit {
            background: #e0e7ff;
            color: #4338ca;

            padding: 7px 12px;

            font-size: 13px;
        }

        .btn-edit:hover {
            background: #c7d2fe;
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
           Summary Cards
        ========================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 35px;
        }

        .summary-card {
            background: white;

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .summary-title {
            color: #6b7280;

            font-size: 14px;

            margin-bottom: 10px;
        }

        .summary-amount {
            font-size: 28px;
            font-weight: bold;
        }

        .balance {
            color: #6d5dfc;
        }

        .income {
            color: #16a34a;
        }

        .expense {
            color: #dc2626;
        }

        /* =========================
           Transaction Section
        ========================= */

        .transaction-card {
            background: white;

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .transaction-card h2 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;

            padding: 14px;

            background: #f9fafb;

            color: #6b7280;

            font-size: 13px;
        }

        td {
            padding: 14px;

            border-top:
                1px solid #e5e7eb;
        }

        .income-text {
            color: #16a34a;
            font-weight: bold;
        }

        .expense-text {
            color: #dc2626;
            font-weight: bold;
        }

        .type-badge {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }

        .income-badge {
            background: #dcfce7;
            color: #15803d;
        }

        .expense-badge {
            background: #fee2e2;
            color: #b91c1c;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
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

        .error {
            background: #fee2e2;
            color: #991b1b;

            padding: 14px;

            border-radius: 8px;

            margin-bottom: 25px;
        }

        /* =========================
           Empty State
        ========================= */

        .empty-state {
            text-align: center;

            padding: 50px 20px;

            color: #6b7280;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        /* =========================
           Responsive Layout
        ========================= */

        @media (max-width: 800px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .navbar {
                padding: 20px;
            }

            .navbar-links {
                gap: 14px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .transaction-card {
                overflow-x: auto;
            }
        }

    </style>

</head>

<body>

<!-- =========================
     Navigation Bar
========================= -->

<nav class="navbar">

    <h2>
        💰 Money Tracker
    </h2>

    <div class="navbar-links">

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
         Page Header
    ========================== -->

    <div class="page-header">

        <div>

            <h1>
                💰 Money Tracker
            </h1>

            <p>
                Manage your income, expenses and spending.
            </p>

        </div>

        <a
            href="add.php"
            class="btn btn-primary"
        >
            + Add Transaction
        </a>

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
         Error Message
    ========================== -->

    <?php if ($error !== ""): ?>

        <div class="error">

            <?php
            echo htmlspecialchars(
                $error
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         Summary Cards
    ========================== -->

    <div class="summary-grid">

        <!-- Current Balance -->

        <div class="summary-card">

            <div class="summary-title">
                💳 Current Balance
            </div>

            <div class="summary-amount balance">

                RM <?php
                echo number_format(
                    $summary["balance"],
                    2
                );
                ?>

            </div>

        </div>


        <!-- Total Income -->

        <div class="summary-card">

            <div class="summary-title">
                📈 Total Income
            </div>

            <div class="summary-amount income">

                RM <?php
                echo number_format(
                    $summary["total_income"],
                    2
                );
                ?>

            </div>

        </div>


        <!-- Total Expenses -->

        <div class="summary-card">

            <div class="summary-title">
                📉 Total Expenses
            </div>

            <div class="summary-amount expense">

                RM <?php
                echo number_format(
                    $summary["total_expense"],
                    2
                );
                ?>

            </div>

        </div>

    </div>


    <!-- =========================
         Transaction History
    ========================== -->

    <div class="transaction-card">

        <h2>
            Recent Transactions
        </h2>


        <?php if (empty($transactions)): ?>

            <!-- Empty State -->

            <div class="empty-state">

                <div class="empty-icon">
                    💸
                </div>

                <h3>
                    No transactions yet
                </h3>

                <p
                    style="
                        margin-top:8px;
                        margin-bottom:20px;
                    "
                >
                    Add your first income or expense record.
                </p>

                <a
                    href="add.php"
                    class="btn btn-primary"
                >
                    + Add Transaction
                </a>

            </div>


        <?php else: ?>

            <!-- Transaction Table -->

            <table>

                <thead>

                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                <?php foreach ($transactions as $transaction): ?>

                    <tr>

                        <!-- Date -->

                        <td>

                            <?php
                            echo date(
                                "d M Y",
                                strtotime(
                                    $transaction[
                                        "transaction_date"
                                    ]
                                )
                            );
                            ?>

                        </td>


                        <!-- Transaction Type -->

                        <td>

                            <?php if (
                                $transaction["transaction_type"]
                                === "Income"
                            ): ?>

                                <span
                                    class="
                                        type-badge
                                        income-badge
                                    "
                                >
                                    Income
                                </span>

                            <?php else: ?>

                                <span
                                    class="
                                        type-badge
                                        expense-badge
                                    "
                                >
                                    Expense
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- Category -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $transaction["category"]
                            );
                            ?>

                        </td>


                        <!-- Description -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $transaction["description"]
                            );
                            ?>

                        </td>


                        <!-- Amount -->

                        <td>

                            <?php if (
                                $transaction["transaction_type"]
                                === "Income"
                            ): ?>

                                <span class="income-text">

                                    + RM <?php
                                    echo number_format(
                                        $transaction["amount"],
                                        2
                                    );
                                    ?>

                                </span>

                            <?php else: ?>

                                <span class="expense-text">

                                    - RM <?php
                                    echo number_format(
                                        $transaction["amount"],
                                        2
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- Actions -->

                        <td>

                            <div class="actions">

                                <!-- Edit -->

                                <a
                                    href="edit.php?id=<?php
                                        echo
                                        $transaction[
                                            "transaction_id"
                                        ];
                                    ?>"
                                    class="btn btn-edit"
                                >
                                    Edit
                                </a>


                                <!-- Delete -->

                                <form
                                    action="delete_handler.php"
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="
                                        return confirm(
                                            'Are you sure you want to delete this transaction?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="transaction_id"
                                        value="<?php
                                            echo
                                            $transaction[
                                                "transaction_id"
                                            ];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-delete"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

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