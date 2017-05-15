<?php
	session_start();
	if (isset($_SESSION['logged_in'])) {
		if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
			header('Location: index.php?again=true');
			exit('An error has occured. Please log in again <a href="index.php">here</a>');
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
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies/cookie_' . $username . '.txt');
	curl_exec($ch);
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/Board/content/' . $_GET['message'] . '?board=' . $_GET['board']);
	// https://iemb.hci.edu.sg/Board/content/27633?board=1048
	$content = curl_exec($ch);
	curl_close($ch);
	$dom = new DOMDocument();
	@$dom->loadHTML($content);
	$finder = new DOMXpath($dom);
	$spaner = $finder->query('//*[contains(@class, "read_message_body_div")]');
	echo '<div id="view-header"><strong>' . substr($spaner->item(0)->getElementsByTagName('div')->item(5)->textContent, 7) . '</strong><br>';
	echo substr($spaner->item(0)->getElementsByTagName('div')->item(6)->textContent, 7) . '<br>';
	echo substr($spaner->item(0)->getElementsByTagName('div')->item(8)->textContent, 7) . '<br></div>';
    $text = $dom->saveXML($spaner->item(0)->getElementsByTagName('div')->item(13));
	echo '<div id="view-body">' . $text . '</div>';
?>