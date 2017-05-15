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
	<title>Student Board | iEMB 2.0</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

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

  <!--JSON parsing + search function-->
  <script>
    function UpdateSearchResults() {
      SearchMessages(document.getElementById("SearchRequest").value);
    }

    function SearchMessages(searchString) {
      var resultsTable = document.getElementById("SearchResults"); //CHANGE TO ID OF TABLE FOR SEARCH RESULTS
      resultsTable.innerHTML = "";

      var unread = JSON.parse(document.getElementById("UnreadMessagesJSON").innerHTML);
      var read = JSON.parse(document.getElementById("ReadMessagesJSON").innerHTML);

      var messagesParsed = 0;

      while (messagesParsed < unread.length) {
        if ((unread[messagesParsed].messageDate.trim().includes(searchString) == true) || (unread[messagesParsed].messageAuthor.trim().includes(searchString) == true) || (unread[messagesParsed].messageTitle.trim().includes(searchString) == true)) {
          var messageRow = resultsTable.insertRow(-1);

          var messageDate = messageRow.insertCell(-1); messageDate.innerHTML = unread[messagesParsed].messageDate.trim();
          var messageAuthor = messageRow.insertCell(-1); messageAuthor.innerHTML = unread[messagesParsed].messageAuthor.trim();
          
          var messageHeading = messageRow.insertCell(-1);
          messageHeading.innerHTML = "<a href='" + unread[messagesParsed].url.trim() + "'>" + unread[messagesParsed].messageTitle.trim() + "</a>";
        }

        messagesParsed++;
      }

      messagesParsed = 0;

      while (messagesParsed < read.length) {
        if ((read[messagesParsed].messageDate.trim().includes(searchString) == true) || (read[messagesParsed].messageAuthor.trim().includes(searchString) == true) || (read[messagesParsed].messageTitle.trim().includes(searchString) == true)) {
          var messageRow = resultsTable.insertRow(-1);

          var messageDate = messageRow.insertCell(-1); messageDate.innerHTML = read[messagesParsed].messageDate.trim();
          var messageAuthor = messageRow.insertCell(-1); messageAuthor.innerHTML = read[messagesParsed].messageAuthor.trim();
          
          var messageHeading = messageRow.insertCell(-1);
          messageHeading.innerHTML = "<a href='" + read[messagesParsed].url.trim() + "'>" + read[messagesParsed].messageTitle.trim() + "</a>";
        }

        messagesParsed++;
      }
    }

    function ParseMessages() {
      //UNREAD MESSAGES
      var unreadMessagesTable = document.getElementById("UnreadMessagesTable"); //CHANGE TO ID OF TABLE FOR UNREADS

      var messagesToGet = JSON.parse(document.getElementById("UnreadMessagesJSON").innerHTML);
      var messagesParsed = 0;

      while (messagesParsed < messagesToGet.length) {
        var messageRow = unreadMessagesTable.insertRow(-1);

        var messageDate = messageRow.insertCell(-1); messageDate.innerHTML = messagesToGet[messagesParsed].messageDate.trim();
        var messageAuthor = messageRow.insertCell(-1); messageAuthor.innerHTML = messagesToGet[messagesParsed].messageAuthor.trim();
        
        var messageHeading = messageRow.insertCell(-1);
        messageHeading.innerHTML = "<a href='" + messagesToGet[messagesParsed].url.trim() + "'>" + messagesToGet[messagesParsed].messageTitle.trim() + "</a>";

        messagesParsed++;
      }

      //READ MESSAGES
      var readMessagesTable = document.getElementById("ReadMessagesTable"); //CHANGE TO ID OF TABLE FOR READS

      messagesToGet = JSON.parse(document.getElementById("ReadMessagesJSON").innerHTML);
      messagesParsed = 0;

      while (messagesParsed < messagesToGet.length) {
        var messageRow = readMessagesTable.insertRow(-1);

        var messageDate = messageRow.insertCell(-1); messageDate.innerHTML = messagesToGet[messagesParsed].messageDate.trim();
        var messageAuthor = messageRow.insertCell(-1); messageAuthor.innerHTML = messagesToGet[messagesParsed].messageAuthor.trim();
        
        var messageHeading = messageRow.insertCell(-1);
        messageHeading.innerHTML = "<a href='" + messagesToGet[messagesParsed].url.trim() + "'>" + messagesToGet[messagesParsed].messageTitle.trim() + "</a>";

        messagesParsed++;
      }
    }
  </script>
</head>

<body>
  <header>
    <label for='sideOpen'>&#x2630;</label> iEMB 2.0 <!--Use entity for compatibility - originally was menu character-->
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

  <!--SEARCH-->
  <div id="Search" style="background-color: black; color: white; width: 100%; height: 200px; bottom: 0; position: fixed;">
    <input size="100" onkeyup="UpdateSearchResults();" id="SearchRequest" placeholder="Search by message author, title or date..." />
    <table id="SearchResults"></table>
  </div>

  <!--MESSAGES-->
  <strong>Unread Messages</strong>
  <table id="UnreadMessagesTable">
    <tr>
      <th>Date</th>
      <th>Author</th>
      <th>Subject</th>
    </tr>
  </table>

  <strong>Read Messages</strong>
  <table id="ReadMessagesTable">
    <tr>
      <th>Date</th>
      <th>Author</th>
      <th>Subject</th>
    </tr>
  </table>

  <?php
    $a = 0; $unreadMessagesAsObject = []; $messagesParsed = 0;
    foreach ($dom->getElementById('tab_table')->getElementsByTagName('tr') as $key=>$row) {
      if ($key === 0) continue;
      // Date
      $text = $row->getElementsByTagName('td')->item(0)->textContent;
      $unreadMessagesAsObject[$messagesParsed]["messageDate"] = substr($text, -60, -58) . ' ' . substr($text, -57, -54);

      // Username
      $text = $row->getElementsByTagName('td')->item(1)->textContent;
      $unreadMessagesAsObject[$messagesParsed]["messageAuthor"] = substr($text, 0, -112);

      // Heading
      $text = $row->getElementsByTagName('td')->item(2);
      $href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
      $href = 'msg.php?board=' . substr($href, -4) . '&message=' . substr($href, 15, -11);
      // Real: /Board/content/26433?board=1048
      $unreadMessagesAsObject[$messagesParsed]["url"] = $href;
      $unreadMessagesAsObject[$messagesParsed]["messageTitle"] = $text->textContent;

      $messagesParsed++; //Also can be used to show number of messages
    }

    echo "<div id='UnreadMessagesJSON' style='display: none;'>" . json_encode($unreadMessagesAsObject) . "</div>";
  ?>

  <?php
    $a = 0; $readMessagesAsObject = []; $messagesParsed = 0;
    foreach ($dom->getElementById('tab_table1')->getElementsByTagName('tr') as $key=>$row) {
      if ($key === 0) continue;
      // Date
      $text = $row->getElementsByTagName('td')->item(0)->textContent;
      $readMessagesAsObject[$messagesParsed]["messageDate"] = substr($text, 0, 2) . ' ' . substr($text, 3, 3);

      // Username
      $text = $row->getElementsByTagName('td')->item(1)->textContent;
      $readMessagesAsObject[$messagesParsed]["messageAuthor"] = substr($text, 0, -112);

      // Heading
      $text = $row->getElementsByTagName('td')->item(2);
		  $href = $text->getElementsByTagName('a')->item(0)->getAttribute('href');
		  $href = 'msg.php?board=' . substr($href, -4) . '&message=' . substr($href, 15, -11);
      // Real: /Board/content/26433?board=1048
      $readMessagesAsObject[$messagesParsed]["url"] = $href;
      $readMessagesAsObject[$messagesParsed]["messageTitle"] = $text->textContent;

      $messagesParsed++;
    }

    echo "<div id='ReadMessagesJSON' style='display: none;'>" . json_encode($readMessagesAsObject) . "</div>";
  ?>

  <script>
    ParseMessages();
  </script>
</body>
</html>