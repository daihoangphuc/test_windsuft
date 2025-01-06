<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '702593637962-rijvvltjcsn6bn6ik7424nmr0r0c4otl.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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'); 
define('GOOGLE_REDIRECT_URI', 'http://localhost:81/manage-htsv/google-callback.php');

// Required Google OAuth scopes
define('GOOGLE_SCOPES', [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile'
]);
