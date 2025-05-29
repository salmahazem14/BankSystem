<?php
// Get the requested path (e.g., /about or /non-existent)
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Define allowed pages (routes)
$routes = [
    '/landingPage.php' => 'landingPage.php',
    '/admin-dashboard.php' => 'admin-dashboard.php',
    '/complaints.php' => 'complaints.php',
    '/login.php' => 'login.php',
    '/messages.php' => 'messages.php',
    '/statement.php' => 'statement.php',
    '/transactions.php' => 'transactions.php',
    '/user-dashboard.php' => 'user-dashboard.php',
    
    
];

// If path is defined in routes, include the corresponding file
if (array_key_exists($path, $routes)) {
    include $routes[$path];
} else {
    include 'notFoundPage.php'; // Show custom 404 page
}
