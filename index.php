<!DOCTYPE html>
<html lang='en-SG'>
<head>
	<title>iEMB</title>
	<meta name='viewport' content='width=device-width,initial-scale=1'>
	<link rel='stylesheet' type='text/css' href='styling.css'>

	<style>
		#margin-top, #margin-bottom {
			height: calc(calc(100% - 31rem) / 2);
			min-height: 20px;
		}

		.text {
			width: 22.5rem; height: 1.75rem;
			border-top: none;
			border-left: none;
			border-right: none;
		}
		
		.text-after {width: 22.9rem;}

		#links {
			font-size: .75rem;
			text-transform: uppercase;
			margin-top: .5rem;
		}
		#links a {
			text-decoration: none;
			color: #666;
			margin-right: .5rem;
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
			overflow: hidden;
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

		#clearfix { clear: both; }

		@media screen and (max-width: 30rem) {
			#margin-top, #margin-bottom {display: none;}
			
			#form-container {
				transform: none;
				width: calc(100% - 2rem);
				padding: 1rem;
				min-height: calc(100% - 2rem);
				height: calc(100% - 2rem);
				margin-bottom: 0;
				overflow: scroll;
			}

			.text {width: 100%;}
			.text-after {width: calc(100% + 6px);}
		}

		#gradient {height: 2rem;}

		#SkinSelect, #SkinSelect-Box {
			z-index: 10;
			background-color: white;
			cursor: pointer;
			border: 0;
			padding: 7.5px; padding-left: 20px; padding-right: 20px;
			box-shadow: 0 2px 2px 0 rgba(0, 0, 0, .14), 0 3px 1px -2px rgba(0, 0, 0, .12), 0 1px 5px 0 rgba(0, 0, 0, .2);
		}

		#SkinSelect {
            width: calc(100% - 130px);
            display: inline-block;
			margin-top: 20px;
			cursor: default;
		}
	</style>

    <script>
        if ("<?php echo $_GET['skinchoice']; ?>" !== "manual") {
	        if ((navigator.userAgent.includes("iPhone") == true) || (navigator.userAgent.includes("Mac") == true)) {
		        if (window.location.href !== "https://iemb.cf") { window.location.href = "https://iemb.cf"; }
		        else { /*Already on iOS port, don't redirect*/ }
	        }
	        else {
		        if (window.location.href.includes("gq") == false) { window.location.href = "https://iemb.gq"; }
		        else { /*Already on Material port, don't redirect*/ }
	        }
        }

        function PickNewSkin() {
            var skin = document.getElementById("SkinSelect-Box").options[document.getElementById("SkinSelect-Box").selectedIndex].value;

            if (skin == "iEMB 2.0") { window.location.href = "https://iemb.gq?skinchoice=manual"; }
            else if (skin == "iOS") { window.location.href = "https://iemb.cf?skinchoice=manual"; }
            else if (skin == "Material") { window.location.href = "https://material.iemb.gq"; }
        }
    </script>
</head>

<body>
	<!--Background-->
	<div id='background-container'><img src='background.svg' id='background'></div>
	<div id='margin-top'></div>

	<!--Sign In form-->
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
            
		    <div id="SkinSelect">
			    Appearance: 
			    <select id="SkinSelect-Box" onchange="PickNewSkin();" style="box-shadow: none;">
				    <option selected>iEMB 2.0</option>
				    <option disabled="disabled">iEMB</option>
				    <option>iOS</option>
				    <option>Material</option>
			    </select>
		    </div>

			<input type='submit' class='button' value='Log In'>
			<div id='clearfix'></div>
			<?php
				// session_set_cookie_params(300, '/', '.locahost', true, true);
				// session_set_cookie_params(300, '/', '.iemb.ml', true, true);
				// above for uploaded file
				session_start();
				$_SESSION['canary'] = time();
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
			<div id='links'>
				<a href='about.html'>About Us</a>
				<a href='privacy.html'>Privacy Policy</a>
			</div>
		</form>
	</div>

	<div id='margin-bottom'>
	</div>
</body>
</html>