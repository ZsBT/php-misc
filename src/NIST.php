<?php /*

	zsNist.php	v1.0
	
	NIST class to handle ANSI/NIST fingerprint data
	
	  For details, see: http://fingerprint.nist.gov/
	
	
	(c) 2014 kalo@zsombor.net
	
	LICENSE: see http://www.wtfpl.net/txt/copying/



class synopsis

  public $data=false;   // the structured data 
	$data[1] is type-1 record
	$data[2] is type-2 record
	$data[4] is type-4 record

  public function readfile($fn){        // reads a NIST fingerprint file to structured $this->data 
  public function read($s){     // read NIST fingerprint from binary string to structured $this->data 
  public function build(){      // builds the NIST file from $this->data and returns as binary string 
  public function explode(){    // print loaded nist data to stdout and save images to files
	                                                                                */





/* separator characters */

define("NIST_FS", "\x1C");
define("NIST_GS", "\x1D");
define("NIST_RS", "\x1E");
define("NIST_US", "\x1F");

namespace ZsBT\misc;

class NIST {
  public $data=false;	// the structured data 
  
  public function readfile($fn){	// reads a NIST fingerprint file to structured $this->data 
    if(!$s=file_get_contents($fn))return false;
    return $this->read($s);
  }
  
  public function read($s){	// read NIST fingerprint from binary string to structured $this->data 
    $reca = @explode(NIST_FS, $s);
    $t1s=array_shift($reca);	// type-1 record must be the first
    $ts = @implode(NIST_FS, $reca);	// remaining (yet unknown) records
    
    foreach(explode(NIST_GS, $t1s) as $line)	// reading type-1 values
      if(preg_match('/^(\d+)\.(\d+):(.+)/',$line,$ma))
        $type[$ma[1]][0+$ma[2]]=$ma[3];
        
    $CNT = $type[1][3];		// file content (CNT)
    if(!preg_match('/^1'.NIST_US.'/',$CNT))	// first information in CNT must be '1'
      throw new Exception("Invalid header in 1.003");
      
    $subfields = @explode(NIST_RS,$CNT);
    array_shift($subfields);	// number of other (type-2 and type-4) records
    
    foreach($subfields as $subfield){
      list($rtyp,$idc) = explode(NIST_US, $subfield);
      switch($rtyp){
        case 2:
          if(!preg_match('/^[0-9\.]+:(\d+)/',$ts,$ma))
            throw new Exception("Invalid type-2 record");
          $t2len = $ma[1];
          $t2s = substr($ts,0,$ma[1]-1);
          foreach(explode(NIST_GS, $t2s) as $line)
            if(preg_match('/^(\d+)\.(\d+):(.+)/',$line,$ma))
              $type[$ma[1]][0+$ma[2]]=$ma[3];
          $ts = substr($ts,$t2len);
          break;
        case 4:
          $type[4][] = &$this->shiftT4($ts);
          break;
        default:
          throw new Exception("Unknown record type-$rtyp");
      }
    }
    
    return $this->data = &$type;
  }
  
  private function shiftT4(&$t4s){	// reads next type-4 data 
    if(!strlen($t4s))return false;
    $ret = array();
    $ret[1] = $size=$this->readnum($t4s, 1, 4);
    $ret[2] = $this->readnum($t4s, 5);
    $ret[3] = $this->readnum($t4s, 6);
    $ret[4] = $this->readnum($t4s, 7);
    $ret[5] = $this->readnum($t4s, 13);
    $ret[6] = $this->readnum($t4s, 14, 2);
    $ret[7] = $this->readnum($t4s, 16, 2);
    $ret[8] = $this->readnum($t4s, 18);
    $ret[9] = &substr($t4s, 18, $size-18);
    $t4s = &substr($t4s, $size);
    return $ret;
  }
  
  private function readnum(&$bin, $start, $len=1){	// reads unsigned positive number from binary data 
    $ss = substr($bin, $start-1, $i=$len);
    while(strlen($ss)<4)$ss=chr(0).$ss;
    $numa = unpack("N",$ss);return $numa[1];
  }
  
  private function makenum($num, $len=1){		// creates number as binary string 
    switch($len){
      case 1:
        return pack("C", $num);
      case 2:
        return pack("n", $num);
      case 4:
        return pack("N", $num);
    }
    return false;
  }
  
  private function concat($tn, &$record){	// imploding a type-1 or type-2 record to binary string
    $reta = array();
    foreach($record as $k=>$v)
      $reta[]=sprintf("%d.%03d:%s", $tn, $k, $v);
    $ret = implode(NIST_GS,$reta);
    $len=1+strlen($ret);	// +1 because of FS 
    $rlen = &$record[1];
    if($len!=$rlen){	// in case of size needs to be corrected 
      $rlen=$len;
      return $this->concat($tn,$record);
    }
    return $ret;
  }
  
  public function build(){	// builds the NIST file from $this->data and returns as binary string 
    if(!$this->data)return false;
    $type = &$this->data;
    $idca=array();
    
    // type-2 
    $idca[]="2".NIST_US."0";
    $type[2][2]=0;
    $t2s = $this->concat(2,$type[2]);
    
    // type-4 
    $t4s="";
    foreach($type[4]as $i=>$t4){
      $idc = 1+$i;
      $idca[] = sprintf("4%s%02d", NIST_US, $idc);
      $t4is=	$this->makenum($t4[1], 4)
        .$this->makenum($idc)
        .$this->makenum($t4[3])
        .$this->makenum($t4[4]). "ÿÿÿÿÿ"
        .$this->makenum($t4[5])
        .$this->makenum($t4[6],2)
        .$this->makenum($t4[7],2)
        .$this->makenum($t4[8])
        .$t4[9]
        ;
      $rlen = strlen($t4is);
      $t4is=	$this->makenum($rlen, 4)	// rebuild is necessary to count record length 
        .$this->makenum($idc)
        .$this->makenum($t4[3])
        .$this->makenum($t4[4]). "ÿÿÿÿÿ"
        .$this->makenum($t4[5])
        .$this->makenum($t4[6],2)
        .$this->makenum($t4[7],2)
        .$this->makenum($t4[8])
        .$t4[9]
        ;
      $type[4][$i][1] = $rlen;
      $t4s.=$t4is;
    }
    
    // type-1 
    $type[1][2] = "0300";	// the class creates this version.
    $type[1][3] = "1".NIST_US.count($idca).NIST_RS.implode(NIST_RS, $idca);	// CNT 
    $type[1][5] = date("Ymd");
    $t1s = $this->concat(1,$type[1]);
    
    return $t1s.NIST_FS.$t2s.NIST_FS.$t4s;
  }

  public function explode(){	// print loaded nist data to stdout and save images to files
    if(!$this->data)return false;
    $ret="";
    foreach(array(1,2)as $ri)
    if(is_array($this->data))foreach($this->data[$ri] as $field=>$data){
        $ret.=sprintf("%d.%03d=%s\n", $ri, $field, $data);
    }
    
    foreach($this->data[4] as $record)
      foreach($record as $field=>$data){
        if($field==9){	// 4.009 is the binary data 
          file_put_contents($fn=sprintf("finger_%d.wsq", $record[4]), $data);
          $data=$fn;
        }
        $ret.=sprintf("4.%03d=%s\n", $field, $data);
      }
    return $ret;
  }
  
  
}
?>
