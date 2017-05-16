<!-- LINE 88 -->
<?php
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
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies/cookie_' . $username . '.txt');
	curl_exec($ch);
	if (!isset($_GET['board'])) {
		header('Location: view.php?board=1048');
		exit('<a href=\'view.php?board=1048\'>Click here to reload</a>');
	}
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/Board/Detail/' . $_GET['board']);
	$content = curl_exec($ch);
	curl_close($ch);
	$dom = new DOMDocument();
	@$dom->loadHTML($content);
	$a = 0; $unreadMessagesAsObject = []; $messagesParsed = 0;
	foreach ($dom->getElementById('tab_table')->getElementsByTagName('tr') as $key=>$row) {
		if ($key < 1) continue;
		if ($row->getElementsByTagName('td')->item(0)->textContent == file_get_contents('viewed.txt')) break;
		$text = $row->getElementsByTagName('td')->item(0)->textContent;
		$unreadMessagesAsObject[$messagesParsed]['messageDate'] = substr($text, -60, -58) . ' ' . substr($text, -57, -54);

		// Username
		$text = $row->getElementsByTagName('td')->item(1)->textContent;
		$unreadMessagesAsObject[$messagesParsed]['messageAuthor'] = substr($text, 0, -112);

		// Heading
		$text = $row->getElementsByTagName('td')->item(2);
		$href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		$href = 'msg.php?board=' . substr($href, -4) . '&message=' . substr($href, 15, -11);
		// Real: /Board/content/26433?board=1048
		$unreadMessagesAsObject[$messagesParsed]['url'] = $href;
		$unreadMessagesAsObject[$messagesParsed]['messageTitle'] = $text->textContent;

		$messagesParsed++; //Also can be used to show number of messages
	}
	$a = 0; $readMessagesAsObject = []; $messagesParsed = 0;
	foreach ($dom->getElementById('tab_table1')->getElementsByTagName('tr') as $key=>$row) {
		if ($key === 0) continue;
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
	<title>Student Board | iEMB 2.0</title>
	<meta name='viewport' content='width=device-width, initial-scale=1.0' />

	<style>
		html {
			font: 1em/1.5rem -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
			height: 100%;
		}
		body {
			margin: 0;
			cursor: default;
			height: 100%;
			/*overflow: hidden;*/
			/*Uncomment when back button implemented*/
		}
		header {
			background-color: #f44336;
			color: #fff;
			line-height: 3rem;
			font-size: 1.5rem;
			padding: 0 1.5rem;
			position: fixed;
			z-index: 2;
			width: calc(100% - 48px);
		}
		header #right {float: right;}
		header #header-logout {
			color: #fff;
			text-decoration: none;
			margin-left: 1rem;
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
			z-index: 299;
			transition: opacity 350ms ease-in-out;
		}
		#navOpen:checked ~ #navOverlay {
			opacity: .5;
			pointer-events: auto;
		}
		#navOpen {display: none;}
		label {cursor: pointer;}
		td {border-top: 1px solid #ccc;}
		#message-container {
			width: 40%;
			float: left;
			border-right: 1px solid #000;
			position: absolute;
			top: 85px;
			height: 100%;
			overflow-y: scroll;
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
			top: 48px;
			right: 0;
			height: calc(100% + 34px);
			overflow-y: scroll;
		}
		#search {
			width: calc(40% - 2rem);
			float: left;
			border: 0;
			border-bottom: 1px solid #000;
			border-right: 1px solid #000;
			position: fixed;
			top: 48px;
			font-size: 1rem;
			outline: none;
			padding: .5rem 1rem;
			/*margin-bottom: .1rem;*/
		}
		#slider {
			width: 100%;
			position: relative;
			height: calc(100% - 83px);
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
	</style>
	<script>
		function updateSearchResults() {
				if (document.getElementById('search').value === '') {
					document.getElementById('message-container').innerHTML = '';
					refreshView();
				}
				else {
					document.getElementById('message-container').innerHTML = '';
					searchMessages(document.getElementById('search').value.toLowerCase());
					refreshView();
				}
		}

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
						messageHeading.innerHTML = unread[messagesParsed].messageTitle.trim();
						messageHeading.className = 'message-header';
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
				if ((read[messagesParsed].messageDate.trim().includes(searchString) == true) || (read[messagesParsed].messageAuthor.trim().includes(searchString) == true) || (read[messagesParsed].messageTitle.trim().includes(searchString) == true)) {
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
		function refreshView() {
			messages = document.getElementsByClassName('message');
			selectMessage = messages[0].id;
			for (i = 0; i < messages.length; i++) messages[i].addEventListener('click', getMessage, false);
			mobileRefresh();
		}
		function mobileRefresh() {
			if (window.innerWidth < 800) for (i = 0; i < messages.length; i++) messages[i].addEventListener('click', transformSlider, false);
			else for (i = 0; i < messages.length; i++) messages[i].removeEventListener('click', transformSlider, false);
		}
		function transformSlider() {document.getElementById('slider').style.transform = 'translateX(-50%)'};
		function readAll() {
			var event = document.createEvent('Events');
			event.initEvent('click', true, false);
			for (i = 0; i < messagesUnread; i++) {
				messages[messagesUnread].dispatchEvent(event);
			}
		}
		document.addEventListener('DOMContentLoaded', function(){
			parseMessages();
			refreshView();
			document.getElementById('header-read').addEventListener('click', readAll, false);
			window.addEventListener('resize', mobileRefresh);
		});
	</script>
</head>

<body>
	<header>
		<label for='navOpen'>&#x2630;</label> iEMB 2.0
		<div id='right'>
			<span id='header-name'>Welcome, <?php echo $username; ?></span>
			<span id='header-read'>Read all</span>
			<a href='logout.php' id='header-logout'>Log Out</a>
		</div>
	</header>
	<input type='checkbox' id='navOpen'>
	<nav>
		<img src='logo.svg'>
		<a href='view.php?board=1048'>Student</a>
		<a href='view.php?board=1050'>Lost &amp; Found</a>
		<a href='view.php?board=1049'>PSB</a>
		<a href='view.php?board=1039'>Service</a>
		<a href='view.php?board=1053'>Let's Serve!</a>
	</nav>
	<label for='navOpen' id='navOverlay'></label>

	<!--SEARCH-->
	<div id='slider'>
		<input onkeyup='updateSearchResults();' id='search' placeholder='Search...' />
		<!--MESSAGES-->
		<div id='message-container'></div>
		<div id='message-view'></div>
	</div>
	<?php
		echo '<div id=\'unreadMessagesJSON\' style=\'display: none;\'>' . json_encode($unreadMessagesAsObject) . '</div>';
		echo '<div id=\'readMessagesJSON\' style=\'display: none;\'>' . json_encode($readMessagesAsObject) . '</div>';
	?>
</body>
</html>