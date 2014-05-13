<?php /* 

  Simple crypt/decrypt function with salt.
  Cracking is too easy to use in production environment!
  With production data, you should use mcrypt.
  
  */

function encrypt($str, $salt="WriteSomeThingUniqueHere") {
	$salt = md5($salt);
	$out = '';
	$str = gzdeflate($str,9);
	for ($i = 0; $i<strlen($str); $i++) {
		$kc = substr($salt, ($i%strlen($salt)) - 1, 1);
		$out .= chr(ord($str{$i})+ord($kc));
	}
	$out = base64_encode($out);
	$out = str_replace(array('=', '/'), array('', '-'), $out);
	return $out;
}


function decrypt($str, $salt="WriteSomeThingUniqueHere") {
	$salt = md5($salt);
	$out = '';
	$str = str_replace('-', '/', $str);
	$str = base64_decode($str);
	for ($i = 0; $i<strlen($str); $i++) {
		$kc = substr($salt, ($i%strlen($salt)) - 1, 1);
		$out .= chr(ord($str{$i})-ord($kc));
	}
	return gzinflate($out);
}

