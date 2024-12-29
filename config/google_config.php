<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '702593637962-rijvvltjcsn6bn6ik7424nmr0r0c4otl.apps.googleusercontent.com'); // TODO: Add your Google Client ID
define('GOOGLE_CLIENT_SECRET', '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'); // TODO: Add your Google Client Secret
define('GOOGLE_REDIRECT_URI', 'http://localhost:81/test_windsuft/google-callback.php');

// Required Google OAuth scopes
define('GOOGLE_SCOPES', [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile'
]);
