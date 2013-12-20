<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Postgres (needs php5-pgsql)

*/

class zsdb3_psql {
  var $SCHEMA=0;		/* search path, usable with postgres */
  private $cstr;
  
  function __construct($DBHOST, $DBPORT, $DBNAME, $DBUSER, $DBPASS='') {
    $cstr  = 'host='.$DBHOST ;
    if ($DBPORT) $cstr .= ' port='.$DBPORT ;
    $cstr .= ' dbname='.$DBNAME ;
    $cstr .= ' user='.$DBUSER ;
    if ($DBPASS) $cstr .= ' password='.$DBPASS;
    $this->cstr=$cstr;
    $this->connect();
  }
  function set_encoding($cs){
    pg_set_client_encoding($this->CONN, $cs);
  }
  
  function connect(){return $this->CONN = pg_pconnect($this->cstr) ;}
  function exec($Q,$debugcall=0) {
    if($this->SCHEMA)$Q="set search_path to {$this->SCHEMA};$Q ";
    if(!$this->CONN)$this->connect();
    $ret = pg_query($this->CONN, $Q) ;
    if (($this->SHOWERROR && !$ret ) || ($this->DEBUGMODE) ) {echo ($errS = sprintf($this->ERRF, $Q, pg_last_error() ));}
    return $ret;
  }
  function query($Q)	{ return $this->query($this->CONN,$Q) ; }
  function close()	{ return pg_close($this->CONN) ; }
  function fa($R)	{ return pg_fetch_array($R) ; }
  function faa($R)	{ return pg_fetch_array($R,NULL,PGSQL_ASSOC); }
  function fan($R)	{ return pg_fetch_array($R,NULL,PGSQL_NUM); }
  function fo($R)	{ return pg_fetch_object($R) ; }
  function nr($R)	{ return pg_num_rows($R) ; }
  function nf($R)	{ return pg_num_fields($R); }
  function fn($R,$i)	{ return pg_field_name($R,$i); }
  function free($R)	{ return pg_free_result($R); }
  
  function lasterror(){ return (object)array("msg"=>pg_last_error()); }
  
  function insert($table, $datarr) {
    if(!$table)return false;
    if(!$datarr)return false ;
    return pg_insert($this->CONN, $table, $datarr) ? true:false ;
  }

  function update($table, $datarr, $conda) {
    return pg_update($this->CONN, $table, $datarr, $conda) ? true:false ;
  }
  
  function delete($table, $conda){
    return pg_delete($this->CONN, $table, $conda);
  }
  
}
