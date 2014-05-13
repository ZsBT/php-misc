<?php /*
	2012-2013 (c) [Z]sombor's [S]imple [D]ata[B]ase	v3.0

	[Z]sombor's [S]imple [D]ata[B]ase	v3.0
	
	See README.md in directory 'zsdb3' for usage

*/


class zsdb3 {
  private $CONN /* connection variable */
    ,$in_transaction = false
    ;
  function __construct($dbspec,$encoding="UTF8"){		/* format: type::dbname@host[:port][/user:password] or sqlite3::filename */
    if(preg_match('/(sqlite3)::(.+)/i', $dbspec, $ma));else
    if(preg_match('/(.+)::(.+)@([a-z0-9_\.-]+)(?::(\d+))?(?:\/([a-z0-9_\.]+:.*))?/i', $dbspec, $ma));else
      return $this->fatal("Wrong db spec: '$dbspec'");
      
    $fn = sprintf("%s/zsdb3/%s.class.php", __DIR__, $type=$ma[1]);
    if(!file_exists($fn))$this->fatal("Module '$type' not found. Expecting file '$fn' ");

    require_once "$fn";
    $class = "zsdb3_$type";
    
    if($type=="sqlite3")
      list($spec, $type, $dbname)=$ma;
    else {
      list($spec, $type, $dbname, $host, $port, $userpass)=$ma;
      if(preg_match('/([a-z0-9_\.]+):(.*)/i',$userpass,$ma1)&& array_shift($ma1))list($user,$pass)=$ma1;
    }
    
    switch(strtolower($type)){
      case 'sqlite3':
        $D = new $class($ma[2]);
        break;
      case 'oracle':
        if(!$port)$port=1521;
        $tns="(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))(CONNECT_DATA =(SERVER = DEDICATED)(SID = $dbname)))";
        $D = new $class($tns,$user,$pass,$encoding);
        break;
      case 'psql':
      case 'mysql':
      case 'mysqli':
      case 'mssql':
        $D = new $class($host, $port, $dbname, $user, $pass);
        break;
      default:
        $this->fatal("database $type not supported");
    }
    
    $D->set_encoding($encoding);
  
    if(!$D)$this->fatal("Error connecting to $type database");
    $this->D = &$D;
    return $D;
  }
  
  private function sql_escape($data) {
      return sprintf("'%s'", str_replace("'","`",$data) );
      if(is_numeric($data))return $data;
      $unpacked = unpack('H*hex', $data);
      return '0x' . $unpacked['hex'];
  }
  
  private function fatal($msg){die("\n\tZSDB3: Fatal error: $msg\n\n");}
  
  function __call($method, $args){	// for a function in specific class
    return call_user_func_array(array($this->D,$method), $args);
  }
  


  public function insert($table, $datarr) {	/* inserts an array-specified row to a table */
    if(!$table)return false;
    if(!$datarr)return false ;
    foreach($datarr as $k=>$v){
      $ka[]=$k;
      $va[]=$this->sql_escape($v);
    }
    $Q = sprintf("insert into $table (%s) values (%s)", implode(',',$ka), implode(',',$va) );
	return $this->exec($Q);
  }
  public function i($table,$datarr)	/* alias for insert() */
    {return $this->insert($table,$datarr);}

  public function update($table, $datarr, $cond="" ) { /* updates a row */
    if(!$cond)return false;	// uncomment if brave
    if(!$table)return false;
    if(!$datarr)return false;
    foreach($datarr as $k=>$v)$seta[]="$k=".$this->sql_escape($v);
    $Q = "update $table set ".implode(",",$seta);
    if($cond)$Q.=" where $cond";
    return $this->exec($Q);
  }
  public function u($table,$datarr,$cond=0) /* alias for update() */
    {return $this->update($table,$datarr,$cond);}
  
  public function iou($t, $datarr, $cond=0) {	/* deletes then inserts a row */
    if(!$cond)return false;
    $this->exec("delete from $t where $cond");
    return $this->insert($t,$datarr);
  }



  public function Q($query) { /* Query one field of one row. returns a simple value */
    $R=$this->query($query);
    $fa=$this->fan($R);
    if($R)$this->free($R);else return false;
    return $fa[0];
  }
  public function QFO($query) /* queries the first row then returns as an object */
    { $R=$this->query($query);$fo=$this->fo($R);if ($R) $this->free($R);return $fo;}
  public function QFA($query) /* queries the first row then returns as an array */
    { $R=$this->query($query);$fa=$this->faa($R);if ($R) $this->free($R);return $fa;}
  public function QA($query) /* queries one field of several rows */
    { $R=$this->query($query);$ret=array();while ($fa=$this->fan($R)) $ret[]=$fa[0];if ($R) $this->free($R);return $ret;}
  public function QAA($query) /* queries array of rows, elements are associated array of fields */
    { $R=$this->query($query);$ret=array();while($fa=$this->faa($R))$ret[]=$fa;if($R)$this->free($R);return $ret; }
  public function QOA($query) /* queries array of rows, elements are objects of fields */
    { $R=$this->query($query);$ret=array();while ($fo=$this->fo($R)) $ret[]=$fo;if($R)$this->free($R);return $ret;}
  public function btrans() /* begin transaction */
    { return $this->in_transaction=$this->exec("begin transaction"); }
  public function commit() /* commit transaction */
    { $this->in_transaction=false; return $this->exec("commit");}
  public function rollback() /* rollback transaction */
    { $this->in_transaction=false; return $this->exec("rollback");}
  
}
