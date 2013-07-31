<?php
#
#	for VT100 terminal!
#

function AnsiSTR( $str, $opta=array() ) {
  $E = "\x1b";
  
  $ca=array();
  if (!is_array($opta)) $opta = array($opta);
  foreach ($opta as $opt) switch($opt) {
    case "RSET": $ca[]="[0m";	break;	//	reset; clears all colors and styles (to white on black)
    case "b":	$ca[]="[1m";	break;	//	bold on (see below)
    case "i":	$ca[]="[3m";	break;	//	italics on
    case "u":	$ca[]="[4m";	break;	//	underline on
    case "I":	$ca[]="[7m";	break;	//	inverse on; reverses foreground & background colors
    case "S":	$ca[]="[9m";	break;	//	strikethrough on
    
    case "black": $ca[]="[30m";	break;	//	set foreground color to black
    case "red":	$ca[]="[31m";	break;	//	set foreground color to red
    case "green": $ca[]="[32m";	break;	//	set foreground color to green
    case "yellow":$ca[]="[33m";	break;	//	set foreground color to yellow
    case "blue": $ca[]="[34m";	break;	//	set foreground color to blue
    case "magenta":$ca[]="[35m";break;	//	set foreground color to magenta (purple)
    case "cyan": $ca[]="[36m";	break;	//	set foreground color to cyan
    case "white":$ca[]="[37m";	break;	//	set foreground color to white
    
    case "bgblack":	$ca[]="[40m";	break;	//	set background color to black
    case "bgred":	$ca[]="[41m";	break;	//	set background color to red
    case "bggreen":	$ca[]="[42m";	break;	//	set background color to green
    case "bgyellow":	$ca[]="[43m";	break;	//	set background color to yellow
    case "bgblue":	$ca[]="[44m";	break;	//	set background color to blue
    case "bgmagenta":	$ca[]="[45m";	break;	//	set background color to magenta (purple)
    case "bgcyan":	$ca[]="[46m";	break;	//	set background color to cyan
    case "bgwhite":	$ca[]="[47m";	break;	//	set background color to white
    
    case "EL2":     $ca[]="[2K"; break;         // Clear entire line
    case "CUB":     $ca[]="[1D"; break;		// cursor back
    case "CUB10":   $ca[]="[10D"; break;	// cb 10 chars
    case "CUB20":   $ca[]="[20D"; break;	// cb 20 chars
    case "CUB50":   $ca[]="[50D"; break;	// cb 50 chars
    case "CUB100":  $ca[]="[100D"; break;	// cb 100 chars

  }
  
  $prefix="";
  foreach ($ca as $c) $prefix.= ($E.$c);
  return $prefix.$str. $E."[0m";
}
