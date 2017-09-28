<?php

namespace ZsBT\misc\Solus;

class Result {
	
	function __construct($response){
		if(!$response) throw new \Exception(self::EX_EMPTYRESPONSE." - {$this->host}");
		
		preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $response, $match);
		foreach ($match[1] as $x => $y)
			$this->{$y} = $match[2][$x];
		
		if($this->status=='error')throw new \Exception($this->statusmsg);
		
		if(isset($this->ipaddr))
			$this->ipaddr = explode(",",$this->ipaddr);
		
		foreach(['hdd','mem','bw']as $attr)
			if(isset($this->{$attr}))
				list($this->{$attr}->total, $this->{$attr}->used, $this->{$attr}->free, $this->{$attr}->percentused) = explode(",",$this->{$attr});
	}
	
	const EX_EMPTYRESPONSE = "Got no response";
}

