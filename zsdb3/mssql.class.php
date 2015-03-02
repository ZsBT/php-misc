<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* MSSQL	(needs php5-sybase)

*/

class zsdb3_mssql{

  function __construct($DBHOST,$DBPORT=1433,$DBNAME=0,$DBUSER='',$DBPASS=''){
    if(!function_exists("mssql_pconnect"))return $this->fatal("php5-sybase not installed\n");
    $tries=3;
    if(!$DBPORT)$DBPORT=1433;
    while($tries-- && !$this->CONN=mssql_pconnect($DBHOST,$DBUSER,$DBPASS))sleep(2);
    if(!$this->CONN)return $this->fatal("MSSQL: db connect error\n");
    if($DBNAME)if(!mssql_select_db($DBNAME))return $this->fatal("MSSQL: selecting db '$DBNAME' failed\n");
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
  
  function set_encoding($enc){
    ini_set('mssql.charset', strtoupper($enc) );
  }
  
  function fatal($msg){ die($msg); }
  
  function mssql_escape($data) {
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
      $va[]=$this->mssql_escape($v);
    }
    $Q = sprintf("insert into $table (%s) values (%s)", implode(',',$ka), implode(',',$va) );
	return $this->exec($Q);
  }

  function update($table, $datarr, $cond=0 ) {
    if(!$table)return false;
    if(!$datarr)return false;
    if(!$cond)return false;	// uncomment if brave
    
    foreach($datarr as $k=>$v)
      $seta[]="$k=".$this->mssql_escape($v);
    
    $Q = "update $table set ".implode(",",$seta);
    if($cond)$Q.=" where $cond";
    
    return $this->exec($Q);
  }
  
}
