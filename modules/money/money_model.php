<?php
// ===================================================================
// money_model.php
// Business Logic / Database Layer for the Money Tracker module.
// ===================================================================


// =============================================================
// MONEY TRACKER CATEGORIES
// =============================================================

$income_categories = array(
    "Allowance",
    "Salary",
    "Scholarship",
    "Part-Time Job",
    "Gift",
    "Other"
);

$expense_categories = array(
    "Food",
    "Transport",
    "Education",
    "Entertainment",
    "Shopping",
    "Bills",
    "Health",
    "Other"
);


// =============================================================
// TRANSACTION FUNCTIONS
// =============================================================


// -------------------------------------------------------------
// validateTransactionInput()
// -------------------------------------------------------------
function validateTransactionInput(
    $transaction_type,
    $category,
    $amount,
    $transaction_date
) {
    $errors = array();

    if (
        $transaction_type !== "Income" &&
        $transaction_type !== "Expense"
    ) {
        $errors[] = "Please select a valid transaction type.";
    }

    if (trim($category) === "") {
        $errors[] = "Please select a category.";
    }

    if (
        $amount === "" ||
        !is_numeric($amount) ||
        $amount <= 0
    ) {
        $errors[] = "Amount must be greater than zero.";
    }

    $date_parts = explode("-", $transaction_date);

    if (
        count($date_parts) != 3 ||
        !checkdate(
            (int)$date_parts[1],
            (int)$date_parts[2],
            (int)$date_parts[0]
        )
    ) {
        $errors[] = "Please enter a valid transaction date.";
    }

    return $errors;
}


// -------------------------------------------------------------
// addTransaction()
// -------------------------------------------------------------
function addTransaction(
    $conn,
    $user_id,
    $transaction_type,
    $category,
    $amount,
    $transaction_date,
    $description
) {
    $sql = "INSERT INTO money_transactions
            (
                user_id,
                transaction_type,
                category,
                amount,
                transaction_date,
                description
            )
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "issdss",
        $user_id,
        $transaction_type,
        $category,
        $amount,
        $transaction_date,
        $description
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// getTransactionsForUser()
// -------------------------------------------------------------
function getTransactionsForUser($conn, $user_id) {

    $sql = "SELECT *
            FROM money_transactions
            WHERE user_id = ?
            ORDER BY transaction_date DESC,
                     transaction_id DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return array();
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $transactions = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $transactions;
}


// -------------------------------------------------------------
// getTransactionById()
// -------------------------------------------------------------
function getTransactionById(
    $conn,
    $transaction_id,
    $user_id
) {
    $sql = "SELECT *
            FROM money_transactions
            WHERE transaction_id = ?
            AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $transaction_id,
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $transaction = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $transaction;
}


// -------------------------------------------------------------
// updateTransaction()
// -------------------------------------------------------------
function updateTransaction(
    $conn,
    $transaction_id,
    $user_id,
    $transaction_type,
    $category,
    $amount,
    $transaction_date,
    $description
) {
    $sql = "UPDATE money_transactions
            SET transaction_type = ?,
                category = ?,
                amount = ?,
                transaction_date = ?,
                description = ?
            WHERE transaction_id = ?
            AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssdssii",
        $transaction_type,
        $category,
        $amount,
        $transaction_date,
        $description,
        $transaction_id,
        $user_id
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// deleteTransaction()
// -------------------------------------------------------------
function deleteTransaction(
    $conn,
    $transaction_id,
    $user_id
) {
    $sql = "DELETE FROM money_transactions
            WHERE transaction_id = ?
            AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $transaction_id,
        $user_id
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// getMoneySummary()
// Calculate total income, expenses and balance.
// -------------------------------------------------------------
function getMoneySummary($conn, $user_id) {

    $summary = array(
        "total_income" => 0,
        "total_expense" => 0,
        "balance" => 0
    );

    $sql = "SELECT

                COALESCE(
                    SUM(
                        CASE
                            WHEN transaction_type = 'Income'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_income,

                COALESCE(
                    SUM(
                        CASE
                            WHEN transaction_type = 'Expense'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_expense

            FROM money_transactions

            WHERE user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return $summary;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($row) {

        $summary["total_income"] =
            (float)$row["total_income"];

        $summary["total_expense"] =
            (float)$row["total_expense"];

        $summary["balance"] =
            $summary["total_income"] -
            $summary["total_expense"];
    }

    return $summary;
}


// =============================================================
// BUDGET FUNCTIONS
// =============================================================


// -------------------------------------------------------------
// validateBudgetInput()
// Validate monthly budget data.
// -------------------------------------------------------------
function validateBudgetInput(
    $category,
    $monthly_limit,
    $budget_month
) {
    $errors = array();

    if (trim($category) === "") {
        $errors[] = "Please select a budget category.";
    }

    if (
        $monthly_limit === "" ||
        !is_numeric($monthly_limit) ||
        $monthly_limit <= 0
    ) {
        $errors[] = "Budget amount must be greater than zero.";
    }

    // budget_month should be YYYY-MM-01
    $date_parts = explode("-", $budget_month);

    if (
        count($date_parts) != 3 ||
        !checkdate(
            (int)$date_parts[1],
            (int)$date_parts[2],
            (int)$date_parts[0]
        )
    ) {
        $errors[] = "Please select a valid budget month.";
    }

    return $errors;
}


// -------------------------------------------------------------
// saveBudget()
// Add a new budget or update an existing budget for the same
// user/category/month.
// -------------------------------------------------------------
function saveBudget(
    $conn,
    $user_id,
    $category,
    $monthly_limit,
    $budget_month
) {
    $sql = "INSERT INTO money_budgets
            (
                user_id,
                category,
                monthly_limit,
                budget_month
            )
            VALUES (?, ?, ?, ?)

            ON DUPLICATE KEY UPDATE
                monthly_limit = VALUES(monthly_limit),
                updated_at = CURRENT_TIMESTAMP";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isds",
        $user_id,
        $category,
        $monthly_limit,
        $budget_month
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// getBudgetsForUser()
// Retrieve budgets belonging to one user.
// Optional month filter.
// -------------------------------------------------------------
function getBudgetsForUser(
    $conn,
    $user_id,
    $budget_month = ""
) {
    $budgets = array();

    if ($budget_month !== "") {

        $sql = "SELECT *
                FROM money_budgets
                WHERE user_id = ?
                AND budget_month = ?
                ORDER BY category ASC";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return array();
        }

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $user_id,
            $budget_month
        );

    } else {

        $sql = "SELECT *
                FROM money_budgets
                WHERE user_id = ?
                ORDER BY budget_month DESC,
                         category ASC";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return array();
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $user_id
        );
    }

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $budgets[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $budgets;
}


// -------------------------------------------------------------
// getBudgetById()
// Retrieve one budget belonging to the logged-in user.
// -------------------------------------------------------------
function getBudgetById(
    $conn,
    $budget_id,
    $user_id
) {
    $sql = "SELECT *
            FROM money_budgets
            WHERE budget_id = ?
            AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $budget_id,
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $budget = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $budget;
}


// -------------------------------------------------------------
// deleteBudget()
// Delete only the logged-in user's budget.
// -------------------------------------------------------------
function deleteBudget(
    $conn,
    $budget_id,
    $user_id
) {
    $sql = "DELETE FROM money_budgets
            WHERE budget_id = ?
            AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $budget_id,
        $user_id
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// getMonthlyExpenseByCategory()
// Calculate how much the user spent in one category
// during the selected month.
// -------------------------------------------------------------
function getMonthlyExpenseByCategory(
    $conn,
    $user_id,
    $category,
    $budget_month
) {
    // First day of selected month
    $month_start = date(
        "Y-m-01",
        strtotime($budget_month)
    );

    // Last day of selected month
    $month_end = date(
        "Y-m-t",
        strtotime($budget_month)
    );

    $sql = "SELECT
                COALESCE(SUM(amount), 0) AS total_spent

            FROM money_transactions

            WHERE user_id = ?
            AND transaction_type = 'Expense'
            AND category = ?
            AND transaction_date BETWEEN ? AND ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isss",
        $user_id,
        $category,
        $month_start,
        $month_end
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($row) {
        return (float)$row["total_spent"];
    }

    return 0;
}


// -------------------------------------------------------------
// getTotalMonthlyBudget()
// Total budget allocated for selected month.
// -------------------------------------------------------------
function getTotalMonthlyBudget(
    $conn,
    $user_id,
    $budget_month
) {
    $sql = "SELECT
                COALESCE(SUM(monthly_limit), 0) AS total_budget

            FROM money_budgets

            WHERE user_id = ?
            AND budget_month = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $user_id,
        $budget_month
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($row) {
        return (float)$row["total_budget"];
    }

    return 0;
}


// -------------------------------------------------------------
// getTotalMonthlyExpenses()
// Total expenses for selected month.
// -------------------------------------------------------------
function getTotalMonthlyExpenses(
    $conn,
    $user_id,
    $budget_month
) {
    $month_start = date(
        "Y-m-01",
        strtotime($budget_month)
    );

    $month_end = date(
        "Y-m-t",
        strtotime($budget_month)
    );

    $sql = "SELECT
                COALESCE(SUM(amount), 0) AS total_expense

            FROM money_transactions

            WHERE user_id = ?
            AND transaction_type = 'Expense'
            AND transaction_date BETWEEN ? AND ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $user_id,
        $month_start,
        $month_end
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($row) {
        return (float)$row["total_expense"];
    }

    return 0;
}

?>