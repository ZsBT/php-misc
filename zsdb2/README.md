zsdb2 (depricated, keeping for compatibility reasons. please use zsdb3
======================================================================

PHP libs to connect to various databases easily
-----------------------------------------------

Look into zsdb.class.php to check the methods. This is the base class that all others extend. 
A simple example for a PostgreSQL database:

	<?php

	require_once 'zsdb.pg.class.php';
	$DB = new ZSDB_PG("localhost", 5432, "mydatabase", "username", "password");

	$firstnames = $DB->QA("select distinct firstname from partners");	// will return an array
	print_R($firstnames);

	$DB->TXF = array('firstname', 'lastname', 'initials');	// set text-field names to be parenthized

	$DB->iou("partners", array(		// inserts or updates a record
		'firstname'	=>	'John',
		'lastname'	=>	'Smith',
		'initials'	=>	'Mr',
		'birthyear'	=>	1972,
		'gender'	=>	1,
		)
		, "id=32"
	);

	$obj = $DB->QFO("select * from partners where id=32");		// returns an object
	echo "His firstname is {$obj->firstname}.";

	$arr = $DB->QFA("select * from partners where id=32");		// returns an array
	print_R($arr);

