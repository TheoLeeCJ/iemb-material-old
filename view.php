<?php
	require 'credentials.php';
	// session_start();
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
	// Log into iEMB
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/home/login');
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies/cookie_' . hash('sha512', $username) . '.txt');
	curl_exec($ch);
	if (!isset($_GET['board'])) {
		header('Location: view.php?board=1048');
		exit('<a href=\'view.php?board=1048\'>Click here to reload</a>');
	}
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/Board/Detail/' . $_GET['board']); //Gets actual data from iEMB board
	$content = curl_exec($ch);
	curl_close($ch);
	
	$dom = new DOMDocument();
	// Download HTML from iEMB to PHP
	@$dom->loadHTML('<!DOCTYPE html>' . $content);

	//Parses messages into JSON
	$unreadMessagesAsObject = []; $messagesParsed = 0;
	foreach ($dom->getElementById('tab_table')->getElementsByTagName('tr') as $key=>$row) {
		if ($key < 1) continue;
		if ($row->getElementsByTagName('td')->item(0)->textContent == file_get_contents('viewed_box1.txt')) break;
		$text = $row->getElementsByTagName('td')->item(0)->textContent;
		$unreadMessagesAsObject[$messagesParsed]['messageDate'] = substr($text, -60, -58) . ' ' . substr($text, -57, -54);

		// Username
		$text = $row->getElementsByTagName('td')->item(1)->textContent;
		$unreadMessagesAsObject[$messagesParsed]['messageAuthor'] = substr($text, 0, -112);

		// Heading
		$text = $row->getElementsByTagName('td')->item(2);
		$href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		$href = 'msg.php?board=' . substr($href, -4) . '&message=' . substr($href, 15, -11);

		$unreadMessagesAsObject[$messagesParsed]['url'] = $href;
		$unreadMessagesAsObject[$messagesParsed]['messageTitle'] = $text->textContent;

		$messagesParsed++;
	}

	//Parses messages into JSON
	$readMessagesAsObject = []; $messagesParsed = 0;
	foreach ($dom->getElementById('tab_table1')->getElementsByTagName('tr') as $key=>$row) {
		if ($key < 1) continue;
		if ($row->getElementsByTagName('td')->item(0)->textContent == file_get_contents('viewed_box2.txt')) break;
		// Date
		$text = $row->getElementsByTagName('td')->item(0)->textContent;
		$readMessagesAsObject[$messagesParsed]['messageDate'] = substr($text, 0, 2) . ' ' . substr($text, 3, 3);

		// Username
		$text = $row->getElementsByTagName('td')->item(1)->textContent;
		$readMessagesAsObject[$messagesParsed]['messageAuthor'] = substr($text, 0, -112);

		// Heading
		$text = $row->getElementsByTagName('td')->item(2);
		$href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		$href = 'msg.php?board=' . substr($href, -4) . '&message=' . substr($href, 15, -11);
		// Real: /Board/content/26433?board=1048
		$readMessagesAsObject[$messagesParsed]['url'] = $href;
		$readMessagesAsObject[$messagesParsed]['messageTitle'] = $text->textContent;

		$messagesParsed++;
	}
?>

<!DOCTYPE html>
<html lang='en-SG'>
<head>
	<title>
		<?php
			if ($_GET['board'] == '1048') { echo 'Student Board | iEMB'; }
			else if ($_GET['board'] == '1050') { echo 'Lost and Found | iEMB'; }
			else if ($_GET['board'] == '1049') { echo 'PSB Board | iEMB'; }
			else if ($_GET['board'] == '1039') { echo 'Service Board | iEMB'; }
			else if ($_GET['board'] == '1053') { echo 'Let\'s Serve! | iEMB'; }
		?>
	</title>
	<meta name='viewport' content='width=device-width, initial-scale=1.0' />

	<link rel='stylesheet' type='text/css' href='styling.css'>

	<style>
		body {
			cursor: default;
			position: absolute;
			top: 0; right: 0;	bottom: 0; left: 0;
		}
		
		.text {
			border-radius: 0;
			font-size: 1rem;
			display: block;
			width: 100%;
			height: 4.5rem;
			margin: .25rem auto;
			padding: .2rem;
			border: 1px solid #eee;
			border-bottom: 2px solid #ccc;
			outline: none;
		}

		.text-after {
			display: block;
			width: calc(100% + 7px);
			height: 2px;
			margin: auto;
			margin-top: calc(-2px - .25rem);
			transition: transform ease-in-out 200ms;
			transform: scaleX(0);
			background-color: #9a0007;
		}

		#headerContainer {
			height: 3rem;
			background-color: #f44336;
			color: #fff;
			line-height: 3rem;
			font-size: 1.5rem;
			padding: 0 1.5rem;
			position: fixed;
			z-index: 1;
			width: calc(100% - 48px);
			overflow: hidden;
		}
		header {
			height: 6rem;
			transform: translateY(-50%);
			transition: transform 250ms ease-in-out;
		}
		header #right {float: right;}
		header #header-logout {
			color: #fff;
			text-decoration: none;
			margin-left: 1rem;
		}

		#readAllProgress {
			height: 3rem;
			display: block;
			width: 100%;
		}

		/*Side menu*/
		nav {
			display: block;
			position: fixed;
			z-index: 3;
			top: 0;
			left: 0;
			height: 100%;
			background-color: #d32f2f;
			max-width: 75%;
			width: 15rem;
			transform: translateX(-100%);
			transition: transform 350ms ease-in-out;
			overflow-y: scroll;
			-webkit-overflow-scrolling: touch;
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
			background-image: linear-gradient(to bottom, #ffebee 30%, #ffcdd2 50%, #d32f2f);
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
			z-index: 2;
			transition: opacity 350ms ease-in-out;
		}
		#navOpen:checked ~ #navOverlay {
			opacity: .5;
			width: 100%;
			pointer-events: auto;
		}
		#navOpen {display: none;}
		label {cursor: pointer;}
		p {margin: 0;}
		
		#message-container {
			width: 40%;
			float: left;
			border-right: 1px solid #000;
			position: absolute;
			top: 37px;
			height: 100%;
			overflow-y: scroll;
			-webkit-overflow-scrolling: touch;
		}

		.message {
			color: #000;
			text-decoration: none;
			border-bottom: 1px solid #000;
			display: block;
			margin: 0 .5rem;
			padding-top: .5rem;
			padding-bottom: 1rem;
			padding-left: .5rem; padding-right: .5rem;
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
			position: absolute;
			right: 0;
			height: calc(100% + 34px);
			top: 0;
			overflow-y: scroll;
			-webkit-overflow-scrolling: touch;
		}

		#search {
			width: calc(40% - 2rem);
			border: 0;
			border-bottom: 1px solid #000;
			border-right: 1px solid #000;
			border-radius: 0;
			position: fixed;
			font-size: 1rem;
			outline: none;
			padding: .5rem 1rem;
		}

		#slider {
			width: 100%;
			position: relative;
			height: calc(100% - 85px);
			top: 48px;
			transition: 200ms ease-in-out transform;
		}

		@media screen and (max-width: 800px) {
			#header-name {display: none;}

			header {
				padding: 0 .75rem;
				width: calc(100% - 1.5rem);
			}

			#search {
				width: calc(50% - 2rem);
				position: relative;
				border-right: none;
			}

			#message-container, #message-view {
				border: none;
				width: 50%;
				height: 100%;
			}

			#slider {width: 200%;}
		}

		/*Loading spinner - displays when client waits for server to get a message*/
		#loadingSpinner {
			border: 16px solid #ccc;
			border-top: 16px solid #f44336;
			border-radius: 50%;
			width: 120px;
			height: 120px;
			animation: spin 1s linear infinite;
			position: relative;
			left: calc(50% - 60px);
			top: calc(50% - 60px);
			line-height: 120px;
			text-align: center;
		}
		#loadingSpinner:after {
			content: 'Loading...';
			display: block;
			animation: backSpin 1s linear infinite reverse;
		}
		@keyframes spin {
			0% {transform: rotate(0deg);}
			100% {transform: rotate(360deg);}
		}
		@keyframes backSpin {
			0% {transform: rotate(0deg);}
			100% {transform: rotate(360deg);}
		}

		label {
			cursor: pointer;
			margin-right: 8px;
			position: relative;
			top: 6px;
		}

		/*Defines radio button styles for response*/
		.radioStyle {
			height: 12px;
			width: 12px;
			margin-bottom: -2px;
			border-radius: 50%;
			display: inline-block;
			border: 2px solid #000;
			position: relative;
			z-index: 1;
		}

		.radio:checked + label .radioStyle:before {transform: scale(4, 4);}
		.radio:checked + label .radioStyle {border-color: #9a0007;}

		.radio:checked + label .radioStyle:after {
			-webkit-backface-visibility: hidden;
			backface-visibility: hidden;
			content: ' ';
			background-color: #9a0007;
			opacity: .5;
			height: 16px;
			width: 16px;
			transform: scale(0, 0);
			position: absolute;
			left: -2px;
			top: -2px;
			border-radius: 50%;
			animation: selectRipple 200ms;
			z-index: 1;
		}

		.radioStyle:before {
			-webkit-backface-visibility: hidden;
			backface-visibility: hidden;
			content: '';
			background-color: #9a0007;
			height: 2px;
			width: 2px;
			transform: scale(0, 0);
			position: absolute;
			left: 5px;
			top: 5px;
			border-radius: 1px;
			transition: transform 200ms ease-in-out;
			z-index: -1;
		}

		.radio {display: none;}

		@keyframes selectRipple {
			0% {transform: scale(0, 0) translateZ(0);}
			75% {transform: scale(1.5, 1.5) translateZ(0);}
			100% {transform: scale(0, 0) translateZ(0);}
		}

		.transformed {
			transform: translateX(-50%);
			height: calc(100% - 48px);
		}

		/*Top menu icon - morphs on mobile*/
		.Hotdog {
			display: inline-block;
			cursor: pointer;
		}

		.HotdogBun1, .HotdogSausage, .HotdogBun2 {
			width: 20px;
			height: 3px;
			background-color: white;
			margin: 6px 0;
			transition: 0.4s;
		}

		@media screen and (max-width: 800px) {
			label {
				left: -20px;
			}

			.change .HotdogBun1 {
				transform:
				rotate(-45deg)
				translate(-15px, -6px)
				scale(0.5, 1);
			}

			.change .HotdogSausage {
				transform: translate(-9px, 0px);
			}

			.change .HotdogBun2 {
				transform:
				rotate(45deg)
				translate(-15px, 6px)
				scale(0.5, 1);
			}
		}
		#attaches {margin: 2rem 0 0 .25rem;}
	</style>
	<script>
		//Updates search results based on user's search
		function updateSearchResults() {
			if (document.getElementById('search').value === '') {
				document.getElementById('message-container').innerHTML = '';
				parseMessages();
				refreshView();
			}
			else {
				document.getElementById('message-container').innerHTML = '';
				searchMessages(document.getElementById('search').value.toLowerCase());
				refreshView();
			}
		}

		//Searches through messages and outputs results to message container
		function searchMessages(searchString) {
			var resultsTable = document.getElementById('message-container'); //CHANGE TO ID OF TABLE FOR SEARCH RESULTS
			var unread = JSON.parse(document.getElementById('unreadMessagesJSON').innerHTML);
			var read = JSON.parse(document.getElementById('readMessagesJSON').innerHTML);

			var messagesParsed = 0;

			while (messagesParsed < unread.length) {
				if ((unread[messagesParsed].messageDate.trim().toLowerCase().includes(searchString) == true) || (unread[messagesParsed].messageAuthor.trim().toLowerCase().includes(searchString) == true) || (unread[messagesParsed].messageTitle.trim().toLowerCase().includes(searchString) == true)) {
					var messageRow = document.createElement('div');
					messageRow.setAttribute('id', 'a' + unread[messagesParsed].url.substr(31));
					messageRow.className = 'message';

					var messageDate = document.createElement('div');
					messageDate.innerHTML = unread[messagesParsed].messageDate.trim();
					messageDate.className = 'message-date';
					messageRow.innerHTML = messageRow.innerHTML + messageDate.outerHTML;
					
					var messageHeading = document.createElement('div');
					var messageHeadingBold = document.createElement('strong');
					messageHeadingBold.innerHTML = unread[messagesParsed].messageTitle.trim();
					messageHeading.className = 'message-header';
					messageHeading.appendChild(messageHeadingBold);
					messageRow.innerHTML = messageRow.innerHTML + messageHeading.outerHTML;

					var messageAuthor = document.createElement('div');
					messageAuthor.innerHTML = unread[messagesParsed].messageAuthor.trim();
					messageAuthor.className = 'message-username';
					messageRow.innerHTML = messageRow.innerHTML + messageAuthor.outerHTML;

					resultsTable.innerHTML = resultsTable.innerHTML + messageRow.outerHTML;
				}
				messagesParsed++;
			}

			messagesParsed = 0;

			while (messagesParsed < read.length) {
				if ((read[messagesParsed].messageDate.trim().toLowerCase().includes(searchString) == true) || (read[messagesParsed].messageAuthor.trim().toLowerCase().includes(searchString) == true) || (read[messagesParsed].messageTitle.trim().toLowerCase().includes(searchString) == true)) {
					var messageRow = document.createElement('div');
					messageRow.setAttribute('id', 'a' + read[messagesParsed].url.substr(31));
					messageRow.className = 'message';

					var messageDate = document.createElement('div');
					messageDate.innerHTML = read[messagesParsed].messageDate.trim();
					messageDate.className = 'message-date';
					messageRow.innerHTML = messageRow.innerHTML + messageDate.outerHTML;
					
					var messageHeading = document.createElement('div');
					messageHeading.innerHTML = read[messagesParsed].messageTitle.trim();
					messageHeading.className = 'message-header';
					messageRow.innerHTML = messageRow.innerHTML + messageHeading.outerHTML;

					var messageAuthor = document.createElement('div');
					messageAuthor.innerHTML = read[messagesParsed].messageAuthor.trim();
					messageAuthor.className = 'message-username';
					messageRow.innerHTML = messageRow.innerHTML + messageAuthor.outerHTML;

					resultsTable.innerHTML = resultsTable.innerHTML + messageRow.outerHTML;
				}
				messagesParsed++;
			}
		}

		//Parses and displays messages using JavaScript - do more things client side
		function parseMessages() {
			var outputDiv = document.getElementById('message-container');

			//UNREAD MESSAGES
			var messagesToGet = JSON.parse(document.getElementById('unreadMessagesJSON').innerHTML);
			messagesUnread = 0;

			while (messagesUnread < messagesToGet.length) {
				var messageRow = document.createElement('div');
				messageRow.setAttribute('id', 'a' + messagesToGet[messagesUnread].url.substr(31));
				messageRow.className = 'message';

				var messageDate = document.createElement('div');
				messageDate.innerHTML = messagesToGet[messagesUnread].messageDate.trim();
				messageDate.className = 'message-date';
				messageRow.innerHTML = messageRow.innerHTML + messageDate.outerHTML;
				
				var messageHeading = document.createElement('div');
				messageHeading.innerHTML = messagesToGet[messagesUnread].messageTitle.trim();
				messageHeading.className = 'message-header';
				messageHeading.style.fontWeight = 'bold';
				messageRow.innerHTML = messageRow.innerHTML + messageHeading.outerHTML;

				var messageAuthor = document.createElement('div');
				messageAuthor.innerHTML = messagesToGet[messagesUnread].messageAuthor.trim();
				messageAuthor.className = 'message-username';
				messageRow.innerHTML = messageRow.innerHTML + messageAuthor.outerHTML;

				outputDiv.innerHTML = outputDiv.innerHTML + messageRow.outerHTML;
				messagesUnread++;
			}

			//READ MESSAGES
			messagesToGet = JSON.parse(document.getElementById('readMessagesJSON').innerHTML);
			var messagesParsed = 0;

			while (messagesParsed < messagesToGet.length) {
				var messageRow = document.createElement('div');
				messageRow.setAttribute('id', 'a' + messagesToGet[messagesParsed].url.substr(31));
				messageRow.className = 'message';

				var messageDate = document.createElement('div');
				messageDate.innerHTML = messagesToGet[messagesParsed].messageDate.trim();
				messageDate.className = 'message-date';
				messageRow.innerHTML = messageRow.innerHTML + messageDate.outerHTML;
			
				var messageHeading = document.createElement('div');
				messageHeading.innerHTML = messagesToGet[messagesParsed].messageTitle.trim();
				messageHeading.className = 'message-header';
				messageRow.innerHTML = messageRow.innerHTML + messageHeading.outerHTML;

				var messageAuthor = document.createElement('div');
				messageAuthor.innerHTML = messagesToGet[messagesParsed].messageAuthor.trim();
				messageAuthor.className = 'message-username';
				messageRow.innerHTML = messageRow.innerHTML + messageAuthor.outerHTML;

				outputDiv.innerHTML = outputDiv.innerHTML + messageRow.outerHTML;
				messagesParsed++;
			}
		}

		//Retrievs message content from server and displays
		function getMessage() {
			document.getElementById('message-view').innerHTML = '';
			var spinner = document.createElement('div');
			spinner.id = 'loadingSpinner';
			document.getElementById('message-view').appendChild(spinner);
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

		//Adds click handlers to messages
		function refreshView() {
			messages = document.getElementsByClassName('message');
			selectMessage = messages[0].id;
			for (i = 0; i < messages.length; i++) messages[i].addEventListener('click', getMessage, false);
			mobileRefresh();
		}

		//Change look of screen on mobile...
		function mobileRefresh() {
			if (window.innerWidth < 800) for (i = 0; i < messages.length; i++) messages[i].addEventListener('click', transformSlider, false);
			else for (i = 0; i < messages.length; i++) messages[i].removeEventListener('click', transformSlider, false);
		}

		//Responsible for menu icon and sliding behaviour on mobile/small screens/hugely zoomed in displays
		function transformSlider() {
			var hotdog = document.getElementsByClassName('Hotdog')[0];
			document.getElementById('slider').classList.toggle('transformed');
			hotdog.classList.toggle('change');

			if (hotdog.getAttribute('data-messageopen') == 'true') {
				hotdog.removeEventListener('click', transformSlider);
				hotdog.setAttribute('data-messageopen', 'false');
				setTimeout(function() { document.getElementById("MenuContainer").setAttribute("for", "navOpen"); }, 1000);
			}
			else {
				hotdog.addEventListener('click', transformSlider);
				hotdog.setAttribute('data-messageopen', 'true');
				document.getElementById("MenuContainer").removeAttribute("for");
			}
		}

		//Reads all messages :D
		function readAll() {
			document.getElementById('message-view').innerHTML = 'Reading all messages...';
			document.getElementById('readAllProgress').max = messagesUnread;
			document.getElementsByTagName('header')[0].style.transform = 'translateY(0)';
			var done = 0;
			for (i = 0; i < messagesUnread; i++) {
				var request = new XMLHttpRequest();
				request.onreadystatechange = function() {
					if (this.readyState === 4 && this.status === 200) {
						document.getElementById('readAllProgress').value += 1;
						done += 1;
					}
				};
				request.open('GET', 'getmessage.php?board='+<?php echo $_GET['board'] ?>+'&message='+document.getElementById('message-container').getElementsByTagName('div')[i].id.substr(1), true);
				request.send();
			}
			var checkDone = setInterval(function() {
				if (done == messagesUnread) {
					document.getElementById('message-view').innerHTML = 'All messages read';
					document.getElementsByTagName('header')[0].style.transform = 'translateY(-50%)';
					clearInterval(checkDone);
				}
			}, 5000);
		}
		document.addEventListener('DOMContentLoaded', function() {
			parseMessages();
			refreshView();
			document.getElementById('header-read').addEventListener('click', readAll, false);
			window.addEventListener('resize', mobileRefresh);
			document.addEventListener('keydown', keyDown);
			location.href = '#';
		});
		function keyDown(event) {
			event = event || window.event;
			console.log(event.keyCode);
			if (event.keyCode == '38') {
				event.preventDefault();
				document.getElementById(selectMessage).previousSibling.click();
				scrollKey();
			}
			else if (event.keyCode == '40') {
				event.preventDefault();
				document.getElementById(selectMessage).nextSibling.click();
				scrollKey();
			}
			if (event.keyCode  == 49 ||
				 event.keyCode == 50 ||
				 event.keyCode == 51 ||
				 event.keyCode == 52 ||
				 event.keyCode == 53 ||
				 event.keyCode == 54 ||
				 event.keyCode == 55 ||
				 event.keyCode == 56 ||
				 event.keyCode == 57 ||
				 event.keyCode == 48 ||
				 event.keyCode == 81 ||
				 event.keyCode == 87 ||
				 event.keyCode == 69 ||
				 event.keyCode == 82 ||
				 event.keyCode == 84 ||
				 event.keyCode == 89 ||
				 event.keyCode == 85 ||
				 event.keyCode == 73 ||
				 event.keyCode == 79 ||
				 event.keyCode == 80 ||
				 event.keyCode == 65 ||
				 event.keyCode == 83 ||
				 event.keyCode == 68 ||
				 event.keyCode == 70 ||
				 event.keyCode == 71 ||
				 event.keyCode == 72 ||
				 event.keyCode == 74 ||
				 event.keyCode == 75 ||
				 event.keyCode == 76 ||
				 event.keyCode == 90 ||
				 event.keyCode == 88 ||
				 event.keyCode == 67 ||
				 event.keyCode == 86 ||
				 event.keyCode == 66 ||
				 event.keyCode == 78 ||
				 event.keyCode == 77 ||
				 event.keyCode == 192 ||
				 event.keyCode == 189 ||
				 event.keyCode == 187 ||
				 event.keyCode == 219 ||
				 event.keyCode == 221 ||
				 event.keyCode == 220 ||
				 event.keyCode == 186 ||
				 event.keyCode == 222 ||
				 event.keyCode == 188 ||
				 event.keyCode == 190 ||
				 event.keyCode == 191) document.getElementById('search').focus();
		}
		function scrollKey() {
			var rect = document.getElementById(selectMessage).getBoundingClientRect();
			if (window.innerHeight - rect.bottom < 70) {
				location.href = '#' + selectMessage;
				var style = window.getComputedStyle(document.getElementById('message-container'), null);
				document.getElementById('message-container').scrollTop -= parseInt(style.getPropertyValue('height')) - 144;
			}
			else if (rect.top < 158) {
				location.href = '#' + document.getElementById(selectMessage).id;
				document.getElementById('message-container').scrollTop -= 72;
			}
		}
	</script>
</head>

<body>
	<!--Header bar - menu icon, function buttons, logout-->
	<div id='headerContainer'>
		<header>
			<progress id='readAllProgress' value='0' max='0'></progress>
			<label id="MenuContainer" for='navOpen'>
				<div class='Hotdog'>
					<div class='HotdogBun1'></div>
					<div class='HotdogSausage'></div>
					<div class='HotdogBun2'></div>
				</div>
			</label> iEMB
			<div id='right'>
				<span id='header-name'>Welcome, <?php echo $username; ?></span>
				<span id='header-read'>Read all</span>
				<a href='logout.php' id='header-logout'>Log Out</a>
			</div>
		</header>
	</div>

	<!--Side menu-->
	<input type='checkbox' id='navOpen'>
	<nav>
		<img src='logo.svg' id='Hwa Chong Logo'>
		<a href='view.php?board=1048'>Student</a>
		<a href='view.php?board=1050'>Lost &amp; Found</a>
		<a href='view.php?board=1049'>PSB</a>
		<a href='view.php?board=1039'>Service</a>
		<a href='view.php?board=1053'>Let's Serve!</a>
	</nav>
	<label for='navOpen' id='navOverlay'></label>

	<!--SEARCH-->
	<div id='slider'>
		<input onkeyup='updateSearchResults();' id='search' placeholder='Search...' tabindex='2'>
		<!--MESSAGES-->
		<div id='message-container'></div>
		<div id='message-view'></div>
	</div>

	<!--DIVs for JavaScript to get content from-->
	<?php
		echo '<div id=\'unreadMessagesJSON\' style=\'display: none;\'>' . json_encode($unreadMessagesAsObject) . '</div>';
		echo '<div id=\'readMessagesJSON\' style=\'display: none;\'>' . json_encode($readMessagesAsObject) . '</div>';
	?>
</body>
</html>