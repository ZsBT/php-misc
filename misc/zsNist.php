<?php /*

	NIST class to handle ANSI/NIST fingerprint data
	
	(c) 2014 Zsombor




class synopsis

  public $data=false;   // the structured data 
	$data[1] is type-1 record
	$data[2] is type-2 record
	$data[4] is type-4 record
	
  public function readfile($fn)        // reads a NIST fingerprint file to structured $data 
  public function read($s)     // read NIST fp data from string
  public function build()      // builds the NIST file and returns as string (ANSI/NIST format)




	
	                                                                                */
/* separator characters */
define("NIST_FS", "\x1C");
define("NIST_GS", "\x1D");
define("NIST_RS", "\x1E");
define("NIST_US", "\x1F");


class zsNist {
  public $data=false;	/* the structured data */
  
  public function readfile($fn){	/* reads a NIST fingerprint file to structured data */
    if(!$s=file_get_contents($fn))die("no such file: $fn\n");
    return $this->read($s);
  }
  
  public function read($s){	/* read type-1, type-2 and type-4 records */
    $reca = explode(NIST_FS, $s);
    $t1s=array_shift($reca);
    $t2s=array_shift($reca);
    $t4s=implode(NIST_FS,$reca);
    
    foreach(explode(NIST_GS, $t1s) as $line)
      if(preg_match('/^(\d+)\.(\d+):(.+)/',$line,$ma))
        $type[$ma[1]][0+$ma[2]]=$ma[3];

    foreach(explode(NIST_GS, $t2s) as $line)
      if(preg_match('/^(\d+)\.(\d+):(.+)/',$line,$ma))
        $type[$ma[1]][0+$ma[2]]=$ma[3];
        
    while($b = &$this->shiftT4($t4s))
      $type[4][]=$b;
      
    return $this->data = &$type;
  }
  
  private function shiftT4(&$t4s){	/* reads next type-4 data */
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
  
  private function readnum(&$bin, $start, $len=1){	/* reads unsigned positive number from binary data */
    $ss = substr($bin, $start-1, $i=$len);
    while(strlen($ss)<4)$ss=chr(0).$ss;
    $numa = unpack("N",$ss);return $numa[1];
  }
  
  private function makenum($num, $len=1){		/* creates number as binary string */
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
  
  private function concat($tn, &$record){	/* imploding a type-1 or type-2 record */
    $reta = array();
    foreach($record as $k=>$v)
      $reta[]=sprintf("%d.%03d:%s", $tn, $k, $v);
    $ret = implode(NIST_GS,$reta);
    $len=1+strlen($ret);	/* +1 because of FS */
    $rlen = &$record[1];
    if($len!=$rlen){	/* in case of size needs to be corrected */
      $rlen=$len;
      return $this->concat($tn,$record);
    }
    return $ret;
  }
  
  public function build(){	/* builds the NIST file and returns as string */
    $type = &$this->data;
    $idca=array();
    
    /* type-2 */
    $idca[]="2".NIST_US."00";
    $t2s = $this->concat(2,$type[2]);
    
    /* type-4 */
    $t4s="";
    foreach($type[4]as $i=>$t4){
      $idc = 1+$i;
      $idca[] = sprintf("4%s%02d", NIST_US, $idc);
      $t4s.=	$this->makenum($t4[1], 4)
        .$this->makenum($idc)
        .$this->makenum($t4[3])
        .$this->makenum($t4[4]). "ÿÿÿÿÿ"
        .$this->makenum($t4[5])
        .$this->makenum($t4[6],2)
        .$this->makenum($t4[7],2)
        .$this->makenum($t4[8])
        .$t4[9]
        ;
    }
    
    /* type-1 */
    $type[1][2] = "0300";
    $type[1][3] = "1".NIST_US.count($idca).NIST_RS.implode(NIST_RS, $idca);	/* CNT */
    $type[1][5] = date("Ymd");
    $t1s = $this->concat(1,$type[1]);
    
    return $t1s.NIST_FS.$t2s.NIST_FS.$t4s;
  }
  
}

