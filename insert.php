<?php
// Include your database connection
include 'database.php'; // Ensure the path is correct

// Initialize variables to retain input values
$product_name = '';
$price = '';
$stock_quantity = '';
$category_id = '';
$order_product_id = '';
$order_quantity = '';

// Check if the form is submitted for category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    // Get the category name from the form
    $category_name = trim($_POST['category_name']);

    // Prepare and execute the insert statement
    try {
        // Prepare an SQL statement
        $stmt = $pdo->prepare("INSERT INTO Categories (category_name) VALUES (:category_name)");
        $stmt->bindParam(':category_name', $category_name);
        $stmt->execute();

        // Get the last inserted category ID
        $lastInsertId = $pdo->lastInsertId();
        
        // Fetch the inserted category details as proof
        $stmt = $pdo->prepare("SELECT * FROM Categories WHERE category_id = :id");
        $stmt->bindParam(':id', $lastInsertId, PDO::PARAM_INT);
        $stmt->execute();
        $insertedCategory = $stmt->fetch(PDO::FETCH_ASSOC);

        // Display the confirmation with the inserted category details
        if ($insertedCategory) {
            $successMessage = "New category inserted successfully! ID: " . htmlspecialchars($insertedCategory['category_id']) . 
                              ", Name: " . htmlspecialchars($insertedCategory['category_name']);
        } else {
            $errorMessage = "Error fetching inserted category details.";
        }
    } catch (PDOException $e) {
        // Handle any errors
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Check if the form is submitted for getting product details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['get_product_details'])) {
    // Get the product_id from the form
    $product_id = trim($_POST['product_id']);

    // Debugging output: Check the product ID being submitted
    // echo "Product ID submitted: $product_id"; // Uncomment to debug

    // Prepare and execute the stored procedure
    try {
        $stmt = $pdo->prepare("CALL GetProductDetails(:product_id)");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT); // Make sure to bind as integer
        $stmt->execute();
        
        // Fetch the result
        $product_details = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Close the cursor after fetching

        // Debugging output: Check fetched product details
        // var_dump($product_details); // Uncomment to debug

    } catch (PDOException $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Check if the form is submitted for product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    // Get product details from the form
    $product_name = trim($_POST['product_name']);
    $price = trim($_POST['price']);
    $stock_quantity = trim($_POST['stock_quantity']);
    $category_id = trim($_POST['category_id']);

    try {
        // Insert the product
        $stmt = $pdo->prepare("INSERT INTO Products (product_name, price, stock_quantity, category_id) VALUES (:product_name, :price, :stock_quantity, :category_id)");
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock_quantity', $stock_quantity);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();

        // Fetch the inserted product details, including category name
        $lastProductId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT p.product_id, p.product_name, p.price, p.stock_quantity, c.category_name 
                               FROM Products p 
                               JOIN Categories c ON p.category_id = c.category_id 
                               WHERE p.product_id = :product_id");
        $stmt->bindParam(':product_id', $lastProductId, PDO::PARAM_INT);
        $stmt->execute();
        $insertedProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        // Display the product details
        if ($insertedProduct) {
            $successMessage = "New product inserted successfully! Details:<br> 
                               ID: " . htmlspecialchars($insertedProduct['product_id']) . "<br> 
                               Name: " . htmlspecialchars($insertedProduct['product_name']) . "<br> 
                               Price: $" . htmlspecialchars($insertedProduct['price']) . "<br> 
                               Stock Quantity: " . htmlspecialchars($insertedProduct['stock_quantity']) . "<br> 
                               Category: " . htmlspecialchars($insertedProduct['category_name']);
        } else {
            $errorMessage = "Error fetching inserted product details.";
        }
    } catch (PDOException $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Check if the form is submitted for order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // Get order details from the form
    $order_product_id = trim($_POST['order_product_id']);
    $order_quantity = trim($_POST['order_quantity']);
    $order_date = date("Y-m-d H:i:s"); // Current date and time

    // Prepare and execute the insert statement for orders
    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Check current stock before placing the order
        $stmt = $pdo->prepare("SELECT stock_quantity FROM Products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $order_product_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Close cursor after fetching

        if ($result && $result['stock_quantity'] >= $order_quantity) {
            // Decrease stock quantity
            $new_stock_quantity = $result['stock_quantity'] - $order_quantity;

            // Update the stock quantity in Products
            $stmt = $pdo->prepare("UPDATE Products SET stock_quantity = :new_stock_quantity WHERE product_id = :product_id");
            $stmt->bindParam(':new_stock_quantity', $new_stock_quantity);
            $stmt->bindParam(':product_id', $order_product_id);
            $stmt->execute();

            // Insert the order
            $stmt = $pdo->prepare("INSERT INTO Orders (product_id, order_quantity, order_date) VALUES (:product_id, :order_quantity, :order_date)");
            $stmt->bindParam(':product_id', $order_product_id);
            $stmt->bindParam(':order_quantity', $order_quantity);
            $stmt->bindParam(':order_date', $order_date);
            $stmt->execute();

            // Commit the transaction
            $pdo->commit();

            // Success message
            $successMessage = "Order placed successfully!";
        } else {
            // Rollback the transaction
            $pdo->rollBack();
            $errorMessage = "Error: Not enough stock available for this product.";
        }
    } catch (PDOException $e) {
        // Handle any errors
        $pdo->rollBack();
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Fetch categories for product insertion
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM Categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); // Close cursor after fetching
} catch (PDOException $e) {
    $errorMessage = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 8px;
        }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .message {
            margin: 10px 0;
            color: green;
        }
        .error {
            margin: 10px 0;
            color: red;
        }
    </style>
</head>
<body>

<h1>E-Commerce Management</h1>

<!-- Display success or error messages -->
<?php if (isset($successMessage)) : ?>
    <div class="message"><?= $successMessage; ?></div>
<?php endif; ?>
<?php if (isset($errorMessage)) : ?>
    <div class="error"><?= $errorMessage; ?></div>
<?php endif; ?>

<!-- Form to add category -->
<form method="POST">
    <h2>Add Category</h2>
    <label for="category_name">Category Name:</label>
    <input type="text" name="category_name" required>
    <button type="submit" name="add_category">Add Category</button>
</form>

<!-- Form to add product -->
<form method="POST">
    <h2>Add Product</h2>
    <label for="product_name">Product Name:</label>
    <input type="text" name="product_name" value="<?= htmlspecialchars($product_name); ?>" required>
    
    <label for="price">Price:</label>
    <input type="number" name="price" value="<?= htmlspecialchars($price); ?>" required>
    
    <label for="stock_quantity">Stock Quantity:</label>
    <input type="number" name="stock_quantity" value="<?= htmlspecialchars($stock_quantity); ?>" required>
    
    <label for="category_id">Category:</label>
    <select name="category_id" required>
        <option value="">Select a category</option>
        <?php foreach ($categories as $category) : ?>
            <option value="<?= htmlspecialchars($category['category_id']); ?>" <?= $category['category_id'] == $category_id ? 'selected' : ''; ?>>
                <?= htmlspecialchars($category['category_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <button type="submit" name="add_product">Add Product</button>
</form>

<!-- Form to place order -->
<form method="POST">
    <h2>Place Order</h2>
    <label for="order_product_id">Product ID:</label>
    <input type="number" name="order_product_id" value="<?= htmlspecialchars($order_product_id); ?>" required>
    
    <label for="order_quantity">Quantity:</label>
    <input type="number" name="order_quantity" value="<?= htmlspecialchars($order_quantity); ?>" required>
    
    <button type="submit" name="place_order">Place Order</button>
</form>

</body>
</html>
