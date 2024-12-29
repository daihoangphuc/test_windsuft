<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID')); // TODO: Add your Google Client ID
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET')); // TODO: Add your Google Client Secret
define('GOOGLE_REDIRECT_URI', 'http://localhost:81/test_windsuft/google-callback.php');

// Required Google OAuth scopes
define('GOOGLE_SCOPES', [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile'
]);
