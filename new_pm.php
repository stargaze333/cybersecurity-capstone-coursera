<?php
include('config.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="assets/style.css" rel="stylesheet" title="Style" />
	</head>
	<body>

<?php
//We check if the user is logged in
if (isset($_SESSION['username'])) {
	$form     = true;
	$otitle   = '';
	$orecip   = '';
	$omessage = '';
	//We check if the form has been sent
	if (isset($_POST['title'], $_POST['recip'], $_POST['message'])) {
		$otitle   = $_POST['title'];
		$orecip   = $_POST['recip'];
		$omessage = $_POST['message'];
		//We remove slashes depending on the configuration
		if (get_magic_quotes_gpc()) {
			$otitle   = stripslashes($otitle);
			$orecip   = stripslashes($orecip);
			$omessage = stripslashes($omessage);
		}
		//We check if all the fields are filled
		if ($_POST['title'] != '' and $_POST['recip'] != '' and $_POST['message'] != '') {
			//We protect the variables
			$title   = mysqli_real_escape_string($link, $otitle);
			$recip   = mysqli_real_escape_string($link, $orecip);
			$message = mysqli_real_escape_string($link, nl2br(htmlentities($omessage, ENT_QUOTES, 'UTF-8')));
			//We check if the recipient exists
			$dn1 = mysqli_fetch_array(mysqli_query($link, 'select count(id) as recip, id as recipid from users where username="'.$recip.'"'));
			if ($dn1['recip'] == 1) {
				//We check if the recipient is not the actual user
				if ($dn1['recipid'] != $_SESSION['userid']) {
					//We encrypt then send the message
					$cipher = "aes-128-gcm";
					$ivlen  = openssl_cipher_iv_length($cipher);
					$iv     = openssl_random_pseudo_bytes($ivlen);
					$key    = getKey($_SESSION['userid'], $dn1['recipid']);
					$tag    = null;
					$method = openssl_get_cipher_methods();
					if (in_array($cipher, $method)) {
						$iv = openssl_random_pseudo_bytes($ivlen);
						$ciphertext_raw = openssl_encrypt($message, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv, $tag);
						$hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
						$ciphertext = base64_encode($iv.$hmac.$ciphertext_raw);    // store $cipher, $hmac and $iv for decryption later
						if (mysqli_query($link, 'insert into pm (title, sender, recipient, message, timestamp, tag) values ("'.$title.'", "'.$_SESSION['userid'].'", "'.$dn1['recipid'].'", "'.$ciphertext.'", "'.time().'", "'.$tag.'")')) {
?>
		<div class="message">The message has successfully been sent.<br />
		<br />
		<a href="mailbox.php" style="color:white">Mailbox</a></div>

<?php
							$form = false;
						}
						else $error = 'An error occurred while sending the message.';//Otherwise, we say that an error occured
					}
					else $error = 'Error while sending the message.';//Otherwise, we say the user cannot send a message to himself
				}
				else $error = 'You cannot send a message to yourself.';//Otherwise, we say the user cannot send a message to himself
			}
			else $error = 'The recipient does not exist.';//Otherwise, we say the recipient does not exists
		}
		else $error = 'Please fill in all of the fields.';//Otherwise, we say a field is empty
	}

	if ($form) {
		//We display a message if necessary
		if (isset($error)) echo '<div class="message">'.$error.'</div>';

		//We display the form
?>
		<div class="content">
			<form action="new_pm.php" method="post">

				<label for="recip">Recipient<span class="small"> (Username)</span></label><input  required=true type="text" value="<?php echo htmlentities($orecip, ENT_QUOTES, 'UTF-8'); ?>" id="recip" name="recip" /><br />
				<br/>
				<label for="title">Title</label><input required=true type="text" value="<?php echo htmlentities($otitle, ENT_QUOTES, 'UTF-8'); ?>" id="title" name="title" /><br />
				<br/>
				<label for="message">Message</label><textarea cols="40" rows="5" id="message" name="message"><?php echo htmlentities($omessage, ENT_QUOTES, 'UTF-8'); ?></textarea><br />
				<br/>
				<br/>
				<input type="submit" style="align:centre" value="Send" />
			</form>
		</div>
<?php
	}
}
else echo '<div class="message">You must be logged in to access this page.</div>';
?>
<br/>
		<div class="foot"><a href="index.php" style="color:white">Home</a></div>
	</body>
</html>
