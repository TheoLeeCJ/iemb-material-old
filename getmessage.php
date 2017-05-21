<?php
	/*
	  This file is responsible for retrieiving messages from the actual iEMB and sending the response to view.php when user wants to view a message.
	*/

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
	curl_setopt($ch, CURLOPT_URL, 'https://iemb.hci.edu.sg/Board/content/' . $_GET['message'] . '?board=' . $_GET['board']);
	// https://iemb.hci.edu.sg/Board/content/27633?board=1048
	$content = curl_exec($ch);
	curl_close($ch);
	$dom = new DOMDocument();
	@$dom->loadHTML('<!DOCTYPE html>' . $content);
	$finder = new DOMXpath($dom);
	$spaner = $finder->query('//*[contains(@class, \'read_message_body_div\')]');
	echo '<div id=\'view-header\'><strong>' . substr($spaner->item(0)->getElementsByTagName('div')->item(6)->textContent, 7) . '</strong><br>';
	echo substr($spaner->item(0)->getElementsByTagName('div')->item(7)->textContent, 7) . '<br>';
	echo substr($spaner->item(0)->getElementsByTagName('div')->item(9)->textContent, 7) . '<br></div>';
	if ($imgs = $spaner->item(0)->getElementsByTagName('div')->item(14)->getElementsByTagName('img')) {
		foreach ($imgs as $img) {
			if (substr($img->getAttribute('src'), 0, 11) != 'data:image/') {
				$file = substr($img->getAttribute('src'), 0, 3) != 'http' ? file_get_contents('https://iemb.hci.edu.sg/Board/content/' . $img->getAttribute('src')) : file_get_contents($img->getAttribute('src'));
				$f = finfo_open();
				$imgtype = finfo_buffer($f, $file, FILEINFO_MIME_TYPE);
				$base = base64_encode($file);
				$img->setAttribute('src', 'data:' . $imgtype . ';base64,' . $base);
			}
		}
	}
	$text = $dom->saveXML($spaner->item(0)->getElementsByTagName('div')->item(14));
	echo '<div id=\'view-body\'>';
	echo $text;
	if ($original = $dom->getElementById('replyForm')) {
		$form = new DOMDocument;
		// Form
		$formElem = $form->createElement('form');
		$formElem->setAttribute('name', 'replyForm');
		$formElem->setAttribute('action', 'respond.php');
		$formElem->setAttribute('method', 'post');
		$form->appendChild($formElem);
		$formElem = $form->getElementsByTagName('form')->item(0);
		// Hidden Element 1 (ID)
		$input = $form->createElement('input');
		$input->setAttribute('type', 'hidden');
		$input->setAttribute('name', 'boardid');
		$input->setAttribute('value', $_GET['board']);
		$formElem->appendChild($input);
		// Hidden Element 2 (Message ID)
		$input = $form->createElement('input');
		$input->setAttribute('type', 'hidden');
		$input->setAttribute('name', 'topic');
		$input->setAttribute('value', $_GET['message']);
		$formElem->appendChild($input);
		// Hidden Element 3 (replyto) --  wtf?
		$input = $form->createElement('input');
		$input->setAttribute('type', 'hidden');
		$input->setAttribute('name', 'replyto');
		$input->setAttribute('value', $dom->getElementById('replyto')->getAttribute('value'));
		$formElem->appendChild($input);
		$input = $form->createElement('textarea');
		$input->setAttribute('type', 'radio');
		$input->setAttribute('name', 'replyContent');
		$input->setAttribute('class', 'text');
		$input->setAttribute('placeholder', 'Comment');
		$text = new DOMText($dom->getElementById('editArea')->textContent);
		$input->appendChild($text);
		$formElem->appendChild($input);
		$after = $form->createElement('div');
		$after->setAttribute('class', 'text-after');
		$text = new DOMText(' ');
		$after->appendChild($text);
		$formElem->appendChild($after);
		$inputDiv = $form->createElement('div');
		foreach (range('A', 'E') as $key => $letter) {
			$input = $form->createElement('input');
			$input->setAttribute('id', 'radio' . $letter);
			$input->setAttribute('type', 'radio');
			$input->setAttribute('name', 'UserRating');
			$input->setAttribute('value', $letter);
			$input->setAttribute('class', 'radio');
			if ($dom->getElementsByTagName('input')->item($key + 3)->hasAttribute('checked')) $input->setAttribute('checked', 'true');
			$inputDiv->appendChild($input);
			$inputLabel = $form->createElement('label');
			$inputLabel->setAttribute('for', 'radio' . $letter);
			$inputLabelDiv = $form->createElement('div');
			$inputLabelDiv->setAttribute('class', 'radioStyle');
			$text = new DOMText(' ');
			$inputLabelDiv->appendChild($text);
			$inputLabel->appendChild($inputLabelDiv);
			$text = new DOMText(' ' . $letter);
			$inputLabel->appendChild($text);
			$inputDiv->appendChild($inputLabel);
		}
		$formElem->appendChild($inputDiv);
		$submit = $form->createElement('input');
		$submit->setAttribute('id', 'button');
		$submit->setAttribute('type', 'submit');
		$submit->setAttribute('value', 'Respond');
		$submit->setAttribute('name', 'PostMessage');
		$formElem->appendChild($submit);
		$input = $form->createElement('input');
		$input->setAttribute('type', 'hidden');
		$input->setAttribute('name', 'Cancel');
		$input->setAttribute('value', 'Cancel');
		$formElem->appendChild($input);
		echo $form->saveXML();
	}

	if ($original = $dom->getElementById('attaches')) {
		echo '<div id=\'attaches\'>';
		echo '<strong>Attachments</strong><br>';
		$attaches = explode(';', explode(PHP_EOL, $content)[222]);
		for ($i = 0; $i < count($attaches) - 1; $i++) {
			$attach = $attaches[$i];
			$begin = strpos($attach, 'addConfirmedChild') + 30;
			$end = strpos($attach, "','", $begin) - $begin;
			$attachName = substr($attach, $begin, $end);
			echo '<a href=\'getAttach.php?name=' . rawurlencode($attachName) . '&message=' . $_GET['message'] . '&board=' . $_GET['board'] . '\'>' . $attachName . '</a><br>';
		}
		echo '</div>';
	}
	echo '</div>';
?>