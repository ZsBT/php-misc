<?php	/*

  Simple class to log something somewhere
	https://github.com/ZsBT
  
  Example: 
    logger::warn( "this is a warning" );
    logger::info(  array("debug"=>23)  );
  
  */


abstract class logger {
  const DATEFORMAT = "Y-m-d H:i:s"
    ,OUTFILE = "php://stdout"	// can be stderr or whatever
    ;

  static public function info($msg, $trace=true, $debug=false){
    // format to JSON if not string
    $msg = (is_string($msg) ? "{ $msg }" : json_encode($msg) );
    // strip newlines
    $msg = str_replace("\r", "", $msg);
    $msg = str_replace("\n", "\\n", $msg);
    $line = sprintf("%s { %s }", date(logger::DATEFORMAT), $msg);
    
    // include IP if exists
    if( isset($_SERVER["REMOTE_ADDR"]) )$line.=sprintf("<%s>", $_SERVER["REMOTE_ADDR"]);
    
    if($trace){
      $trca = debug_backtrace();
      array_shift($trca);
      foreach($trca as $trc){
        $ob = (object)$trc;
        $fun = $ob->function;
        if($ob->file == __FILE__ ) continue;
        if($debug)$fun.= json_encode($ob->args);else $fun.="()";
        $fun.=sprintf("@%s:{$ob->line}",basename($ob->file));
        
        $line.= "$fun; ";
        break;
      }
    }
    
    // here you can set any other output you would like
    file_put_contents("php://stdout", "$line\n", FILE_APPEND);
  }
  

  static public function error($msg, $trace=true, $debug=false){
    if(!is_string($msg))$msg = json_encode($msg,$trace,$debug);
    logger::info("ERROR: $msg");
    return false;
  }
  
  
  static public function warn($msg, $trace=true, $debug=false){
    if(!is_string($msg))$msg = json_encode($msg,$trace,$debug);
    logger::info("WARNING: $msg");
    return $msg;
  }
  
  
  
}
