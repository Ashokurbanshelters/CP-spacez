<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Registration - Customer Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Lead Registration System</h1>
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Step 1: Customer Check</h2>
                
                <form id="customerCheckForm" action="check-customer.php" method="POST">
                    <div class="mb-6">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Customer Phone Number
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                +91
                            </span>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                pattern="[0-9]{10}" 
                                maxlength="10"
                                required
                                placeholder="Enter 10-digit number"
                                class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Enter 10-digit mobile number without +91</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200 font-medium">
                        Check Customer
                    </button>
                </form>
                
                <?php if(isset($_GET['error'])): ?>
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-red-600 text-sm"><?php echo htmlspecialchars($_GET['error']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>