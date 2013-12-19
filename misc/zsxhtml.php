<?php	/*	(c) 2012 Zsombor 

  Class that produces XHTML easliy. based on SimpleXMLelement
  
  Example: 
    require_once 'zsxhtml.php';
    $x = new ZSXHTML();
    $x->body->h1 = $x->head->title = "Hello World!";
    $x->body->h1["align"]="center";
    $x->addcss("nicebasic.css");
    $x->addjs("hello.js");
    echo $x->asxml();

*/

class zsxe extends simplexmlelement {
  function asnode($accents=false){
    $ret= str_replace('<?xml version="1.0"?>','',$this->asxml());
    if($accents)return $this->accenter($ret);
    return $ret;
  }

  function addflash($url,$width=800,$height=110){
    $o=&$this->object[];
    $o['class']='fles';
    $o['type']='application/x-shockwave-flash';
    $o['data']=$url;$o['width']=$width;$o['height']=$height;
    $p=&$o->param[];$p['name']='loop';$p['value']='true';
    $p=&$o->param[];$p['name']='movie';$p['value']=$url;
    $o->p='no flash plugin in your browser';
  }
  
  public function addCData($cdata_text) {
    $node = dom_import_simplexml($this); 
    $no   = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection($cdata_text)); 
  } 
}


class ZSXHTML {
  var $X;

  function __construct($sxe=0) {
    $this->X=(($sxe===0) ? new zsxe("<html/>"):$sxe );
    $this->X["xmlns"]="http://www.w3.org/1999/xhtml";
    $this->X->head->title='';
    $m=&$this->X->head->meta;
    $m['http-equiv']="content-type";
    $m['content']="text/html;charset=utf-8";
    $this->html = &$this->X;
    $this->head = &$this->X->head;
    $this->body = &$this->X->body;
    $this->addmetaname("generator", "zsxhtml @ ZsPHPlibs (http://github.com/ZsBT/)");
    $this->addmetaname("viewport", "width=device-width, initial-scale=1");
    return $this->X;
  }

  public function ashtml(){
    $s = str_replace('<?xml version="1.0"?>', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">', $this->X->asxml() );
    return $s;
  }
  
  public function render(){header("Content-Type: text/html; charset=UTF8");echo $this->ashtml();}
  public function gzrender(){ob_start("ob_gzhandler");$this->render();}
  
  public function asxml(){return $this->X->asxml();}

  public function asnode(){return str_replace('<?xml version="1.0"?>','',$this->X->asxml());}


  public function addmeta($property,$content){$m=&$this->head->meta[];$m['property']=$property;$m['content']=$content;return $m;}
  public function addmetaname($name,$content){$m=&$this->head->meta[];$m['name']=$name;$m['content']=$content;return $m;}
  public function addmetarefresh($uri,$secs=1){
    $m=&$this->head->meta[];
    $m['http-equiv']='Refresh';
    $m['content']="{$secs}; url={$uri}";
  }

  public function addcss($uri){
    $css=&$this->head->link[];
    $css["rel"]="stylesheet";
    $css["type"]="text/css";
    $css["href"]=$uri;
    return $css;
  }

  public function addjs($uri){
    $js=$this->head->addChild("script",' ');
    $js["type"]="text/javascript";
    $js["src"]=$uri;
    return $js;
  }
  
  public function addicon($uri){$link=&$this->head->link[];$link['rel']='shortcut icon';$link['href']=$uri;}

}