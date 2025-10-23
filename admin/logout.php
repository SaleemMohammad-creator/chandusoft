<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

// Destroy session
session_unset();
session_destroy();
redirect('login.php');