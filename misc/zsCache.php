<?php /*

	(c) Zsombor simple cache class with methods set and get				
	
	
CHANGELOG
  2015-10	remove expired files
  	
	*/


class zsCache {
  
  private function fnbykey($key){return $this->PF.md5($key);}

  function __construct($timeout=360,$prefix=''){
    $this->TO=$timeout;
    $this->PF=sys_get_temp_dir()."/.zscache_{$prefix}_";
  }

  
  public function set($key,$data){
    return file_put_contents($this->fnbykey($key), serialize($data));
  }

  
  public function get($key){
    $fn=$this->fnbykey($key);
    if(!file_exists($fn))return NULL;
    if(time() - filemtime($fn)>$this->TO){
      unlink($fn);
      return NULL;
    }
    return unserialize(file_get_contents($fn));
  }

}
