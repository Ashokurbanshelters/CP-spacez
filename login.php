<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Real Estate Lead Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-indigo-600 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto mt-16">
            <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6">
                    <h1 class="text-2xl font-bold text-white text-center">Real Estate CRM</h1>
                    <p class="text-blue-100 text-center mt-2">Lead Management System</p>
                </div>
                
                <div class="p-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Login to Dashboard</h2>
                    
                    <form action="process-login.php" method="POST" id="loginForm">
                        <div class="mb-4">
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                Username
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    required
                                    class="pl-10 w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    placeholder="Enter username"
                                >
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required
                                    class="pl-10 w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    placeholder="Enter password"
                                >
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">Remember me</span>
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-2 px-4 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition duration-200 font-medium">
                            Login
                        </button>
                    </form>
                    
                    <?php if(isset($_GET['error'])): ?>
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-600 text-sm text-center">
                            <?php 
                            if($_GET['error'] == 'invalid') {
                                echo 'Invalid username or password!';
                            } elseif($_GET['error'] == 'session') {
                                echo 'Please login to access the dashboard!';
                            } else {
                                echo 'An error occurred. Please try again!';
                            }
                            ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['logout'])): ?>
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-600 text-sm text-center">You have been logged out successfully!</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500">
                            Default credentials:<br>
                            Username: admin | Password: admin123
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>