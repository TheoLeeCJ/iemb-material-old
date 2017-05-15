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
	if (!isset($_GET['board'])) {
		header('Location: view.php?board=1048');
		exit('<a href="view.php?board=1048">Click here to reload</a>');
	}
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/Board/Detail/' . $_GET['board']);
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
			background-color: #f44336;
			color: #fff;
			line-height: 3rem;
			font-size: 1.5rem;
			padding: 0 1.5rem;
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
			background-color: #d32f2f;
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
			background-color: #b71c1c;
			z-index: -1;
			opacity: 0;
		}
		nav a:hover:after {opacity: 1;}
		nav img {
			width: calc(100% - 4rem);
			display: block;
			padding: 2rem;
			background: linear-gradient(to bottom, #FFEBEE 30%, #FFCDD2 50%, #d32f2f);
		}
		#navOpen:checked ~ nav {transform: translateX(0);}
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
		#navOpen:checked ~ #navOverlay {
			opacity: .5;
			pointer-events: auto;
		}
		#navOpen {display: none;}
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
		#message-container {
			width: 40%;
			float: left;
			border-right: 1px solid #000;
		}
		.message {
			color: #000;
			text-decoration: none;
			border-bottom: 1px solid #000;
			display: block;
			margin: 0 .5rem;
			padding-top: .5rem;
			padding-bottom: 1rem;
			cursor: pointer;
		}
		.message-header, .message-date, .message-username {
			text-overflow: ellipsis;
			overflow: hidden;
			white-space: nowrap;
		}
		.message-header {font-size: 1.2rem;}
		.message-date {float: right;}
		.message-username {clear: right;}
		#view-header {
			margin: 1rem;
			margin-bottom: .5rem;
			padding-bottom: .25rem;
			border-bottom: 1px solid #000;
		}
		#view-body {padding: 0 calc(1rem - 5px);}
		#message-view {
			width: calc(60% - 2px);
			float: left;
		}
	</style>
</head>
<body>
<header>
	<label for='navOpen'>☰</label> iEMB 2.0
	<div id='right'><span id='header-name'>Welcome, <?php echo $username; ?></span><a href='logout.php' id='header-logout'>Log Out</a></div>
</header>
<input type='checkbox' id='navOpen'>
<nav>
	<img src='logo.svg'>
	<a href='view.php?board=1048'>Student</a>
	<a href='view.php?board=1050'>Lost &amp; Found</a>
	<a href='view.php?board=1049'>PSB</a>
	<a href='view.php?board=1039'>Service</a>
	<a href='view.php?board=1053'>Let's Serve!</a>
	<!-- Problematic in PHP, don't know why -->
</nav>
<label for='navOpen' id='navOverlay'></label>
<div id='message-container'>
<?php
	// min not defined for service and let's serve, assuming to be 2
	$min = 1;
	// elseif ($_GET['board'] == 1050) $min = 2;
	// elseif ($_GET['board'] == 1049) $min = 2;
	$a = 0;
	foreach ($dom->getElementById('tab_table')->getElementsByTagName('tr') as $key=>$row) {
		if ($key < $min) continue;
		$text = $row->getElementsByTagName('td')->item(2);
		if (is_null($text)) break;
		$href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		echo '<div id="a' . substr($href, 15, -11) . '" class="message">';
			// Date
			$text = $row->getElementsByTagName('td')->item(0)->textContent;
			echo '<div class="message-date">' . substr($text, -60, -58) . ' ' . substr($text, -57, -54) . '</div>';
			// Heading
			$text = $row->getElementsByTagName('td')->item(2);
			echo '<div class="message-header"><strong>' . $text->textContent . '</strong></div>';
			// Username
			$text = $row->getElementsByTagName('td')->item(1)->textContent;
			echo '<div class="message-username">' . substr($text, 0, -112) . '</div>';
		echo '</div>';
		$a += 1;
	}
	foreach ($dom->getElementById('tab_table1')->getElementsByTagName('tr') as $key=>$row) {
		if ($key < $min) continue;
		$text = $row->getElementsByTagName('td')->item(2);
		if (is_null($text)) break;
		if ($row->getElementsByTagName('td')->item(2)->textContent === '') echo 'yay';
		$href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		echo '<div id="a' . substr($href, 15, -11) . '" class="message">';
			// Date
			$text = $row->getElementsByTagName('td')->item(0)->textContent;
			echo '<div class="message-date">' . substr($text, 0, 2) . ' ' . substr($text, 3, 3) . '</div>';
			// Heading
			// Real: /Board/content/26433?board=1048
			$text = $row->getElementsByTagName('td')->item(2);
			echo '<div class="message-header">' . $text->textContent . '</div>';
			// Username
			$text = $row->getElementsByTagName('td')->item(1)->textContent;
			echo '<div class="message-username">' . substr($text, 0, -112) . '</div>';
		echo '</div>';
		$a += 1;
	}
	echo '</div>';
	if ($a === 0) echo '<div class="message"><strong>No messages</strong></div>';
?>
</div>
<div id='message-view'></div>
<script>
	var messages = document.getElementsByClassName('message');
	selectMessage = messages[0].id;
	for (i = 0; i < messages.length; i++) messages[i].addEventListener('click', getMessage, false);
	function getMessage() {
		document.getElementById(selectMessage.toString()).removeAttribute('style');
		this.style.backgroundColor = '#ff8a80';
		selectMessage = this.id;
		var request = new XMLHttpRequest();
		request.onreadystatechange = function() {
			if (this.readyState === 4 && this.status === 200) document.getElementById('message-view').innerHTML = this.responseText;
		};
		request.open('GET', 'getmessage.php?board='+<?php echo $_GET['board'] ?>+'&message='+this.id.substr(1), true);
		request.send();
	}
</script>
</body>
</html>