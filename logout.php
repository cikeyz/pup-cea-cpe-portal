<?php
/* =====================================================================
   logout.php  ·  Activity-08 Group 4
   ---------------------------------------------------------------------
   Clear the session completely, then send the user back to the login
   page. session_start() runs before any output so unset/destroy work
   correctly (the lecture sample echoed text first, which broke this).
   ===================================================================== */

session_start();
$_SESSION = [];
session_unset();
session_destroy();

header('Location: index.php');
exit;
