<?php /*
	20102-2013 (c) [Z]sombor's [S]imple [D]ata[B]ase	v2.7
	
	this is the base class. it is extended by a specific database class
*/

define ('ZSDB_ERRF', "\n<pre>query:[%s]<br>return:[%s]</pre>\n");	/* error message format */

class __ZSDB {
  var $CONN /* connection variable */
    ,$DEBUGMODE = false /* show sql commands */
    ,$SHOWERROR = true /* show error messages */
    ;
  var $TXF = array() /* field values to be parenthised */
    ;

  function settc($table) { return $this->settabletextcols($table); }	/* synonym for settabletextcols */

  /* $datarr is an associated array( key => value)  - dont forget to use settabletextcols! */
  function fieldnames($R){$ret=array();$nf=$this->nf($R);for($i=0;$i<$nf;$i++)$ret[]=$this->fn($R,$i);return $ret;}
  function update($table, $datarr, $cond=0) {
    if (!$cond) return false;	/* comment out if you're brave */
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
  function Q($query) { /* gives a simple value; use for a query that returns one row and one field */
    $R=$this->query($query);
    $fa=$this->fan($R);
    if($R)$this->free($R);else return false;
    return $fa[0];
  }
  function QFO($query) { /* object of a row */ $R=$this->query($query);$fo=$this->fo($R);if ($R) $this->free($R);return $fo;}
  function QFA($query) { /* array of a row */ $R=$this->query($query);$fa=$this->faa($R);if ($R) $this->free($R);return $fa;}
  function QA($query) { /* array of a single column */ $R=$this->query($query);$ret=array();while ($fa=$this->fan($R)) $ret[]=$fa[0];if ($R) $this->free($R);return $ret;}
  function QAA($query) { /* gives an array of associative arrays; use for small amount of rows */
    $R=$this->query($query);$ret=array();while ($fa=$this->faa($R)) $ret[]=$fa;if ($R) $this->free($R);
    return $ret;
  }
  function QAO($query) { /* gives an array of objects; use for small amount of rows */
    $R=$this->query($query);$ret=array();while ($fo=$this->fo($R)) $ret[]=$fo;if ($R) $this->free($R);
    return $ret;
  }
  function btrans(){ return $this->exec("begin transaction;"); }
  function commit(){ return $this->exec("commit");}
  function rollback(){ return $this->exec("rollback");}
}
