<?php

class Duoshuo_Abstract {
	const DOMAIN = 'duoshuo.com';
	const STATIC_DOMAIN = 'static.duoshuo.com';
	
	protected static $_instance = null;
	
	/**
	 *
	 * @var string
	 */
	public $shortName;
	
	/**
	 *
	 * @var string
	 */
	public $secret;
	
	
	public function __construct(){
		//从数据库获取结果
		$this->shortName = $this->getOption('short_name');
		$this->secret = $this->getOption('secret');
		
		//self::$seoEnabled = $this->getOption('seo_enabled') !== NULL ? $this->getOption('seo_enabled') : self::$seoEnabled;
	}
	
	public function rfc3339_to_mysql($string){
		if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
			return DateTime::createFromFormat(DateTime::RFC3339, $string)->format('Y-m-d H:i:s');
		}
		else{
			$timestamp = strtotime($string);
			return gmdate('Y-m-d H:i:s', $timestamp  + $this->timezone('gmt_offset') * 3600);
		}
	}
	
	public function rfc3339_to_mysql_gmt($string){
		if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
			return DateTime::createFromFormat(DateTime::RFC3339, $string)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
		}
		else{
			$timestamp = strtotime($string);
			return gmdate('Y-m-d H:i:s', $timestamp);
		}
	}
	
	
	/**
	 *
	 * @return Duoshuo_Client
	 */
	public function getClient($remoteAuth = null){	//如果不输入参数，就是游客
		return new Duoshuo_Client($this->shortName, $this->secret, $remoteAuth);
	}
	
	/**
	 * 获取设置
	 */
	public function getOption($key){
		//依赖子类实现
	}
}
