<?php
// ===================================================================
// add.php
// Presentation Layer — Add Money Transaction
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../includes/shared_navbar.php";
require_once "../../config/database.php";
require_once "money_model.php";

// Show message returned from handler
$error = isset($_GET['error']) ? $_GET['error'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Transaction - Money Tracker</title>

    <script src="../../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../../assets/css/theme.css">
    <?php renderSharedNavbarAssets('../../'); ?>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f6f0e2;
            color: #47382d;
        }

        .navbar {
            background: #fffdf7;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(112,83,42,0.07);
        }

        .navbar h2 {
            font-size: 20px;
        }

        .navbar a {
            text-decoration: none;
            color: #d9a441;
            font-weight: bold;
            margin-left: 20px;
        }

        .container {
            max-width: 650px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .card {
            background: #fffdf7;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(112,83,42,0.10);
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: #78695d;
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
            border: 1px solid #dec38c;
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
            background: #d9a441;
            color: #fffdf7;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn:hover {
            background: #a9761f;
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
            color: #d9a441;
        }
    </style>
</head>

<body>


<?php renderIntegratedModuleHeader('../../', 'money'); ?>

<div class="container">

    <div class="card">

        <h1>➕ Add Transaction</h1>

        <p class="subtitle">
            Record your income or expense.
        </p>

        <?php if ($error != ""): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="add_handler.php" method="POST">

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
                    <option value="">-- Select Type --</option>
                    <option value="Income">Income</option>
                    <option value="Expense">Expense</option>
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
                    <option value="">-- Select Category --</option>

                    <optgroup label="Income">
                        <?php foreach ($income_categories as $category): ?>

                            <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </option>

                        <?php endforeach; ?>
                    </optgroup>

                    <optgroup label="Expense">
                        <?php foreach ($expense_categories as $category): ?>

                            <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
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
                    placeholder="Example: 15.50"
                    required
                >

            </div>


            <!-- Date -->
            <div class="form-group">

                <label for="transaction_date">
                    Transaction Date
                </label>

                <input
                    type="date"
                    name="transaction_date"
                    id="transaction_date"
                    value="<?php echo date('Y-m-d'); ?>"
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
                    placeholder="Example: Lunch at campus cafeteria"
                ></textarea>

            </div>


            <button type="submit" class="btn">
                Save Transaction
            </button>

        </form>

        <a href="index.php" class="back-link">
            ← Back to Money Tracker
        </a>

    </div>

</div>

</body>
</html>