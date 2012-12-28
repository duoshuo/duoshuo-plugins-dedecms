<?php
require 'EasyHttp.php';
require 'EasyHttp/Curl.php';
require 'EasyHttp/Cookie.php';
require 'EasyHttp/Encoding.php';
require 'EasyHttp/Fsockopen.php';
require 'EasyHttp/Proxy.php';
require 'EasyHttp/Streams.php';
require 'EasyHttp/Error.php';
/**
 * 
 * @link http://duoshuo.com/
 * @author shen2
 *
 */
class Duoshuo_Client{
	var $end_point = 'http://api.duoshuo.com/';
	/**
	 * ����ֵ��ʽ
	 * @var string
	 */
	var $format = 'json';
	
	var $userAgent = 'DuoshuoPhpSdk/0.3.0';
	
	var $connecttimeout = 30;
	var $timeout = 60;
	var $shortName;
	var $secret;
	var $accessToken;
	var $http;
	
	function __construct($shortName = null, $secret = null, $remoteAuth = null, $accessToken = null){
		$this->shortName = $shortName;
		$this->secret = $secret;
		$this->remoteAuth = $remoteAuth;
		$this->accessToken = $accessToken;
	}
	
	function getLogList($params){
		return $this->request('GET', 'log/list', $params);
	}
	
	/**
	 * 
	 * @param $method
	 * @param $path
	 * @param $params
	 * @throws Duoshuo_Exception
	 * @return array
	 */
	function request($method, $path, $params = array()){
		$params['short_name'] = $this->shortName;
		$params['secret'] = $this->secret;
		$params['remote_auth'] = $this->remoteAuth;
		
		if ($this->accessToken)
			$params['access_token'] = $this->accessToken;
		
		$url = $this->end_point . $path. '.' . $this->format;
		
		return $this->httpRequest($url, $method, $params);
	}
	
	function httpRequest($url, $method, $params){
		$args = array(
				'method' => $method,    //  GET/POST
				'timeout' => $this->timeout,  //  ��ʱ������
				'redirection' => 5,     //  ����ض������
				'httpversion' => '1.0', //  1.0/1.1
				'user-agent' => $this->userAgent,
				//'blocking' => true,     //  �Ƿ�����
				'headers' 	=> array('Expect'=>''),   //  header��Ϣ
				//'cookies' => array(),   //  ����������ʽ��cookie��Ϣ
				//'compress' => false,    //  �Ƿ�ѹ��
				//'decompress' => true,   //  �Ƿ��Զ���ѹ�����
				'sslverify' => true,
				//'stream' => false,
				//'filename' => null      //  ���stream = true��������趨һ����ʱ�ļ���
		);
		switch($method){
			case 'GET':
				$url .= '?' . http_build_query($params, null, '&');	// overwrite arg_separator.output
				break;
			case 'POST':
				$headers = array();
				$args['body'] =  http_build_query($params);
				break;
			default:
		}
		$http = new EasyHttp();
		$response = $http->request($url, $args);
		if (isset($response->errors)){
			if (isset($response->errors['http_request_failed'])){
				$message = $response->errors['http_request_failed'][0];
				if ($message == 'name lookup timed out')
					$message = 'DNS������ʱ�������Ի��������������������(DNS)���á�';
				elseif (stripos($message, 'Could not open handle for fopen') === 0)
					$message = '�޷���fopen����������Ի���ϵ��˵����Ա��http://dev.duoshuo.com/';
				elseif (stripos($message, 'Couldn\'t resolve host') === 0)
					$message = '�޷�����duoshuo.com�����������Ի��������������������(DNS)���á�';
				elseif (stripos($message, 'Operation timed out after ') === 0)
					$message = '������ʱ�������Ի���ϵ��˵����Ա��http://dev.duoshuo.com/';
				throw new Duoshuo_Exception($message, Duoshuo_Exception::REQUEST_TIMED_OUT);
			}
            else
            	throw new Duoshuo_Exception('���ӷ�����ʧ��, ��ϸ��Ϣ��' . json_encode($response->errors), Duoshuo_Exception::REQUEST_TIMED_OUT);
		}

		$json = json_decode($response['body'], true);
		return $json === null ? $response['body'] : $json;
	}
	
	/**
	 * 
	 * @param string $type
	 * @param array $keys
	 */
	function getAccessToken( $type, $keys ) {
		$params = array(
			'client_id'	=>	$this->shortName,
			'client_secret' => $this->secret,
		);
		
		switch($type){
		case 'token':
			$params['grant_type'] = 'refresh_token';
			$params['refresh_token'] = $keys['refresh_token'];
			break;
		case 'code':
			$params['grant_type'] = 'authorization_code';
			$params['code'] = $keys['code'];
			$params['redirect_uri'] = $keys['redirect_uri'];
			break;
		case 'password':
			$params['grant_type'] = 'password';
			$params['username'] = $keys['username'];
			$params['password'] = $keys['password'];
			break;
		default:
			throw new Duoshuo_Exception("wrong auth type");
		}

		$accessTokenUrl = 'http://api.duoshuo.com/oauth2/access_token';
		$response = $this->httpRequest($accessTokenUrl, 'POST', $params);
		
		$token = $response;
		if ( is_array($token) && !isset($token['error']) ) {
			$this->access_token = $token['access_token'];
			if (isset($token['refresh_token'])) //	����û��refresh_token
				$this->refresh_token = $token['refresh_token'];
		} else {
			throw new Duoshuo_Exception("get access token failed." . $token['error']);
		}
		
		return $token;
	}
	
	/**
	 * 
	 * @param array $user_data
	 */
	function remoteAuth($user_data){
	    $message = base64_encode(json_encode($user_data));
	    $time = time();
	    return $message . ' ' . self::hmacsha1($message . ' ' . $time, $this->secret) . ' ' . $time;
	}
	
	// from: http://www.php.net/manual/en/function.sha1.php#39492
	// Calculate HMAC-SHA1 according to RFC2104
	// http://www.ietf.org/rfc/rfc2104.txt
	static function hmacsha1($data, $key) {
		if (function_exists('hash_hmac'))
			return hash_hmac('sha1', $data, $key);
		
	    $blocksize=64;
	    $hashfunc='sha1';
	    if (strlen($key)>$blocksize)
	        $key=pack('H*', $hashfunc($key));
	    $key=str_pad($key,$blocksize,chr(0x00));
	    $ipad=str_repeat(chr(0x36),$blocksize);
	    $opad=str_repeat(chr(0x5c),$blocksize);
	    $hmac = pack(
	                'H*',$hashfunc(
	                    ($key^$opad).pack(
	                        'H*',$hashfunc(
	                            ($key^$ipad).$data
	                        )
	                    )
	                )
	            );
	    return bin2hex($hmac);
	}
}
