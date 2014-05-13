<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Mysql (needs php5-mysql)

*/

class zsdb3_mysqli {
  var $ENCODING;
  function __construct($DBHOST, $DBPORT, $DBNAME, $DBUSER, $DBPASS='') {
    if ($DBPORT) $DBHOST .= ':' . $DBPORT ;
    if (!$this->CONN = mysqli_connect($DBHOST, $DBUSER, $DBPASS, $DBNAME))
      die ('MySQLi: db connect error: '.mysqli_connect_error()) ;
  }
  
  function set_encoding($cs){ return mysqli_set_charset($this->CONN, $cs); }
  
  function exec($Q) { return $ret = mysqli_query($this->CONN, $Q); }
  function query($Q)	{ return $this->exec($Q) ; }
  function close()	{ return mysqli_close($this->CONN) ; }
  function fa($R)	{ return mysqli_fetch_array($R) ; }
  function faa($R)	{ return mysqli_fetch_array($R,mysqli_ASSOC) ; }
  function fan($R)	{ return mysqli_fetch_array($R,mysqli_NUM) ; }
  function fo($R)	{ return mysqli_fetch_object($R) ; }
  function nr($R)	{ return mysqli_num_rows($R) ; }
  function nf($R)	{ return mysqli_num_fields($R); }
  function fn($R,$i)	{ return mysqli_field_name($R,$i); }
  function free($R)	{ return mysqli_free_result($R); }

  function mysqli_escape($data) {return sprintf("'%s'", str_replace("'","`",$data) );}
  
  function insert($table, $datarr) {
    if(!$table)return false;
    if(!$datarr)return false ;
    
    foreach($datarr as $k=>$v){
      $ka[]=$k;
      $va[]=$this->mysqli_escape($v);
    }
    $Q = sprintf("insert into $table (%s) values (%s)", implode(',',$ka), implode(',',$va) );
	return $this->exec($Q);
  }

  function update($table, $datarr, $cond=0 ) {
    if(!$table)return false;
    if(!$datarr)return false;
    if(!$cond)return false;	// uncomment if brave
    
    foreach($datarr as $k=>$v)
      $seta[]="$k=".$this->mysqli_escape($v);
    
    $Q = "update $table set ".implode(",",$seta);
    if($cond)$Q.=" where $cond";
    
    return $this->exec($Q);
  }
}
