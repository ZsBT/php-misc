<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Mysql (needs php5-mysql)

*/

require_once 'zsdb.class.php';

class ZSDB_MYSQL extends __ZSDB {
  function ZSDB_MYSQL($DBHOST, $DBPORT, $DBNAME, $DBUSER, $DBPASS='') {
    if ($DBPORT) $DBHOST .= ':' . $DBPORT ;
    if (!$this->CONN = mysql_connect($DBHOST, $DBUSER, $DBPASS)) die ('MySQL: db connect error: '.mysql_error()) ;
    if (!mysql_select_db($DBNAME, $this->CONN)) die ("MySQL: error selecting db $DBNAME: ".$DBNAME) ;
  }
  function exec($Q) { $ret = mysql_query($Q, $this->CONN); if ($this->DEBUGMODE && !$ret ) printf(ZSDB_ERRF, $Q, mysql_error() ); return $ret;}
  function query($Q)	{ return $this->exec($Q) ; }
  function close()	{ return mysql_close($this->CONN) ; }
  function fa($R)	{ return mysql_fetch_array($R) ; }
  function faa($R)	{ return mysql_fetch_array($R,MYSQL_ASSOC) ; }
  function fan($R)	{ return mysql_fetch_array($R,MYSQL_NUM) ; }
  function fo($R)	{ return mysql_fetch_object($R) ; }
  function nr($R)	{ return mysql_num_rows($R) ; }
  function nf($R)	{ return mysql_num_fields($R); }
  function fn($R,$i)	{ return mysql_field_name($R,$i); }
  function free($R)	{ return mysql_free_result($R); }
  function settabletextcols($tabl) { $this->TXF=''; $a = $this->QA("select distinct column_name from information_schema.columns where data_type='varchar'"); $this->TXF=$a; }
}

