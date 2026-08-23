<?php

// Copy this file to smtp_config.php and enter your own SMTP credentials.
// smtp_config.php is ignored by Git and must never be committed.
return array(
    'enabled' => true,
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'your-account@example.com',
    'password' => 'your-smtp-app-password',
    'encryption' => 'tls', // tls, ssl, or none
    'sender_email' => 'your-account@example.com',
    'sender_name' => 'Student Routine Organizer',

    // Public project root, without a trailing slash.
    // Use HTTPS outside local development.
    'application_base_url' => 'http://localhost/student-routine-organizer'
);

