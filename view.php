<?php
	session_start();
	if (isset($_SESSION['logged_in'])) {
		if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
			header('Location: index.php?again=true');
			exit('An error has occured. Please log in again <a href="index.php"here</a>');
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
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/Board/Detail/1048');
	$content = curl_exec($ch);
	curl_close($ch);
	$dom = new DOMDocument();
	@$dom->loadHTML($content);
?>
<!DOCTYPE html>
<html lang='en-SG'>
<head>
	<title>iEMB</title>
	<style>
		html {
			font: 1em/1.5rem -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
			height: 100%;
		}
		body {margin: 0;}
		header {
			background-color: #607d8b;
			color: #fff;
			line-height: 3rem;
			font-size: 1.5rem;
			padding: 0 2rem;
		}
		header #right {float: right;}
		header #header-logout {
			color: #fff;
			text-decoration: none;
			margin-left: 1rem;
		}
		@media screen and (max-width: 800px) {
			#header-name {display: none;}
			header {padding: 0 .75rem;}
		}
		nav {
			display: block;
			position: fixed;
			z-index: 300;
			top: 0;
			left: 0;
			bottom: 0;
			background-color: #455a64;
			max-width: 75%;
			width: 15rem;
			transform: translateX(-100%);
			transition: transform 350ms ease-in-out;
		}
		nav a {
			display: block;
			color: #fff;
			text-decoration: none;
			font-size: 1.5rem;
			line-height: 3rem;
			padding-left: 1rem;
			position: relative;
		}
		nav a:after {
			position: absolute;
			top: 0;
			left: 0;
			bottom: 0;
			right: 0;
			content: '';
			background-color: #263238;
			z-index: -1;
			opacity: 0;
		}
		nav a:hover:after {opacity: 1;}
		nav img {
			width: calc(100% - 4rem);
			display: block;
			padding: 2rem;
			background: linear-gradient(to bottom, #263238 50%, #37474F 75%, #455a64);
		}
		#sideOpen:checked ~ nav {transform: translateX(0);}
		#navOverlay {
			opacity: 0;
			background-color: #000;
			pointer-events: none;
			position: fixed;
			top: 0;
			left: 0;
			bottom: 0;
			right: 0;
			transition: opacity 350ms ease-in-out;
		}
		#sideOpen:checked ~ #navOverlay {
			opacity: .5;
			pointer-events: auto;
		}
		#sideOpen {display: none;}
		label {cursor: pointer;}
		td {border-top: 1px solid #ccc;}
		table {
			border-collapse: collapse;
		}
		td, th {
			padding: .35rem;
			white-space: nowrap;
			text-align: center;
			overflow: hidden;
		}
		td:first-child {
			max-width: 3rem;
			width: 3rem;
		}
		td:nth-child(2n) {
			max-width: 5rem;
			width: 5rem;
			text-overflow: ellipsis;
		}
		td:last-child {
			text-align: left;
			width: calc(100vw - 8rem - 2.1rem);
			max-width: calc(100vw - 8rem - 2.1rem);
			white-space: nowrap;
			text-overflow: ellipsis;
		}
	</style>
</head>
<body>
<header>
	<label for='sideOpen'>☰</label> iEMB 2.0
	<div id='right'><span id='header-name'>Welcome, <?php echo $username; ?></span><a href='logout.php' id='header-logout'>Log Out</a></div>
</header>
<input type='checkbox' id='sideOpen'>
<nav>
	<img src='logo.svg'>
	<a href='view/student-archive.php'>Student - Archive</a>
	<a href='view/lost-found.php'>Lost &amp; Found</a>
	<a href='view/service.php'>Service</a>
	<a href='view/psb.php'>PSB</a>
</nav>
<label for='sideOpen' id='navOverlay'></label>
<?php
	echo '<strong>Unread Messages</strong>';
	echo '<table>';
	echo '<th>Date</th>';
	echo '<th>From</th>';
	echo '<th>Title</th>';
	$a = 0;
	foreach ($dom->getElementById('tab_table')->getElementsByTagName('tr') as $key=>$row) {
		if ($key === 0) continue;
		echo '<tr>';
		// Date
		$text = $row->getElementsByTagName('td')->item(0)->textContent;
		echo '<td>';
		// echo substr($text, 0, 2) . ' ' . substr($text, 3, 3);
		echo substr($text, -60, -58) . ' ' . substr($text, -57, -54);
		echo '</td>';
		// Username
		$text = $row->getElementsByTagName('td')->item(1)->textContent;
		echo '<td>';
		echo substr($text, 0, -112);
		echo '</td>';
		// Heading
		$text = $row->getElementsByTagName('td')->item(2);
		$href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		$href = 'msg.php?board=' . substr($href, -4) . '&message=' . substr($href, 15, -11);
		// Real: /Board/content/26433?board=1048
		echo '<td>';
		echo '<a href="' . $href . '">' . $text->textContent . '</a>';
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
?>
<?php
	echo '<strong>Read Messages</strong>';
	echo '<table>';
	echo '<th>Date</th>';
	echo '<th>From</th>';
	echo '<th>Title</th>';
	$a = 0;
	foreach ($dom->getElementById('tab_table1')->getElementsByTagName('tr') as $key=>$row) {
		if ($key === 0) continue;
		echo '<tr>';
		// Date
		$text = $row->getElementsByTagName('td')->item(0)->textContent;
		echo '<td>';
		echo substr($text, 0, 2) . ' ' . substr($text, 3, 3);
		echo '</td>';
		// Username
		$text = $row->getElementsByTagName('td')->item(1)->textContent;
		echo '<td>';
		echo substr($text, 0, -112);
		echo '</td>';
		// Heading
		$text = $row->getElementsByTagName('td')->item(2);
		$href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		$href = 'msg.php?board=' . substr($href, -4) . '&message=' . substr($href, 15, -11);
		// Real: /Board/content/26433?board=1048
		echo '<td>';
		echo '<a href="' . $href . '">' . $text->textContent . '</a>';
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
?>
</body>
</html>