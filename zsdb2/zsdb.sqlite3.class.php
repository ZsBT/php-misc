<?php /*
	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Sqlite3 (needs php5-sqlite)
*/
require_once 'zsdb.class.php';

class ZSDB_SQLITE3 extends __ZSDB {
  function ZSDB_SQLITE3($filename, $flags=SQLITE3_OPEN_READWRITE ) {
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
  function nr($R) { return ($R->numColumns() && $R->columnType(0) != SQLITE3_NULL) ? -1 : 0; }
  function nf($R)	{return $R->numColumns();}
  function fn($R,$i)	{return $R->columnName($i);}
  function free($R) { return $R->finalize() ; }
}

