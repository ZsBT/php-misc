<?php	/*

    sharepoint list support
    depends on thybag/php-sharepoint-lists-api and zsbt/misc
    
    public function cachedList($ListName)
    public function cachedFile($url,$TTL=null)
    public function cachedAttachments($Listname,$itemid)

    
    */
    

namespace ZsBT\misc;

class zSharePoint extends \Thybag\SharePointAPI {

    const AUTHMODE = "SPONLINE";
    
    

    // do not initialize parent class yet
    function __construct($Args){
        $this->__args = $Args;
        $this->Name = str_replace("\\","_",get_called_class());
        $this->cache = new oCache($Args["CacheDIR"], $Args["CacheTTL"], $this->Name );
    }
    
    
    // create instance from parent only if needed
    private function instance(){
        if($this->__instance)return $this->__instance;
        
        $Args = $this->__args;
        parent::__construct($Args['UserName'], $Args['Password'], $Args['WSDL'], self::AUTHMODE );
        $this->__instance = $this;
    }


    // read the cached version of the list. if not exists yet, retrieve online and store in cache.
    public function cachedList($ListName){
    	$me = $this;
    	
    	return $this->cache->get( $this->Name.$ListName, function($key,$ob) use($me,$ListName) {
		$me->instance();
		zSharePointLogger::write(LOG_DEBUG, "read list $ListName");
		$list = $me->read($ListName);
		foreach ($list as $i=>$item ){
			$newitem = [];
			foreach($item as $key=>$val){
				$newitem[ str_replace("_x0020_", "_", $key) ] = $val;
			}
			$list[$i] = $newitem;
		}
		return $list;
    	});
        
    }
    
    
    
    
    // retrieve a SP file - from cache if possible
    public function cachedFile($url,$TTL=null){
    	$me = $this;
    	
    	return $this->cache->get( $this->Name.$url, function() use ($me,$url,$TTL) {
		zSharePointLogger::write(LOG_DEBUG, "read file $url");
    		$me->instance();
    		return @$this->getFile($url, $TTL);
    	});
    
    }


    // attachment list (URLs)
    public function cachedAttachments($Listname,$itemid){
    	$me = $this;
    	return $this->cache->get( $this->Name.$Listname.$itemid, function() use ($me,$Listname,$itemid){
		zSharePointLogger::write(LOG_DEBUG, "read attachmentlist $itemid of $Listname");
		$me->instance();
		return @$this->getAttachments($Listname,$itemid );
    	});
    	
    	// TODEL
    	if($ret = @Cache::get($Listname.$itemid) ) return $ret;
    	Cache::set($Listname.$itemid, $data);
    	return $data;
    }
    


    // retrieve a SP file - online
    private function getFile($url,$method="GET"){
        if(!isset($this->sponline))
            $this->sponline = new SPonline($this->__args['UserName'],$this->__args['Password'],$this->__args['WSDL']);
        
        $body = $this->sponline->getfile($url,$method);
        $head = $this->sponline->header;
        
        return $body;
        
    }
    
    
    
    

    
}




abstract class zSharePointLogger {
	public static $LOGFILE = NULL;
	public static $TIMEFORMAT = "Y-m-d H:i:s";
	public static $ADMINEMAIL = NULL;
	
	function write($loglevel,$message){
		$fmsg = sprintf("[%s] %s\n", date(self::$TIMEFORMAT), $message );
		if(self::$LOGFILE) 
			return file_put_contents(self::$LOGFILE, $fmsg, FILE_APPEND ); 
		else
			return syslog($loglevel,$message);
	}
	
	function assert($exception){
		self::write(LOG_ERR, $exception);
		if(self::$ADMINEMAIL)return mail(self::$ADMINEMAIL, "Critical Sharepoint Error: ".$exception->getmessage(), $exception );
		exit(1);
	}
}



// helper class for attachment retrieval
class SPonline {

	private $authCookies=false;
        function getCookies(){
            return $authCookies;
        }
	

	function __construct($username,$password,$location=false){
		$this->location = $location;
		$this->{"_login"} = $username;
		$this->{"_password"} = $password;
	}
	
	
	public $header=[];


	function headerParser($ch,$line){
		if(preg_match("/^([^:]+): (.+)/",$line,$ma))
			$this->header[strtolower($ma[1])]=$ma[2];
		return strlen($line);
	}
	
	
	function getfile($url,$method="GET"){
		if(!isset($this->location))$this->location=$url;
		
		// Authenticate with SP online in order to get required authentication cookies
		if (!($this->authCookies)) $this->configureAuthCookies($this->location);

		// Set base headers
		$headers = array();
		
#		$headers[] = "Content-Type: text/xml;";

		$curl = curl_init(str_replace(" ","%20",$url) );

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

		// Send request and auth cookies.
		curl_setopt($curl, CURLOPT_COOKIE, $this->authCookies);

		curl_setopt($curl, CURLOPT_TIMEOUT, 90);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

		// Useful for debugging
		curl_setopt($curl, CURLOPT_VERBOSE,TRUE );
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		
		
#		$curl->parent = &$this;
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, [self,"headerParser"] );


		// Add headers
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		// Init the cURL
		$response = curl_exec($curl);

		// Throw exceptions if there are any issues
		if (curl_errno($curl)) throw new \SoapFault('Receiver', curl_error($curl));
		if ($response == '') throw new \SoapFault('Receiver', "No XML returned");

		// Close CURL
		curl_close($curl);

		return ($response);
	}
	
	
	 
	protected function configureAuthCookies($location) {

		// Get endpoint "https://somthing.sharepoint.com"
		$location = parse_url($location);
		$endpoint = 'https://'.$location['host'];

		// get username & password
		$login = $this->{'_login'};
		$password = $this->{'_password'};

		// Create XML security token request
		$xml = $this->generateSecurityToken($login, $password, $endpoint);

		// Send request and grab returned xml
		$result = $this->authCurl("https://login.microsoftonline.com/extSTS.srf", $xml);

		
		// Extract security token from XML
		$xml = new \DOMDocument();
		$xml->loadXML($result);
		$xpath = new \DOMXPath($xml);

		// Register SOAPFault namespace for error checking
		$xpath->registerNamespace('psf', "http://schemas.microsoft.com/Passport/SoapServices/SOAPFault");

		// Try to detect authentication errors
		$errors = $xpath->query("//psf:internalerror");
		if($errors->length > 0){
			$info = $errors->item(0)->childNodes;
			throw new \Exception($info->item(1)->nodeValue, $info->item(0)->nodeValue);
		}

		$nodelist = $xpath->query("//wsse:BinarySecurityToken");
		foreach ($nodelist as $n){
			$token = $n->nodeValue;
			break;
		}

		if(!isset($token)){
			throw new \Exception("Unable to extract token from authentiction request");
		}

		// Send token to SharePoint online in order to gain authentication cookies
		$result = $this->authCurl($endpoint."/_forms/default.aspx?wa=wsignin1.0", $token, true);

		// Extract Authentication cookies from response & set them in to AuthCookies var
		$this->authCookies = $this->extractAuthCookies($result);
	}

	/**
	 * extractAuthCookies
	 * Extract Authentication cookies from SP response & format in to usable cookie string
	 *
	 * @param $result cURL Response
	 * @return $cookie_payload string containing cookie data.
	 */
	protected function extractAuthCookies($result){

		$authCookies = array();
		$cookie_payload = '';

		$header_array = explode("\r\n", $result);

		// Get the two auth cookies
		foreach($header_array as $header) {
			$loop = explode(":",$header);
			if($loop[0] == 'Set-Cookie') {
				$authCookies[] = $loop[1];
			}
		}

		// Extract cookie name & payload and format in to cURL compatible string
		foreach($authCookies as $payload){
			$e = strpos($payload, "=");
			// Get name
			$name = substr($payload, 0, $e);
			// Get token
			$content = substr($payload, $e+1);
			$content = substr($content, 0, strpos($content, ";"));

			// If not first cookie, add cookie seperator
			if($cookie_payload !== '') $cookie_payload .= '; ';

			// Add cookie to string
			$cookie_payload .= $name.'='.$content;
		}

	  	return $cookie_payload;
	}

	/**
	 * authCurl
	 * helper method used to cURL SharePoint Online authentiction webservices
	 *
	 * @param $url URL to cURL
	 * @param $payload value to post to URL
	 * @param $header true|false - Include headers in response
	 * @return $raw Data returned from cURL.
	 */
	protected function authCurl($url, $payload, $header = false){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,  $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	  	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		if($header)  curl_setopt($ch, CURLOPT_HEADER, true);

		$result = curl_exec($ch);

		// catch error
		if($result === false) {
			throw new \SoapFault('Sender', 'Curl error: ' . curl_error($ch));
		}

		curl_close($ch);

		return $result;
	}

	/**
	 * Get the XML to request the security token
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $endpoint
	 * @return type string
	 */
	protected function generateSecurityToken($username, $password, $endpoint) {
	return <<<TOKEN
    <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"
      xmlns:a="http://www.w3.org/2005/08/addressing"
      xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  <s:Header>
    <a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue</a:Action>
    <a:ReplyTo>
      <a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
    </a:ReplyTo>
    <a:To s:mustUnderstand="1">https://login.microsoftonline.com/extSTS.srf</a:To>
    <o:Security s:mustUnderstand="1"
       xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
      <o:UsernameToken>
        <o:Username>$username</o:Username>
        <o:Password>$password</o:Password>
      </o:UsernameToken>
    </o:Security>
  </s:Header>
  <s:Body>
    <t:RequestSecurityToken xmlns:t="http://schemas.xmlsoap.org/ws/2005/02/trust">
      <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
        <a:EndpointReference>
          <a:Address>$endpoint</a:Address>
        </a:EndpointReference>
      </wsp:AppliesTo>
      <t:KeyType>http://schemas.xmlsoap.org/ws/2005/05/identity/NoProofKey</t:KeyType>
      <t:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue</t:RequestType>
      <t:TokenType>urn:oasis:names:tc:SAML:1.0:assertion</t:TokenType>
    </t:RequestSecurityToken>
  </s:Body>
</s:Envelope>
TOKEN;
	}
	

}


