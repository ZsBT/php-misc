<?php /*

	(c) Zsombor's simple cache class with methods set and get				
	
  Variables
  
  public static $PREFIX;		// where and what name to use for storing data
  public static $TIMEOUT;			// expiration, in seconds
  
  
  Methods
  
  public static function set($key,$data)	// write to cache file
  public static function get($key)		// read from cache file
  public static function del($key)		// delete from cache file
  public static function cleanup()	// delete expired files
  	
	*/

namespace ZsBT\misc;

abstract class Cache {
  public static $PREFIX = "/tmp/zsCache";
  public static $TIMEOUT = 360;
  

  private static function fnbykey($key)	{	// generate a filename by key
    return sprintf("%s-%s.ob", self::$PREFIX, md5($key) );
  }


  public static function set($key,$data)	// write to cache file
  {
    return file_put_contents(self::fnbykey($key), @serialize($data));
  }

  
  public static function get($key)		// read from cache file
  {
    $fn=self::fnbykey($key);
    if(!file_exists($fn))return NULL;
    
    if( (time() - filemtime($fn)) > self::$TIMEOUT){
      unlink($fn);
      return NULL;
    }
    return @unserialize(@file_get_contents($fn));
  }
  

  public static function del($key){	// delete from cache
    return unlink(self::fnbykey($key));
  }
  
  
  public static function cleanup()	// delete expired files
  {
    foreach( glob(self::$PREFIX."*") as $fn )
      if( (time()- filemtime($fn)) > self::$TIMEOUT )
        unlink($fn);
  }

}
?>
