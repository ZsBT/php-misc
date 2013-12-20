<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Oracle (needs pecl install oci8)

*/
class zsdb3_oracle {
  function __construct($TNS, $DBUSER, $DBPASS) { 
    $this->CONN=0;
    $RETRY=5;
    if(!function_exists("oci_connect"))die("Oracle instant client is not installed.");
    while( (!$this->CONN) && ($RETRY--) ) if ($conn=oci_connect($DBUSER,$DBPASS,$TNS,"AL32UTF8")) $this->CONN=$conn; else sleep(1);
    if(!$this->CONN){$e = oci_error();if($this->DEBUGMODE)printf(ZSDB_ERRF,'',htmlentities($e['message']));return;}
    $this->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");
  }
  function exec($Q,$COMMITMODE=OCI_DEFAULT) {
    if ($this->DEBUGMODE) echo "$Q\n";
    $stid = oci_parse($this->CONN, $Q);
    if (!$stid) { $e = oci_error($this->CONN);if($this->DEBUGMODE)printf(ZSDB_ERRF, $Q, htmlentities($e['message']));}
    $r = oci_execute($stid, $COMMITMODE);
    if (!$r) {$e = oci_error($stid);if($this->DEBUGMODE)printf(ZSDB_ERRF, $Q, htmlentities($e['message']));}
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

  function ora_escape($data) {return sprintf("'%s'", str_replace("'","`",$data) );}
  
  function insert($table, $datarr) {
    if(!$table)return false;
    if(!$datarr)return false ;
    
    foreach($datarr as $k=>$v){
      $ka[]=$k;
      $va[]=$this->ora_escape($v);
    }
    $Q = sprintf("insert into $table (%s) values (%s)", implode(',',$ka), implode(',',$va) );
	return $this->exec($Q);
  }

  function update($table, $datarr, $cond=0 ) {
    if(!$table)return false;
    if(!$datarr)return false;
    if(!$cond)return false;	// uncomment if brave
    
    foreach($datarr as $k=>$v)
      $seta[]="$k=".$this->ora_escape($v);
    
    $Q = "update $table set ".implode(",",$seta);
    if($cond)$Q.=" where $cond";
    
    return $this->exec($Q);
  }
}
