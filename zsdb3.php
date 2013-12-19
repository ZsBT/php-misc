<?php /*
	20102-2013 (c) [Z]sombor's [S]imple [D]ata[B]ase	v3.0
	
*/


class zsdb3 {
  var $CONN /* connection variable */
    ,$DEBUGMODE = false /* show sql commands */
    ,$SHOWERROR = true /* show error messages */
    ,$ERRF="\n<pre>zsdb3 query:[%s]<br>return:[%s]</pre>\n"	/* error message format */
    ,$FERRF="\n<pre>zsdb3: %s</pre>\n"	/* fatal error message format */
    ,$in_transaction = false
    ;
  function __construct($dbspec){		/* format: type::dbname@host[:port][/user:password] or sqlite::filename */
    if(preg_match('/(.+)::(.+)@([a-z0-9_\.-]+)(?::(\d+))?(?:\/([a-z0-9-_\.]+):(.*))?/i', $dbspec, $ma));else
    if(preg_match('/(sqlite3)::(.+)/i', $dbspec, $ma));else
      return $this->fatal("Wrong db spec: '$dbspec'");

    $fn = sprintf("%s/zsdb3/%s.class.php", __DIR__, $type=$ma[1]);
    if(!file_exists($fn))$this->fatal("Module '$type' not found ($fn) ");

    require_once "$fn";
    $class = "zsdb3_$type";
    
    $host = isset($ma[3])? $ma[3]:'localhost';
    
    switch($type){
      case 'psql':
      case 'mysql':
      case 'mssql':
        $D = new $class($host, $ma[4], $ma[2], $ma[5], $ma[6]);
        break;
      case 'sqlite3':
        $D = new $class($ma[2]);
        break;
      default:
        $this->fatal("$type not supported yet");
    }
  
    if(!$D)$this->fatal("Error connecting to $type database");
    $this->D = &$D;
    return $D;
    
  }
  
  private function fatal($msg){if($this->SHOWERROR)printf($this->FERRF, $msg);exit(1);}

  /* insert. returns the new id if available */
  function i($t,$datarr){return $this->D->insert($t,$datarr);}
  
  /* update. returns updated rows */
  function u($table,$datarr,$cond=0){return $this->D->update($table,$datarr,$cond);}
  
  function iou($t, $datarr, $conda) {	/* update if $cond has rows, else insert */
    $this->delete($t, $conda);
    return $this->insert($t,$datarr);
  }
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
  function QOA($query) { /* gives an array of objects; use for small amount of rows */
    $R=$this->query($query);$ret=array();while ($fo=$this->fo($R)) $ret[]=$fo;if ($R) $this->free($R);
    return $ret;
  }
  function QAO($query) {
    return $this->QOA($query);
  }
  function btrans(){ return $this->in_transaction=$this->exec("begin transaction"); }
  function commit(){ $this->in_transaction=false; return $this->exec("commit");}
  function rollback(){ $this->in_transaction=false; return $this->exec("rollback");}
  
  public function __call($method, $args){return $this->D->$method($args[0],$args[1]) ;}		/* for a function in specific class */
  
}
