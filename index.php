<!DOCTYPE html>
<html lang='en-SG'>
<head>
	<meta name='viewport' content='width=device-width,initial-scale=1'>
	<title>iEMB</title>
	<link rel='stylesheet' type='text/css' href='https://necolas.github.io/normalize.css/7.0.0/normalize.css'>
	<style>
		html {
			font: 1em/1.5rem -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
			height: 100%;
		}
		body {
			margin: 0;
			height: 100%;
		}
		.text {
			border-radius: 0;
			font-size: 1rem;
			display: block;
			width: 22.5rem;
			max-width: 100%;
			height: 1.75rem;
			margin: .25rem auto;
			padding: .2rem;
			border: none;
			border-bottom: 2px solid #ccc;
			outline: none;
		}
		.text-after {
			display: block;
			width: 22.9rem;
			height: 2px;
			margin: auto;
			margin-top: calc(-2px - .25rem);
			transition: transform ease-in-out 200ms;
			transform: scaleX(0);
			background-color: #9a0007;
		}
		.text:focus + .text-after {transform: scaleX(1);}
		#button {
			background-color: #9a0007;
			color: #fff;
			margin-top: 1.5rem;
			text-transform: uppercase;
			padding: .75rem;
			display: block;
			border: none;
			float: right;
			font-size: 1rem;
			border-radius: 3px;
			cursor: pointer;
			box-shadow: rgba(0, 0, 0, 0.137255) 0px 2px 2px 0px, rgba(0, 0, 0, 0.117647) 0px 3px 1px -2px, rgba(0, 0, 0, 0.2) 0px 1px 5px 0px;
		}
		#margin-top, #margin-bottom {
			height: calc(calc(100% - 31rem) / 2);
			min-height: 20px;
		}
		#form-container {
			overflow-x: hidden;
			width: 22.9rem;
			height: 25rem;
			margin: auto;
			padding: 3rem;
			padding-top: 0;
			background-color: #fff;
			box-shadow: 0 2px 2px 0 rgba(0, 0, 0, .14), 0 3px 1px -2px rgba(0, 0, 0, .12), 0 1px 5px 0 rgba(0, 0, 0, .2);
		}
		#background-container {
			position: fixed;
			z-index: -1;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
		}
		#background {
			width: 100%;
			height: 100%;
		}
		#logo-container {
			font-size: 40px;
			line-height: 40px;
			height: 40px;
			color: #d32f2f;
		}
		#logo {
			margin-right: 10px;
			margin-bottom: -4px;
		}
		#instructions {margin: 40px 0;}
		#instructions-signin {
			font-size: 1.5rem;
			line-height: 2rem;
		}
		.clearfix {clear: both;}
		@media screen and (max-width: 30rem) {
			#margin-top, #margin-bottom {display: none;}
			#form-container {
				transform: none;
				width: calc(100% - 2rem);
				padding: 1rem;
				min-height: calc(100% - 2rem);
				height: calc(100% - 2rem);
				margin-bottom: 0;
			}
			.text {width: 100%;}
			.text-after {width: calc(100% + 6px);}
		}
		#gradient {height: 2rem;}
	</style>
</head>
<body>
	<div id='background-container'><img src='background.svg' id='background'></div>
	<div id='margin-top'></div>
	<div id='form-container'>
		<div id='gradient'></div>
		<div id='logo-container'><img src='logo.svg' id='logo' height='40'>iEMB</div>
		<div id='instructions'>
			<div id='instructions-signin'>Sign In</div>
			<div id='instructions-account'>with your Hwa Chong Institution account</div>
		</div>
		<form action='index.php' method='post'>
			<input class='text' name='username' placeholder='Username (IC Number)'>
			<div class='text-after'></div>
			<input type='password' class='text' name='password' placeholder='Password'>
			<div class='text-after'></div>
			<input type='submit' id='button' value='Log In'>
			<div class='clearfix'></div>
			<?php
				session_start();
				if (isset($_SESSION['logged_in'])) {
					if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
						header('Location: view.php');
						exit('You are already logged in');
					}
				}
				if (isset($_GET['again'])) echo 'Please log in again<br>';
				if (isset($_GET['logged_out'])) echo 'You have been logged out<br>';
				if (isset($_POST['username']) && isset($_POST['password'])) {
					if ($_POST['username'] !== '' && $_POST['password'] !== '') {
						$conn = ftp_connect('www2.hci.edu.sg') or exit('Error: Failed to connect with server. Please try again later.');
						if (@ftp_login($conn, 'hci\\' . $_POST['username'], $_POST['password'])) {
							$_SESSION['username'] = $_POST['username'];
							$_SESSION['password'] = $_POST['password'];
							$_SESSION['logged_in'] = true;
							header('Location: view.php');
							exit('You are now logged in. Please <a href="view.php">click here</a> to view messages.');
						}
						else echo '<div style="margin-top:1.5rem">Username or Password incorrect. Please try again.</div>';
						ftp_close($conn);
					}
					else echo '<div style="margin-top:1.5rem">Please enter your credentials</div>';
				}
			?>
		</form>
	</div>
	<div id='margin-bottom'>
	</div>
</body>
</html>