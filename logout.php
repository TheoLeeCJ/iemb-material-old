<?php
	// session_set_cookie_params(300, '/', '.iemb.ml', true, true);
	// above for uploaded file
	session_set_cookie_params(300, '/', '.locahost', true, true);
	session_start();
	unlink('cookies/cookie_' . hash('sha512', $_SESSION['username']) . '.txt');
	foreach (array_keys($_SESSION) as $key) {
		unset($_SESSION[$key]);
	}
	session_destroy();
	header('Location: index.php?logged_out=true');
	exit('You have been logged out');
?>