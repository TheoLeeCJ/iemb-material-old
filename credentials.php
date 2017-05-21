<?php
	session_start();
	// This file checks login status
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