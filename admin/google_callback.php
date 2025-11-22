<?php
session_start();
 
// Your Google OAuth settings
$clientID     = "216987089784-glpcj6l3gknic0seqc63ctk4h2st89v3.apps.googleusercontent.com";
$clientSecret = "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";
$redirectURI  = "https://chandusoft.test/admin/google_callback.php";

 
// STEP 1 — Get "code" from Google
if (!isset($_GET['code'])) {
    die("Authorization code not returned by Google.");
}
 
// STEP 2 — Exchange code for access token
$tokenURL = "https://oauth2.googleapis.com/token";
 
$data = [
    'code'          => $_GET['code'],
    'client_id'     => $clientID,
    'client_secret' => $clientSecret,
    'redirect_uri'  => $redirectURI,
    'grant_type'    => 'authorization_code'
];
 
$ch = curl_init($tokenURL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
$response = curl_exec($ch);
curl_close($ch);
 
$tokenInfo = json_decode($response, true);
 
if (!isset($tokenInfo['access_token'])) {
    die("Failed to get access token. Response: " . $response);
}
 
// STEP 3 — Fetch user info
$userInfoURL = "https://www.googleapis.com/oauth2/v2/userinfo";
 
$ch = curl_init($userInfoURL);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $tokenInfo['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
$userInfo = json_decode(curl_exec($ch), true);
curl_close($ch);
 
if (!isset($userInfo['email'])) {
    die("Failed to fetch user info.");
}
 
// STEP 4 — Log the user in (store email in session)
// You may replace this with database logic
$_SESSION['user_email'] = $userInfo['email'];
$_SESSION['user_name']  = $userInfo['name'] ?? '';
 
header("Location: /../admin/dashboard.php");
exit;