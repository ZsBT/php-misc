<?php	/*

  simple class to log somewhere
  
  */


abstract class logger {

  public function info($msg, $trace=true, $debug=false){
    if(!is_string($msg))$msg = json_encode($msg);
    $msg = str_replace("\n", "\\n", $msg);
    
    $line = date("Y-m-d H:i:s ")."{ $msg } ";
    
    if($ip = $_SERVER["REMOTE_ADDR"])$line.="<$ip> ";
    
    if($trace){
      $trca = debug_backtrace();
      foreach($trca as $trc){
        $ob = (object)$trc;
        $fun = $ob->function;
        if(isset($ob->class)){
          if( ($ob->class=="logger") && ($fun!="info") )continue;
          $fun = "{$ob->class}::$fun";
        }
        
        if($debug)$fun.= json_encode($ob->args);else $fun.="()";
        $fun.=sprintf("@%s:{$ob->line}",basename($ob->file));
        
        $line.= "$fun; ";
      }
    }
    
    // here you can set any other output you would like
    file_put_contents("php://stderr", "$line\n");
#    return file_put_contents(sprintf("%s/galamb-%s.log",LOG_DIR,date("Ymd") ), "$line\n", FILE_APPEND);
  }
  

  public function error($msg){
    if(!is_string($msg))$msg = json_encode($msg);
    logger::info("ERROR: $msg");
    return false;
  }
  
  
  public function warn($msg){
    if(!is_string($msg))$msg = json_encode($msg);
    logger::info("WARNING: $msg");
    return $msg;
  }
  
  
  
}

