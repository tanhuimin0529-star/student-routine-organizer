<?php
// ===================================================================
// edit.php
// Presentation Layer — Edit Money Transaction
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "money_model.php";


// -------------------------------------------------------------
// Get transaction ID
// -------------------------------------------------------------
$transaction_id =
    isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


// -------------------------------------------------------------
// Get transaction belonging to logged-in user
// -------------------------------------------------------------
$transaction = getTransactionById(
    $conn,
    $transaction_id,
    $logged_in_user_id
);


// -------------------------------------------------------------
// Transaction not found / belongs to another user
// -------------------------------------------------------------
if (!$transaction) {

    header(
        "Location: index.php?success=" .
        urlencode("Transaction not found.")
    );

    exit;
}


// Error message returned by handler
$error = isset($_GET["error"]) ? $_GET["error"] : "";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Transaction - Money Tracker</title>

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

        .navbar {
            background: white;
            padding: 20px 40px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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

        .container {
            max-width: 650px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 35px;
            border-radius: 18px;

            box-shadow:
                0 10px 30px rgba(0,0,0,0.08);
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input,
        select,
        textarea {

            width: 100%;

            padding: 12px;

            border:
                1px solid #d1d5db;

            border-radius: 8px;

            font-size: 15px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 8px;

            background: #6d5dfc;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;
        }

        .btn:hover {
            background: #5848e5;
        }

        .error {

            background: #fee2e2;

            color: #991b1b;

            padding: 12px;

            border-radius: 8px;

            margin-bottom: 20px;
        }

        .back-link {

            display: inline-block;

            margin-top: 20px;

            text-decoration: none;

            color: #6d5dfc;
        }

    </style>

</head>


<body>


<nav class="navbar">

    <h2>💰 Money Tracker</h2>

    <div>

        <a href="../../dashboard/dashboard.php">
            Home
        </a>

        <a href="index.php">
            Money Tracker
        </a>

        <a href="../../authentication/logout.php">
            Logout
        </a>

    </div>

</nav>


<div class="container">


    <div class="card">


        <h1>✏️ Edit Transaction</h1>

        <p class="subtitle">
            Update your transaction information.
        </p>


        <?php if ($error != ""): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            action="edit_handler.php"
            method="POST"
        >


            <!-- Transaction ID -->

            <input
                type="hidden"
                name="transaction_id"
                value="<?php
                    echo $transaction["transaction_id"];
                ?>"
            >


            <!-- Transaction Type -->

            <div class="form-group">

                <label for="transaction_type">
                    Transaction Type
                </label>


                <select
                    name="transaction_type"
                    id="transaction_type"
                    required
                >

                    <option value="">
                        -- Select Type --
                    </option>


                    <option
                        value="Income"
                        <?php
                        if (
                            $transaction["transaction_type"]
                            === "Income"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        Income
                    </option>


                    <option
                        value="Expense"
                        <?php
                        if (
                            $transaction["transaction_type"]
                            === "Expense"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        Expense
                    </option>

                </select>

            </div>


            <!-- Category -->

            <div class="form-group">

                <label for="category">
                    Category
                </label>


                <select
                    name="category"
                    id="category"
                    required
                >

                    <option value="">
                        -- Select Category --
                    </option>


                    <optgroup label="Income">

                        <?php
                        foreach (
                            $income_categories
                            as $category
                        ):
                        ?>

                            <option
                                value="<?php
                                    echo htmlspecialchars(
                                        $category
                                    );
                                ?>"
                                <?php
                                if (
                                    $transaction["category"]
                                    === $category
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $category
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </optgroup>


                    <optgroup label="Expense">

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
                                <?php
                                if (
                                    $transaction["category"]
                                    === $category
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $category
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </optgroup>

                </select>

            </div>


            <!-- Amount -->

            <div class="form-group">

                <label for="amount">
                    Amount (RM)
                </label>


                <input
                    type="number"
                    name="amount"
                    id="amount"

                    step="0.01"
                    min="0.01"

                    value="<?php
                        echo htmlspecialchars(
                            $transaction["amount"]
                        );
                    ?>"

                    required
                >

            </div>


            <!-- Transaction Date -->

            <div class="form-group">

                <label for="transaction_date">
                    Transaction Date
                </label>


                <input
                    type="date"
                    name="transaction_date"
                    id="transaction_date"

                    value="<?php
                        echo htmlspecialchars(
                            $transaction["transaction_date"]
                        );
                    ?>"

                    required
                >

            </div>


            <!-- Description -->

            <div class="form-group">

                <label for="description">
                    Description
                </label>


                <textarea
                    name="description"
                    id="description"
                    maxlength="255"
                ><?php
                    echo htmlspecialchars(
                        $transaction["description"]
                    );
                ?></textarea>

            </div>


            <button
                type="submit"
                class="btn"
            >
                Save Changes
            </button>


        </form>


        <a
            href="index.php"
            class="back-link"
        >
            ← Cancel and return to Money Tracker
        </a>


    </div>


</div>


</body>

</html>