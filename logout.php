<?php
	session_start();
	unlink('cookies/cookie_' . hash('sha512', $_SESSION['username']) . '.txt');
	unset($_SESSION['username']);
	unset($_SESSION['password']);
	unset($_SESSION['logged_in']);
	session_destroy();
	header('Location: index.php?logged_out=true');
	exit('You have been logged out');
?>