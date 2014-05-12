<?php /*

	2012 (c) Zsombor's [S]imple [D]ata[B]ase	v2.7
		* Oracle (needs pecl install oci8)

*/
class zsdb3_oracle {
  function __construct($TNS, $DBUSER, $DBPASS, $encoding) { 
    $this->CONN=false;
    if(!function_exists("oci_connect"))die("Oracle instant client is not installed.");
    if($encoding=="UTF8")$encoding="AL32UTF8";
    $this->CONN=oci_connect($DBUSER,$DBPASS,$TNS,$encoding);
  }
  function set_encoding(){return false;}
  function exec($Q,$COMMITMODE=OCI_DEFAULT) {
    $stid = oci_parse($this->CONN, $Q);
    $r = oci_execute($stid, $COMMITMODE);
    return $stid;
  }
  function query($Q)	{ return $this->exec($Q); }
  function commit()	{ return oci_commit($this->CONN); }
  function rollback()	{ return oci_rollback($this->CONN); }
  function close()	{ return oci_close($this->CONN) ; }
  function fa($R,$mode=OCI_BOTH)	{ return oci_fetch_array($R,$mode) ; }
  function faa($R)	{ return $this->fa($R,OCI_ASSOC);}
  function fan($R)	{ return $this->fa($R,OCI_NUM);}
  function fo($R)	{ return oci_fetch_object($R) ; }
  function nr($R)	{ return oci_num_rows($R) ; }
  function nf($R)	{ return oci_num_fields($R);}
  function fn($R,$i)	{ return oci_field_name($R,$i);}
  function free($R)	{ return oci_free_statement($R); }

}
