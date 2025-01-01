<?php
require_once __DIR__ . '/config/google_config.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

// Add all required scopes
foreach (GOOGLE_SCOPES as $scope) {
    $client->addScope($scope);
}

$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
