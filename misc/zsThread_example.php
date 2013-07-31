<?php

require_once 'zsThread.php';


function getstate($url){
  $MC=new Memcache;
  $MC->connect("localhost");
  $ra = ($MC->get("ra")||array());
  $ra[$url]=file_get_contents($url);
  $MC->set("RA",$ra);
}

$ta=array();

$t=new zsThread("getstate");
$t->start("http://blade4.emil/health/state");

$ta[]=$t;

// wait for all the threads to finish
while( !empty( $ta ) ) {
	foreach( $ta as $index => $thread ) {
		if( ! $thread->isAlive() ) {
			unset( $ta[$index] );
		}
	}
	// let the CPU do its work
	sleep( 1 );
	echo "varok... ";
}

$MC=new Memcache;
$MC->connect("localhost");

print_R($MC->get("RA"));

echo "\n\n";