<?php /*

	(c) Zsombor's simple object-cache class with methods set and get				
  
  
  Methods
  
  public function put($key,$data)	// write to cache 
  public function get($key, $callable)		// read from cache 
  public function del($key)		// delete from cache 
  public function cleanup($force=false)	// delete (expired) files
  	
	*/

namespace ZsBT\misc;

class oCache {
    
  /**
    the constructor
    $TTL	(time-to-leave) lifetime, in seconds. 
    $DIR	the writable directory, exclusively used by this cache. if not exists, we'll try to create it
    $PREFIX	optional name prefix for the individual files
  
  **/
  
  
  
  function __construct($DIR=NULL,$TTL,$PREFIX="x"){
    if(!$DIR)$DIR = sys_get_temp_dir()."/oCache";
    $this->DIR = $DIR;
    $this->PREFIX=$PREFIX;
    $this->TTL = ( $TTL ? $TTL : 600) ;
    
    if(!file_exists($this->DIR) && !mkdir($this->DIR,0775,true))
      throw new oCacheException("Cannot create directory {$this->DIR}", 510);
    
    if(!file_put_contents("{$this->DIR}/CACHEDIR.TAG", "Signature: 8a477f597d28d172789f06886806bc55\n# created by ".__FILE__."\n" ))
      throw new oCacheException("Cannot write directory {$this->DIR}", 511);
  }
  


  public function put($key,&$data,$TTL=NULL)	// write to cache 
  {
    $ob = new oCacheObject($key, $TTL ? $TTL : $this->TTL, $data );
    return file_put_contents($this->fnbykey($key), @serialize($ob));
  }

  
  // read from cache. optionally use a function to retrieve data if no stored version
  public function get($key, $callable=NULL )
  {
    $ob = @unserialize(@file_get_contents($this->fnbykey($key)));
    if($ob && ($ob->Expiry > time() ))return $ob->Data;
    
    if($callable){
      $data = $callable($key,$ob);
      if( ($data===NULL) && $ob )return $ob->Data;	// return expired object if $callable fails
      $this->put($key,$data);
      return $data;
    }
    return NULL;
  }
  
  
  public function expire($key){	// make cache object expired
    $ob = @unserialize(@file_get_contents($this->fnbykey($key)));
    $ob->Expiry = time()-1;
    return file_put_contents($this->fnbykey($key), @serialize($ob) );
  }
  

  public function del($key){	// delete from cache
    return unlink($this->fnbykey($key));
  }
  
  
  public function cleanup($force=FALSE)	// delete files, only expired ones by default
  {
    foreach( glob(sprintf("%s/%s-*.ob",$this->DIR,$this->PREFIX)) as $fn ){
      if(!$ob = @unserialize(@file_get_contents($fn)))unlink($fn);else 
        if($force || ($ob->Expiry < time()) )
          unlink($fn);
    }
  }

  private function fnbykey($key)	{	// generate a filename by key
    return sprintf("%s/%s-%s.ob", $this->DIR,$this->PREFIX, md5(serialize($key)) );
  }


}


class oCacheObject { 
  public $Created, $Expiry, $Key, $Data; 
  
  function __construct(&$Key,$TTL,&$Data){
    $this->Created = time();
    $this->Key = &$Key;
    $this->Expiry = time()+$TTL;
    $this->Data = $Data;
  }
  
}


class oCacheException extends \Exception { }

?>
