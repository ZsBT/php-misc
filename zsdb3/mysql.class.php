<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Mysql (needs php5-mysql)

*/

class zsdb3_mysql {
  function __construct($DBHOST, $DBPORT, $DBNAME, $DBUSER, $DBPASS='') {
    if ($DBPORT) $DBHOST .= ':' . $DBPORT ;
    if (!$this->CONN = mysql_connect($DBHOST, $DBUSER, $DBPASS)) die ('MySQL: db connect error: '.mysql_error()) ;
    if (!mysql_select_db($DBNAME, $this->CONN)) die ("MySQL: error selecting db $DBNAME: ".$DBNAME) ;
    
  }
  function exec($Q) { return mysql_query($Q, $this->CONN); }
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

  function mysql_escape($data) {
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
      $va[]=$this->mysql_escape($v);
    }
    $Q = sprintf("insert into $table (%s) values (%s)", implode(',',$ka), implode(',',$va) );
	return $this->exec($Q);
  }

  function update($table, $datarr, $cond=0 ) {
    if(!$table)return false;
    if(!$datarr)return false;
    if(!$cond)return false;	// uncomment if brave
    
    foreach($datarr as $k=>$v)
      $seta[]="$k=".$this->mysql_escape($v);
    
    $Q = "update $table set ".implode(",",$seta);
    if($cond)$Q.=" where $cond";
    
    return $this->exec($Q);
  }
}

