<?php
session_start();
session_destroy();

// Redirect specifically to the root login file
// We use './' to tell the server "look in the current directory"
header("Location: ./login.php"); 
exit;
?>