<?php
// /app/google-login.php
 
// Start session
session_start();
 
// Replace with your Google OAuth credentials and redirect URI
$clientID = '216987089784-glpcj6l3gknic0seqc63ctk4h2st89v3.apps.googleusercontent.com';
$redirectURI = 'https://yourdomain.com/admin/google_callback.php';
 
// Build the Google OAuth URL
$params = [
    'client_id' => $clientID,
    'redirect_uri' => $redirectURI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'offline',
    'prompt' => 'consent',
];
 
$oauthURL = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
 
// Redirect user to Google OAuth consent screen
header('Location: ' . $oauthURL);
exit();
 
 