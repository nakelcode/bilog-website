<?php

session_start();

unset($_SESSION["user_id"]);
unset($_SESSION["full_name"]);

header("Location: login.php");
exit;

