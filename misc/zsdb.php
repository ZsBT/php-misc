<?php /*

  D E P R I C A T E D  -  U S E   Z S D B 3   I N S T E A D
  

	2010 (c) Zsombor's [S]imple [D]ata[B]ase	v2.6
		* Postgres (needs php5-pgsql)
		* Mysql (needs php5-mysql)
		* MSSQL	(needs php5-sybase)
		* Sqlite3 (needs php5-sqlite)
		* Oracle (needs pecl install oci8)

*/

define ('SDB_ERRF', "\n<pre>query:[%s]<br>return:[%s]</pre>\n");

class __SDB {
  var
    $CONN, /* connection variable */
    $DEBUGMODE = false, /* show sql commands */
    $SHOWERROR = true ; /* show error messages */
  var
    $TXF = array(), /* field values to be parenthised */
    $SCHEMA=0;		/* search path, usable with postgres */

  /* $datarr is an associated array( key => value)  - dont forget to use settabletextcols! */
  function update($table, $datarr, $cond=0) {
    if (!$cond) return 0;	/* comment out if you're brave */
    $Q = "update $table set " ;
    $setf = array() ;
    foreach ($datarr as $k => $v) { if (in_array($k,$this->TXF)) $v = "'$v'" ; $setf[sizeof($setf)] = $k."=$v" ; }
    $Q .= implode(', ', $setf) ;
    if ($cond) $Q .= ' where ' . $cond ;
    return $this->exec($Q);
  }
  function u($table,$datarr,$cond=0){return $this->update($table,$datarr,$cond);}
  function insert($t, $datarr) {
    if (!$datarr) return false ;
    $knew = $vnew = array();
    foreach ($datarr as $k => $v) { $knew[sizeof($knew)] = $k ; if (in_array($k,$this->TXF))$v="'".str_replace("'","\'",$v)."'";$vnew[sizeof($vnew)]=$v;}
    $Q = "insert into $t (" . implode(',',$knew) . ") values (" .implode(',',$vnew) . ")" ;
    return $this->exec($Q);
  }
  function i($t,$datarr){return $this->insert($t,$datarr);}
  function insertorupdate($t, $datarr, $cond) {	/* update if $cond has rows, else insert */
    $this->exec("delete from $t where $cond;");
    return $this->insert($t,$datarr);
  }
  function iou($t,$datarr,$cond=0) { return $this->insertorupdate($t,$datarr,$cond); }	/* synonym for insertorupdate */
  function settc($table) { return $this->settabletextcols($table); }	/* synonym for settabletextcols */
  function Q($query) { /* gives a simple value; use for a query that returns unique result */
    $R=$this->query($query);
    $fa=$this->fan($R);
    if($R)$this->free($R);else return false;
    return $fa[0];
  }
  function QFO($query) { /* object row */ $R=$this->query($query);$fo=$this->fo($R);if ($R) $this->free($R);return $fo;}
  function QFA($query) { /* array row */ $R=$this->query($query);$fa=$this->faa($R);if ($R) $this->free($R);return $fa;}
  function QA($query) { /* array of a single column */ $R=$this->query($query);$ret=array();while ($fa=$this->fan($R)) $ret[]=$fa[0];if ($R) $this->free($R);return $ret;}
  function QAA($query) { /* gives an array of associative array; use for small amount of rows */
    $R=$this->query($query);$ret=array();while ($fa=$this->faa($R)) $ret[]=$fa;if ($R) $this->free($R);
    return $ret;
  }
  function btrans(){ return $this->exec("begin transaction;"); }
  function commit(){ return $this->exec("commit");}
  function rollback(){ return $this->exec("rollback");}
  function fieldnames($R){
    $ret=array();
    $nf=$this->nf($R);
    for($i=0;$i<$nf;$i++)$ret[]=$this->fn($R,$i);
    return $ret;
  }
}

class SDB_PG extends __SDB {
  function SDB_PG($DBHOST, $DBPORT, $DBNAME, $DBUSER, $DBPASS='') {
    $cstr  = 'host='.$DBHOST ;
    if ($DBPORT) $cstr .= ' port='.$DBPORT ;
    $cstr .= ' dbname='.$DBNAME ;
    $cstr .= ' user='.$DBUSER ;
    if ($DBPASS) $cstr .= ' password='.$DBPASS;
    $this->CONN = pg_pconnect($cstr) ;
  }
  function exec($Q,$debugcall=0) {
    if ($this->SCHEMA) $Q="set search_path to {$this->SCHEMA};$Q ";
    $ret = pg_query($this->CONN, $Q) ;
    if (($this->SHOWERROR && !$ret ) || ($this->DEBUGMODE) ) {echo ($errS = sprintf(SDB_ERRF, $Q, pg_last_error() ));}
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

class SDB_MYSQL extends __SDB {
  function SDB_MYSQL($DBHOST, $DBPORT, $DBNAME, $DBUSER, $DBPASS='') {
    if ($DBPORT) $DBHOST .= ':' . $DBPORT ;
    if (!$this->CONN = mysql_connect($DBHOST, $DBUSER, $DBPASS)) die ('MySQL: db connect error: '.mysql_error()) ;
    if (!mysql_select_db($DBNAME, $this->CONN)) die ("MySQL: error selecting db $DBNAME: ".$DBNAME) ;
  }
  function exec($Q) { $ret = mysql_query($Q, $this->CONN); if ($this->DEBUGMODE && !$ret ) printf(SDB_ERRF, $Q, mysql_error() ); return $ret;}
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

class SDB_MSSQL extends __SDB{
  function SDB_MSSQL($DBHOST,$DBPORT=1433,$DBNAME=0,$DBUSER='',$DBPASS=''){
    if(!function_exists("mssql_pconnect"))die("php5-sybase not installed\n");
    $tries=3;
    if(!$DBPORT)$DBPORT=1433;
    while($tries-- && !$this->CONN=mssql_pconnect($DBHOST,$DBUSER,$DBPASS))sleep(2);
    if(!$this->CONN)die("MSSQL: db connect error\n");
    if($DBNAME)if(!mssql_select_db($DNAME))die("MSSQL: selecting db '$DBNAME' failed\n");
  }
  function query($Q)	{return mssql_query($Q,$this->CONN);}
  function exec($Q)	{return $this->query($Q);}
  function close()	{return $this->close($this->CONN);}
  function fa($R)	{return mssql_fetch_array($R);}
  function faa($R)	{return mssql_fetch_array($R,MSSQL_ASSOC);}
  function fan($R)	{return mssql_fetch_array($R,MSSQL_NUM);}
  function fo($R)	{return mssql_fetch_object($R);}
  function nr($R)	{return mssql_num_rows($R);}
  function nf($R)	{return mssql_num_fields($R);}
  function fn($R,$i)	{return mssql_field_name($R,$i);}
  function free($R)	{return mssql_free_result($R);}
  function settabletextcols($T){
    $ta = array();
    $i=0;
    $R=$this->query("select top 0 * from $T ");  
    while($fn=mssql_field_name($R)){
      switch($ft=mssql_field_type($R)){
        case "char":
        case "datetime":$ta[]=$fn;break;
      }
#      echo "$fn=$ft<br>";
      $i++;
    }
    return ($this->TXF=$ta);
  }
}

class SDB_SQLITE3 extends __SDB {
  function SDB_SQLITE3($filename, $flags=SQLITE3_OPEN_READWRITE ) {
    $this->CONN = new SQLite3($filename, $flags);
    if (!$this->CONN) die("SQLite: $filename: $errmsg\n"); 
  }
  function query($Q) { if($this->DEBUGMODE)echo "query: [$Q]"; return $this->CONN->query($Q); }
  function exec($Q) { if($this->DEBUGMODE)echo "exec: [$Q]"; return $this->CONN->exec($Q); }
  function close() { return $this->CONN->close(); }
  function fa($R, $restype=SQLITE3_ASSOC ) { return $R->fetchArray($restype); }
  function faa($R) { return $this->fa($R, SQLITE3_ASSOC); }
  function fan($R) { return $this->fa($R, SQLITE3_NUM); }
  function fo($R, $objectType = NULL) { 
      $array = $R->fetchArray(); 
      if(is_null($objectType)) $object = new stdClass(); else $object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($objectType), $objectType)); 
      $reflector = new ReflectionObject($object); 
      for($i = 0; $i < $R->numColumns(); $i++) { 
          $name = $R->columnName($i); 
          $value = $array[$name]; 
          try { 
              $attribute = $reflector->getProperty($name); 
              $attribute->setAccessible(TRUE); 
              $attribute->setValue($object, $value); 
          } catch (ReflectionException $e) { $object->$name = $value; } 
      } 
      return $object; 
  } 
  function nr($R) { return ($R->numColumns() && $R->columnType(0) != SQLITE3_NULL) ? -1 : 0; }
  function nf($R)	{return $R->numColumns();}
  function fn($R,$i)	{return $R->columnName($i);}
  function free($R) { return $R->finalize() ; }
}

class SDB_OCI extends __SDB {
  function SDB_OCI($TSN, $DBUSER, $DBPASS) { 
    $this->CONN=0;
    $RETRY=5;
    while( (!$this->CONN) && ($RETRY--) ) {
      if ($conn=oci_connect($DBUSER,$DBPASS,$TSN,"AL32UTF8")) $this->CONN=$conn; else sleep(1);
    }
    if(!$this->CONN){$e = oci_error();die (sprintf(SDB_ERRF,'',htmlentities($e['message'])));}
    $this->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");
  }
  function exec($Q,$COMMITMODE=OCI_DEFAULT) {
    if ($this->DEBUGMODE) echo "$Q\n";
    $stid = oci_parse($this->CONN, $Q);
    if (!$stid) { $e = oci_error($this->CONN);die(sprintf(SDB_ERRF, $Q, htmlentities($e['message'])));}
    $r = oci_execute($stid, $COMMITMODE);
    if (!$r) {$e = oci_error($stid);die(sprintf(SDB_ERRF, $Q, htmlentities($e['message'])));}
    return $stid;
  }
  function query($Q)	{ return $this->exec($Q); }
  function commit()	{ if ($this->DEBUGMODE) echo "commit\n"; return oci_commit($this->CONN); }
  function rollback()	{ if ($this->DEBUGMODE) echo "rollback\n"; return oci_rollback($this->CONN); }
  function close()	{ return oci_close($this->CONN) ; }
  function fa($R,$mode=OCI_BOTH)	{ return oci_fetch_array($R,$mode) ; }
  function faa($R)	{ return $this->fa($R,OCI_ASSOC);}
  function fan($R)	{ return $this->fa($R,OCI_NUM);}
  function fo($R)	{ return oci_fetch_object($R) ; }
  function nr($R)	{ return oci_num_rows($R) ; }
  function nf($R)	{ return oci_num_fields($R);}
  function fn($R,$i)	{ return oci_field_name($R,$i);}
  function free($R)	{ if ($this->DEBUGMODE) echo "free\n"; return oci_free_statement($R); }
  
  function settabletextcols($tabl) {
    if (!strlen($tabl)) return 0;
    $stmt=oci_parse($this->CONN,"select * from $tabl where rownum=1");
    oci_execute($stmt);
    $ncols = oci_num_fields($stmt);
    $parA = array('CHAR','NCHAR','VARCHAR2','NVARCHAR2','DATE','TIMESTAMP','TIMESTAMP WITH LOCAL TIMEZONE','TIMESTAMP WITH TIMEZONE');
    $fieldA = array();
    for ($i = 1; $i <= $ncols; $i++) if (in_array(oci_field_type($stmt,$i),$parA)) {$fieldA[]=oci_field_name($stmt,$i);$fieldA[]=strtolower(oci_field_name($stmt,$i));}
    return ($this->TXF = $fieldA);
  }
}


?>
