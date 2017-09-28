<?php	/*

	Simple SolusVM client.
	
	https://github.com/ZsBT/php-misc
	
	*/
	

namespace ZsBT\misc\Solus;

class Client {
	private $host, $key, $hash;
	
	function __construct($host, $key, $hash){
		$this->host = $host;
		$this->key = $key;
		$this->hash = $hash;
	}
	
	public function boot(){ return $this->call('boot'); }
	public function reboot(){ return $this->call('reboot'); }
	public function shutdown(){ return $this->call('shutdown'); }
	public function status(){ return $this->call('status'); }
	public function info(){ return $this->call('info',['hdd'=>true]); }
	
	





	private function call($action, $parms=[]){

		// Url to the client API
		 
		$url = "https://{$this->host}:5656/api/client";
		 
		// Specify the key, hash and action
		 
		$postfields["key"] = $this->key;
		$postfields["hash"] = $this->hash;
		$postfields["action"] = $action; // reboot, shutdown, boot, status
		
		foreach($parms as $pk=>$pv)
			$postfields[$pk] = $pv;
		 
		// Send the query to the solusvm master
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . "/command.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		$data = curl_exec($ch);
		curl_close($ch);
		
		return new Result($data);
	}
	
	
}

