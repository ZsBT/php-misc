<?php   /*

  Simple class to log something somewhere
        https://github.com/ZsBT
  
  Example: 
    logger::debug(  array("debug"=>23)  );
    logger::info( "some information to note" );
    logger::warn( "this is a warning" );
    logger::error( "server made a boo-boo" );
  */


abstract class logger {
  const DATEFORMAT = "Y-m-d H:i:s"
      ,OUTFILE_DEBUG= "php://stdout"    // can be stderr or file or whatever
      ,OUTFILE_INFO = "php://stdout"    // as above
      ,OUTFILE_WARN = "php://stdout"    // as above
      ,OUTFILE_ERROR= "php://stdout"    // as above
    ; 

  static private function printout($type, $msg, $trace){
    // format to JSON if not string
    $msg = (is_string($msg)? $msg:json_encode($msg) );
    // strip newlines
    $msg = str_replace("\r", "", $msg);
    $msg = str_replace("\n", "\\n", $msg);
    $line = sprintf("%s $type %s ", date(logger::DATEFORMAT), $msg);

    // include IP if exists
    if( isset($_SERVER["REMOTE_ADDR"]) )$line.=sprintf("<%s>", $_SERVER["REMOTE_ADDR"]);

    // more details here
    if( $trace ){
      $trca = debug_backtrace();
      array_shift($trca);
      foreach($trca as $trc){
        $ob = (object)$trc;
        $fun = $ob->function;
        if($ob->file == __FILE__ ) continue;
//        $fun.= json_encode($ob->args);
        $fun.=sprintf("@%s:{$ob->line}",basename($ob->file));

        $line.= "$fun; ";
        break;
      }
    }

    // where to put text
    switch($type){
      case "DEBUG": $outfile = logger::OUTFILE_DEBUG; break;
      case "WARN": $outfile = logger::OUTFILE_WARN; break;
      case "ERROR": $outfile = logger::OUTFILE_ERROR; break;
      default: $outfile = logger::OUTFILE_INFO; break;
    }
    
    return file_put_contents($outfile, "$line\n", FILE_APPEND);
  }
  
  static public function debug($msg, $trace=true){
    return logger::printout("DEBUG", $msg, $trace);
  }
  
  static public function info($msg, $trace=false){
    return logger::printout("INFO", $msg, $trace);
  }
  
  static public function warn($msg, $trace=false){
    return logger::printout("WARN", $msg, $trace);
  }
  
  static public function error($msg, $trace=false){
    return logger::printout("ERROR", $msg, $trace);
  }
  
}
