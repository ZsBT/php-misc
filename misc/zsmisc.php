<?php

function detectUTF8($string) {
    return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )+%xs', 
    $string);
}

/* converts a big integer to a short string (like Google URL shortener) */
function numtoshortstring($N,$set="23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"){
  $n=strlen($set);$s='';
  while($N>0){$d=($N % $n);$s=''.$set[$d].$s;$N=round(($N-$d)/$n);}
  return $s;
}
#echo numtoshortstring( microtime(1)*10000 );


function uuid($prefix = '') {
  $chars = md5(uniqid(mt_rand(), true));
  $uuid  = substr($chars,0,8) . '-';  
  $uuid .= substr($chars,8,4) . '-';
  $uuid .= substr($chars,12,4) . '-';
  $uuid .= substr($chars,16,4) . '-';
  $uuid .= substr($chars,20,12);
  return $prefix . strtoupper($uuid);
}

function humanTimeElapsed($seconds, $words=array('seconds','minutes','hours','days','weeks') ){
  if($seconds<0)$seconds=0-$seconds;
  if($seconds>59){$minutes=floor($seconds/60);$seconds=$seconds % 60;}
  if($minutes>59){$hours=floor($minutes/60);$minutes=$minutes % 60;}
  if($hours>23){$days=floor($hours/24);$hours=$hours % 24;}
  if($days>6){$weeks=floor($days/7);$days=$days % 7;}
  
  $ret='';
  if($weeks)$ret.="$weeks {$words[4]} ";
  if($days)$ret.="$days {$words[3]} ";
  if($hours)$ret.="$hours {$words[2]} ";
  if($minutes)$ret.="$minutes {$words[1]} ";
  if($seconds)$ret.="$seconds {$words[0]} ";
  return trim($ret);
}

