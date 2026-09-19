<?php
// Simple user creation script
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    // Load existing users
    $usersFile = 'data/users.json';
    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
    
    // Check if username exists
    $usernameExists = false;
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $usernameExists = true;
            break;
        }
    }
    
    if ($usernameExists) {
        $error = "Username '$username' already exists!";
    } else {
        // Add new user
        $newUser = [
            'id' => 'USR' . date('YmdHis') . rand(100, 999),
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ];
        
        $users[] = $newUser;
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        
        $success = "User '$username' created successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Add New User</h1>
                
                <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-red-600 text-sm"><?php echo $error; ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md">
                    <p class="text-green-600 text-sm"><?php echo $success; ?></p>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" id="username" name="username" required 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" required 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" required 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div class="mb-6">
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select id="role" name="role" required 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="sales">Sales</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200">
                        Create User
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>