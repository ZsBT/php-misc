<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Postgres (needs php5-pgsql)

*/

require_once 'zsdb.class.php';

class ZSDB_PG extends __ZSDB {
  var $SCHEMA=0;		/* search path, usable with postgres */
  private $cstr;
  function ZSDB_PG($DBHOST, $DBPORT, $DBNAME, $DBUSER, $DBPASS='') {
    $cstr  = 'host='.$DBHOST ;
    if ($DBPORT) $cstr .= ' port='.$DBPORT ;
    $cstr .= ' dbname='.$DBNAME ;
    $cstr .= ' user='.$DBUSER ;
    if ($DBPASS) $cstr .= ' password='.$DBPASS;
    $this->cstr=$cstr;
    $this->connect();
  }
  function connect(){return $this->CONN = pg_pconnect($this->cstr) ;}
  function exec($Q,$debugcall=0) {
    if($this->SCHEMA)$Q="set search_path to {$this->SCHEMA};$Q ";
    if(!$this->CONN)$this->connect();
    $ret = pg_query($this->CONN, $Q) ;
    if (($this->SHOWERROR && !$ret ) || ($this->DEBUGMODE) ) {echo ($errS = sprintf(ZSDB_ERRF, $Q, pg_last_error() ));}
    return $ret;
  }
  function query($Q)	{ return $this->exec($Q) ; }
  function close()	{ return pg_close($this->CONN) ; }
  function fa($R)	{ return pg_fetch_array($R) ; }
  function faa($R)	{ return pg_fetch_array($R,NULL,PGSQL_ASSOC); }
  function fan($R)	{ return pg_fetch_array($R,NULL,PGSQL_NUM); }
  function fo($R)	{ return pg_fetch_object($R) ; }
  function nr($R)	{ return pg_numrows($R) ; }
  function nf($R)	{ return pg_num_fields($R); }
  function fn($R,$i)	{ return pg_field_name($R,$i); }
  function free($R)	{ return pg_free_result($R); }
  function settabletextcols($tabl) {
    $tabl=strtolower($tabl);
    $R=$this->exec("select distinct attname as oszlop from pg_attribute where 
      attrelid in (select distinct typrelid from pg_type left join pg_description on typrelid=objoid where typname in ('$tabl') ) 
      and atttypid in (16,25,869,1042,1043,1082,1114)") ;
    $ret = array();
    while ($fa=$this->fa($R)) $ret[] = $fa[0];
    return ($this->TXF = $ret);
  }
  function settabledatecols($tabl) {
    $tabl=strtolower($tabl);
    $R=$this->exec("select distinct attname as oszlop from pg_attribute where 
      attrelid=(select distinct typrelid from pg_type left join pg_description on typrelid=objoid where typname in ('$tabl')) 
      and atttypid=1082") ;
    $ret = array();
    while ($fa=$this->fa($R)) $ret[] = $fa[0];
    return ($this->TXF = $ret);
  }
  function settxall() {
    $this->TXF='';
    $a=$this->QA("select tablename from pg_tables where schemaname='public'");
    foreach ($a as $t) $this->TXF = array_merge($this->TXF, $this->settabletextcols($t) );
  }
}
