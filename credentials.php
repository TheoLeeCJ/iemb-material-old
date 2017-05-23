<?php
	// session_set_cookie_params(300, '/', '.iemb.ml', true, true);
	// above for uploaded file
	// session_set_cookie_params(400, '/', '.locahost', true, true);
	session_start();
	if (!isset($_SESSION['canary'])) {
		session_regenerate_id(true);
		$_SESSION['canary'] = time();
	}
	if ($_SESSION['canary'] < time() - 300) {
		session_regenerate_id(true);
		$_SESSION['canary'] = time();
	}
	if ($_SESSION['username'] !== '' && $_SESSION['password'] !== '') {
		$conn = ftp_connect('www2.hci.edu.sg') or exit('Error: Failed to connect with server. Please try again later.');
		if (!@ftp_login($conn, 'hci\\' . $_SESSION['username'], $_SESSION['password'])) {
			header('Location: index.php');
			exit('Please log in');
		}
		ftp_close($conn);
	}
	else {
		header('Location: index.php');
		exit('Please log in');
	}
?>