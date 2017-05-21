<?php
	if (isset($_GET['name']) && isset($_GET['message']) && isset($_GET['board'])) {
		session_start();
		if (isset($_SESSION['logged_in'])) {
			if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
				header('Location: index.php?again=true');
				exit('An error has occured. Please log in again <a href=\'index.php\'>here</a>');
			}
		}
		$username = $_SESSION['username'];
		$password = $_SESSION['password'];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=' . $username . '&password=' . $password);
		curl_setopt($ch, CURLOPT_POSTREDIR, 2);
		curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/home/login');
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies/cookie_' . hash('sha512', $username) . '.txt');
		curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/Board/ShowFile?t=2&ctype=1&id=' . $_GET['message'] . '&file=' . rawurlencode($_GET['name']) . '&boardId=' . $_GET['board']);
		$fileCurl = curl_exec($ch);
		$fileType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);
		$file = fopen('temp/' . hash('sha512', $_GET['name']), 'w+');
		fwrite($file, $fileCurl);
		fclose($file);
		header('Content-Description: File Transfer');
		// header('Content-Type: application/octet-stream');
		header('Content-Type: ' . $fileType);
		header('Content-Disposition: attachment; filename="' . $_GET['name'] . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		readfile('temp/' . hash('sha512', $_GET['name']));
		unlink('temp/' . hash('sha512', $_GET['name']));
		exit();
	}
?>