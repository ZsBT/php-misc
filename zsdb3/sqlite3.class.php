<?php /*
	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Sqlite3 (needs php5-sqlite)
*/

class zsdb3_sqlite3 {
  function __construct($filename, $flags=SQLITE3_OPEN_READWRITE ) {
    $this->CONN = new SQLite3($filename, $flags);
    if (!$this->CONN) die("SQLite: $filename: $errmsg\n"); 
  }
  function query($Q) { if($this->DEBUGMODE)echo "query: [$Q]"; return $this->CONN->query($Q); }
  function exec($Q) { if($this->DEBUGMODE)echo "exec: [$Q]"; return $this->CONN->exec($Q); }
  function close() { return $this->CONN->close(); }
  function fa($R, $restype=SQLITE3_ASSOC ) { return $R->fetchArray($restype); }
  function faa($R) { return $this->fa($R, SQLITE3_ASSOC); }
  function fan($R) { return $this->fa($R, SQLITE3_NUM); }
  function fo($R){$fa = $this->faa($R);if($fa)return (object)$fa;return FALSE; } 
  function nr($R) { return $this->CONN->changes(); }
  function nf($R)	{return $R->numColumns();}
  function fn($R,$i)	{return $R->columnName($i);}
  function free($R) { return $R->finalize() ; }
  function lasterror(){ return (object)array("code"=>$this->CONN->lastErrorCode(),"msg"=>$this->CONN->lastErrorMsg() ); }
  
  function insert($table, $datarr) {
    if(!$table)return false;
    if(!$datarr)return false ;
    
    foreach($datarr as $k=>$v){$ka[]=$k;$va[]=":$k";}
    $stQ = sprintf("insert into $table (%s) values (%s)", implode(',',$ka), implode(',',$va) );
    $stmt = $this->CONN->prepare($stQ);
    foreach($datarr as $k=>$v)
      $stmt->bindValue(":$k", $v);
    $r = $stmt->execute();
    
    return $r ? $this->CONN->lastInsertRowID():false ;
  }

  function update($table, $datarr, $cond=0) {
    if(!$cond)return false;	/* comment out if you're brave */
    $stQ = "update $table set " ;
    foreach($datarr as $k=>$v)$seta[]="$k=:$k";
    $stQ .= implode(', ', $seta) ;
    if($cond)$stQ.=' where '.$cond;
    
    $stmt = $this->CONN->prepare($stQ);
    foreach($datarr as $k=>$v)
      $stmt->bindValue(":$k", $v);
    $r = $stmt->execute();
    
    return $r? $this->CONN->changes() : false;
  }
  
}
