<?php
	/*
	  This file is responsible for submitting responses to iEMB servers using POST as actual iEMB uses a mere form for this.
	*/

	if (!isset($_POST['boardid']) || !isset($_POST['topic']) || !isset($_POST['replyto'])) {
		$board = isset($_POST['boardid']) ? $_POST['boardid'] : '1048';
		header('Location: view.php?board=' . $board);
		exit();
	}
	session_start();
	$username = $_SESSION['username'];
	$password = $_SESSION['password'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=' . $username . '&password=' . $password);
	curl_setopt($ch, CURLOPT_POSTREDIR, 2);
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/home/login');
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies/cookie_' . $username . '.txt');
	curl_exec($ch);
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/board/ProcessResponse');
	$replyContent = isset($_POST['replyContent']) ? urlencode($_POST['replyContent']) : '';
	$UserRating = isset($_POST['UserRating']) ? urlencode($_POST['UserRating']) : '';
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=' . $username . '&password=' . $password . '&boardid=' . $_POST['boardid'] . '&topic=' . $_POST['topic'] . '&replyto=' . $_POST['replyto'] . '&replyContent=' . $replyContent . '&UserRating=' . $UserRating . 'PostMessage=Post+Reply');
	curl_exec($ch);
	curl_close($ch);
	header('Location: view.php?board=' . $_POST['boardid']);
?>