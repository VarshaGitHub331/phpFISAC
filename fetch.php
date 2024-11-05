<?php
// Include your database connection
include 'database.php'; // Ensure the path is correct

// Check if the form is submitted for getting product details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['get_product_details'])) {
    // Get the product_id from the form
    $product_id = trim($_POST['product_id']);

    // Prepare and execute the stored procedure
    try {
        $stmt = $pdo->prepare("CALL GetProductDetails(:product_id)");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        // Fetch the result
        $product_details = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Close the cursor after fetching

        // Check if product details were found
        if ($product_details) {
            // Display product details (example)
            echo "Product Name: " . htmlspecialchars($product_details['product_name']) . "<br>";
            echo "Price: $" . htmlspecialchars($product_details['price']) . "<br>";
            echo "Stock Quantity: " . htmlspecialchars($product_details['stock_quantity']) . "<br>";
            // Add more fields as needed
        } else {
            echo "No product found with the given ID.";
        }
    } catch (PDOException $e) {
        // Handle any errors
        echo "Error: " . $e->getMessage();
    }
}
?>

<!-- Example HTML Form to Get Product Details -->
<form method="POST" action="">
    <label for="product_id">Enter Product ID:</label>
    <input type="number" name="product_id" id="product_id" required>
    <button type="submit" name="get_product_details">Get Product Details</button>
</form>
