<?php
/**
 * IRECSTEM 2026 - Logout Handler
 */

session_start();
session_destroy();
header('Location: auth.php');
exit;
