<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* MSSQL	(needs php5-sybase)

*/
require_once 'zsdb.class.php';

class ZSDB_MSSQL extends __ZSDB{
  function ZSDB_MSSQL($DBHOST,$DBPORT=1433,$DBNAME=0,$DBUSER='',$DBPASS=''){
    if(!function_exists("mssql_pconnect"))die("php5-sybase not installed\n");
    $tries=3;
    if(!$DBPORT)$DBPORT=1433;
    while($tries-- && !$this->CONN=mssql_pconnect($DBHOST,$DBUSER,$DBPASS))sleep(2);
    if(!$this->CONN)die("MSSQL: db connect error\n");
    if($DBNAME)if(!mssql_select_db($DNAME))die("MSSQL: selecting db '$DBNAME' failed\n");
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
  function settabletextcols($T){
    $ta = array();
    $i=0;
    $R=$this->query("select top 0 * from $T ");  
    while($fn=mssql_field_name($R)){
      switch($ft=mssql_field_type($R)){
        case "char":
        case "datetime":$ta[]=$fn;break;
      }
      $i++;
    }
    return ($this->TXF=$ta);
  }
}

