<?php /*
	20102-2013 (c) [Z]sombor's [S]imple [D]ata[B]ase	v3.0
	
	usage:
	
	$db = new zsdb3($connspec);
	

	for psql, mysql, mysqli, mssql, oracle, connspec syntax is :
	
	type::dbname@host[:port][/user:password]
	
	eg: mysqli::mydb@localhost/username:s3cr3tp@ss
	

	for sqlite, connspec is:
	
	sqlite::/path/to/sqlite3.db
	
	
*/


class zsdb3 {
  var $CONN /* connection variable */
    ,$in_transaction = false
    ;
  function __construct($dbspec,$encoding="UTF8"){		/* format: type::dbname@host[:port][/user:password] or sqlite::filename */
    if(preg_match('/(sqlite3)::(.+)/i', $dbspec, $ma));else
    if(preg_match('/(.+)::(.+)@([a-z0-9_\.-]+)(?::(\d+))?(?:\/([a-z0-9_\.]+:.*))?/i', $dbspec, $ma));else
      return $this->fatal("Wrong db spec: '$dbspec'");
      
    $fn = sprintf("%s/zsdb3/%s.class.php", __DIR__, $type=$ma[1]);
    if(!file_exists($fn))$this->fatal("Module '$type' not found ($fn) ");

    require_once "$fn";
    $class = "zsdb3_$type";
    
    list($spec, $type, $dbname, $host, $port, $userpass)=$ma;
    if(preg_match('/([a-z0-9_\.]+):(.*)/i',$userpass,$ma1)&& array_shift($ma1))list($user,$pass)=$ma1;
    
    switch(strtolower($type)){
      case 'sqlite3':
        $D = new $class($ma[2]);
        break;
      case 'oracle':
        if(!$port)$port=1521;
        $tns="(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))(CONNECT_DATA =(SERVER = DEDICATED)(SID = $dbname)))";
        $D = new $class($tns,$user,$pass);
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
  
  function sql_escape($data) {
      return sprintf("'%s'", str_replace("'","`",$data) );
      if(is_numeric($data))return $data;
      $unpacked = unpack('H*hex', $data);
      return '0x' . $unpacked['hex'];
  }
  
  function insert($table, $datarr) {
    if(!$table)return false;
    if(!$datarr)return false ;
    foreach($datarr as $k=>$v){
      $ka[]=$k;
      $va[]=$this->sql_escape($v);
    }
    $Q = sprintf("insert into $table (%s) values (%s)", implode(',',$ka), implode(',',$va) );
	return $this->exec($Q);
  }
  function i($table,$datarr){return $this->insert($table,$datarr);}

  function update($table, $datarr, $cond=0 ) {
    if(!$cond)return false;	// uncomment if brave
    if(!$table)return false;
    if(!$datarr)return false;
    foreach($datarr as $k=>$v)$seta[]="$k=".$this->sql_escape($v);
    $Q = "update $table set ".implode(",",$seta);
    if($cond)$Q.=" where $cond";
    return $this->exec($Q);
  }
  function u($table,$datarr,$cond=0){return $this->update($table,$datarr,$cond);}
  
  function iou($t, $datarr, $cond=0) {	/* delete first */
    if(!$cond)return false;
    $this->exec("delete from $t where $cond");
    return $this->insert($t,$datarr);
  }



  function Q($query) { /* gives a simple value; use for a query that returns one row and one field */
    $R=$this->query($query);
    $fa=$this->fan($R);
    if($R)$this->free($R);else return false;
    return $fa[0];
  }
  function QFO($query) { /* object of the first row */ $R=$this->query($query);$fo=$this->fo($R);if ($R) $this->free($R);return $fo;}
  function QFA($query) { /* array of the first row */ $R=$this->query($query);$fa=$this->faa($R);if ($R) $this->free($R);return $fa;}
  function QA($query) { /* array of a single column from multiple records */ $R=$this->query($query);$ret=array();while ($fa=$this->fan($R)) $ret[]=$fa[0];if ($R) $this->free($R);return $ret;}
  function QAA($query) { /* gives an array of associative arrays; use for small amount of rows */
    $R=$this->query($query);$ret=array();while ($fa=$this->faa($R)) $ret[]=$fa;if ($R) $this->free($R);
    return $ret;
  }
  function QOA($query) { /* gives an array of objects; use for small amount of rows */
    $R=$this->query($query);$ret=array();while ($fo=$this->fo($R)) $ret[]=$fo;if ($R) $this->free($R);
    return $ret;
  }
  function btrans(){ return $this->in_transaction=$this->exec("begin transaction"); }
  function commit(){ $this->in_transaction=false; return $this->exec("commit");}
  function rollback(){ $this->in_transaction=false; return $this->exec("rollback");}
  
  public function __call($method, $args){return $this->D->$method($args[0],$args[1]) ;}		/* for a function in specific class */
  
}
