<?php /*

	(c) Zsombor simple cache class with methods set and get				*/
	

class zsCache {
  private function fnbykey($key){return $this->PF.md5($key);}
  function __construct($timeout=360,$prefix=''){
    $this->TO=$timeout;
    $this->PF=sys_get_temp_dir()."/.zscache_{$prefix}_";
  }
  function set($key,$data){ return file_put_contents($this->fnbykey($key), serialize($data));}
  function get($key){
    $fn=$this->fnbykey($key);
    if(!file_exists($fn))return NULL;
    if(time()-filemtime($fn)>$this->TO)return NULL;
    return unserialize(file_get_contents($fn));
  }
}
