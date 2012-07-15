<?php
/**
 * 多说插件 类定义
 *
 * @version		$Id: duoshuo.php 0 10:17 2012-4-27 
 * @author 		allen 
 * @package		DedeCMS.DUOSHUO
 * @copyright	Copyright (c) 2012 - , Duoshuo, Inc.
 * @link		http://www.duoshuo.com
 */
class Duoshuo_Exception extends Exception{
	const SUCCESS		= 0;
	const ENDPOINT_NOT_VALID = 1;
	const MISSING_OR_INVALID_ARGUMENT = 2;
	const ENDPOINT_RESOURCE_NOT_VALID = 3;
	const NO_AUTHENTICATED = 4;
	const INVALID_API_KEY = 5;
	const INVALID_API_VERSION = 6;
	const CANNOT_ACCESS = 7;
	const OBJECT_NOT_FOUND = 8;
	const API_NO_PRIVILEGE = 9;
	const OPERATION_NOT_SUPPORTED = 10;
	const API_KEY_INVALID = 11;
	const NO_PRIVILEGE = 12;
	const RESOURCE_RATE_LIMIT_EXCEEDED = 13;
	const ACCOUNT_RATE_LIMIT_EXCEEDED = 14;
	const INTERNAL_SERVER_ERROR = 15;
	const REQUEST_TIMED_OUT = 16;
	const NO_ACCESS_TO_THIS_FEATURE = 17;
	const INVALID_SIGNATURE = 18;

	const USER_DENIED_YOUR_REQUEST = 21;
	const EXPIRED_TOKEN = 22;
	const REDIRECT_URI_MISMATCH = 23;
	const DUPLICATE_CONNECTED_ACCOUNT = 24;

	const PLUGIN_DEACTIVATED = 30;
}

include_once DEDEROOT.'/plus/duoshuo/duoshuo_client.php';

function rfc3339_to_mysql($string){
	global $cfg_cli_time;
	if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
		return DateTime::createFromFormat(DateTime::RFC3339, $string)->format('Y-m-d H:i:s');
	}
	else{
		$timestamp = strtotime($string);
		return gmdate('Y-m-d H:i:s', $timestamp  + $cfg_cli_time * 3600);
	}
}

function rfc3339_to_mysql_gmt($string){
	if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
		return DateTime::createFromFormat(DateTime::RFC3339, $string)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
	}
	else{
		$timestamp = strtotime($string);
		return gmdate('Y-m-d H:i:s', $timestamp);
	}
}


function current_url()
{
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self     = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info    = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
	return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}


class Duoshuo{
	
	const DOMAIN = 'duoshuo.com';
	const STATIC_DOMAIN = 'static.duoshuo.com';
	const VERSION = '0.2.0';
	
	static $commentTag = '{dede:duoshuo/}';
	
	/**
	 *
	 * @var string
	 */
	static $prefix = 'duoshuo_';
	
	/**
	 *
	 * @var string
	 */
	static $shortName;
	
	/**
	 *
	 * @var string
	 */
	static $secret;
	
	/**
	 * 管理后台路径
	 * @var string
	 */
	static $adminPath;

	/**
	 * 是否开启评论实时反向同步回本地
	 * @var bool
	 */
	static $syncToLocal = true;
	
	/**
	 * 是否开启SEO优化
	 * @var bool
	 */
	static $seoEnabled = false;
	
	/**
	 * 
	 */	
	static $initialized = false;
	/**
	 *
	 * @var array
	 */
	static $errorMessages = array();
	
	static $EMBED = false;
	
	static function init()
	{
		//从数据库获取结果
		self::$shortName = self::getConfig('short_name');
		self::$secret = self::getConfig('secret');
		self::$adminPath = self::getConfig('admin_path');
		self::$seoEnabled = self::getConfig('seo_enabled') !== NULL ? self::getConfig('seo_enabled') : self::$seoEnabled;
		self::$initialized = true;
	}
	
	/**
	 * 保存多说设置
	 * @param 键 $key
	 * @param 值 $value
	 * @param 键名 $info
	 * @param 类型 $type
	 * @param 组别 $groupid
	 */
	static function saveConfig($key, $value, $info = NULL,$type = NULL,$groupid = NULL){
		global $dsql;
		$oldvalue = self::getConfig($key);
		if($oldvalue===NULL){
			$info = isset($info) ? $info : '多说设置项'; //默认值
			$type = isset($type) ? $type : 'string';	//默认值
			$groupid = isset($groupid) ? $groupid : 8;	//默认值
			
			$sql = "INSERT into #@__sysconfig (varname, value, info, type, groupid) values ('".
			self::duoshuoKey($key)."','".$value."','".$info."','".$type."',".$groupid.")";
		}else{
			$sql = "UPDATE #@__sysconfig SET "
			.(" value = '".$value."'")
			.(isset($info) ? ",info = '".$info."',": "")
			.(isset($type) ? ",type = '".$type."',": "")
			.(isset($groupid) ? ",groupid = '".$groupid."' ": "")
			." WHERE varname = '".self::duoshuoKey($key)."'";
		}
		$config = $dsql->ExecuteNoneQuery($sql);
		
		return $config;
	}
	
	static function getConfig($key){
		global $dsql;
		$sql = "SELECT value FROM #@__sysconfig WHERE varname = '".self::duoshuoKey($key)."'";
		$value = $dsql->GetOne($sql);
		if(is_array($value)){
			return $value['value'];
		}else{
			return NULL;
		}
	}
	
	static function duoshuoKey($key){
		return self::$prefix.$key;
	}
	
	/**
	 *
	 * @return DuoshuoClient
	 */
	static function getClient($userId = 0){	//如果不输入参数，就是游客
		$remoteAuth = null;//self::remoteAuth($userId);
	
		/*if ($userId !== null){
			$accessToken = self::getUserMeta($userId, 'duoshuo_access_token');
	
			if (is_string($accessToken))
				return new DuoshuoClient(self::$shortName, self::$secret, $remoteAuth, $accessToken);
		}*/
		return new DuoshuoClient(self::$shortName, self::$secret, $remoteAuth);
	}
	/*
	static function remoteAuth($userId = null){	// null 代表当前登录用户，0代表游客
		if ($userId === null)
			$current_user = wp_get_current_user();
		elseif($userId != 0)
		$current_user = get_user_by( 'id', $userId);
	
		if (isset($current_user) && $current_user->ID) {
			$avatar_tag = get_avatar($current_user->ID);
			$avatar_data = array();
			preg_match('/(src)=((\'|")[^(\'|")]*(\'|"))/i', $avatar_tag, $avatar_data);
			$avatar = str_replace(array('"', "'"), '', $avatar_data[2]);
	
			$user_data = array(
					'id' => $current_user->ID
					'name' => $current_user->display_name,
					'avatar' => $avatar,
					'email' => $current_user->user_email,
			);
		}
		else{
			$user_data = array();
		}
		$message = base64_encode(json_encode($user_data));
		$time = time();
		return $message . ' ' . self::hmacsha1($message . ' ' . $time, self::$secret) . ' ' . $time;
	}
	
	static function buildQuery(){
		return array(
				'short_name'	=>	self::$shortName,
				'sso'	=>	array(
						'login'=>	site_url('wp-login.php', 'login') .'?action=duoshuo_login',
						'logout'=>	htmlspecialchars_decode(wp_logout_url(), ENT_QUOTES),
				),
				'remote_auth'	=>	self::remoteAuth(),
		);
	}
	*/
	
	static function checkDefaultSettings($adminPath){
		$duoshuoDefaultSettings = array(
				'short_name'	=>	array(
						'value' =>	'',
						'info'	=>	'多说二级域名',
						'type'	=>	'string',
				),
				'secret'	=>	array(
						'value' =>	'',
						'info'	=>	'多说站点密钥',
						'type'	=>	'string',
				),
				'admin_path'	=>	array(
						'value' =>	$adminPath,
						'info'	=>	'Dede管理后台路径，多说仅在Dedecms插件中用于评论的回流，并不会告知多说服务器',
						'type'	=>	'string',
				),
				'sync_lock'		=>	array(
						'value'	=>	0,
						'info'	=>	'多说正在同步时间(0表示同步正常完成)',
						'type'	=>	'int',
				),
				'last_sync'	=>	array(
						'value'	=>	0,
						'info'	=>	'已完成的最后同步时间戳',
						'type'	=>	'int',
				),
				'seo_enabled'	=>	array(
						'value'	=>	0,
						'info'	=>	'开启SEO优化',
						'type'	=>	'int',
				),
		);
		
		foreach ($duoshuoDefaultSettings as $key => $defaultSetting){
			$setting = self::getConfig($key);
			if(!isset($setting) || $setting === NULL){
				self::saveConfig($key, $defaultSetting['value'],
				$defaultSetting['info'], $defaultSetting['type']);
			}
		}
		
	}
	
	/**
	 *  打包选项信息
	 *  例如：pageckageOptions();
	 *
	 * @access    public
	 * @return    array
	 */
	static function packageOptions()
	{
		global $cfg_webname,$cfg_description,$cfg_basehost,$cfg_indexurl,$cfg_adminemail,$cur_url;
		$params = array(
				'name'			=>	htmlspecialchars_decode($cfg_webname),
				'short_name'	=>	self::$shortName,
				'system'		=>	'dedecms',
				'callback'		=>	current_url(),
				'plugin_dir_url' => $cfg_basehost.'/plus/duoshuo/',
				'plugin_version' => self::VERSION,
				'url'			=>	$cfg_basehost.$cfg_indexurl,
				'siteurl'		=>	$cfg_basehost,
				'admin_email'	=>	$cfg_adminemail,
				//'timezone'	=> $cfg_cli_time,
		);
		return $params;
	}
	
	static function syncCommentsToLocal(){
		$syncLock = self::getConfig('sync_lock');//检查是否正在同步评论 同步完成后该值会置0
		if(!isset($syncLock) || $syncLock > time()- 900){//正在或15分钟内发生过写回但没置0
			//return;
		}
		try{
			self::saveConfig('sync_lock',  time());
			
			$last_sync = self::getConfig('last_sync');
			
			$limit = 50;
			
			$params = array(
				'since' => $last_sync,
				'limit' => $limit,
				'order' => 'asc',
				'sources'=>'duoshuo,anonymous'
			);
			
			$client = self::getClient();
			
			$posts = array();
			$aidList = array();
			$max_sync_date = 0;
			
			do{
				$response = $client->request('GET', 'log/list', $params);
			
				$count = count($response['response']);
				
				if ($count){
					//合并
					$aidList = array_merge(self::_syncCommentsToLocal($response['response']),$aidList);
					//唯一化
					$aidList = array_unique($aidList);
					
					foreach($response['response'] as $log)
						if ($log['date'] > $max_sync_date)
							$max_sync_date = $log['date'];
					$params['since'] = $max_sync_date;
				}
			} while ($count == $limit);//如果返回和最大请求条数一致，则再取一次
			
			if ($max_sync_date > $last_sync)
				self::saveConfig('last_sync', $max_sync_date);
			
			//更新静态文件
			if(Duoshuo::$syncToLocal && Duoshuo::$seoEnabled){
				foreach($aidList as $aid){
					$startid = $aid;
					$endid = $aid;
					include_once(Duoshuo::$adminPath."/makehtml_archives_action.php");
				}
			}
			
			self::saveConfig('sync_lock',  0);
		}
		catch(Exception $ex){
			//Showmsg($e->getMessage());
		}
	}
	
	static function sendException($e){
		$response = array(
				'code'	=>	$e->getCode(),
				'errorMessage'=>$e->getMessage(),
		);
		echo json_encode($response);
		exit;
	}
	
	/**
	 * 从服务器pull评论到本地
	 *
	 * @param array $posts
	 */
	static function _syncCommentsToLocal($logs){
		global $dsql;
		$approvedMap = array(
				'pending'	=>	'0',
				'approved'	=>	'1',
				'deleted'	=>	'2',
				'spam'		=>	'3',
				'thread-deleted'=>'4',
		);
		$actionMap = array(
			'create' => '0',
			'update' => '0',
			'approve' => '1',
			'delete' => '2',
			'spam' => '3',
			'delete-forever' => '4',
		);
		$aidList = array();
		foreach($logs as $log){
			switch($log['action']){
				case 'create':
					//查找同步记录
					$sql = "SELECT * FROM duoshuo_commentmeta WHERE post_id = ".$log['meta']['post_id'];
					$synced = $dsql->GetOne($sql);
					if(is_array($synced)){//create操作的评论，没同步过才处理
						continue;
					}
					if(!empty($log['meta']['source_thread_id'])){
						$aid = $log['meta']['source_thread_id'];
						$sql = "SELECT title FROM #@__archives WHERE id = ".$aid;
						$thread = $dsql->GetOne($sql);
						if(is_array($thread)){
							$title = $thread['title'];
							//注意防止sql注入 title,author_name,message
							$sql = "INSERT INTO #@__feedback (aid,typeid,username,arctitle,ip,ischeck,dtime,mid,bad,good,ftype,face,msg) VALUES ("
							.$log['meta']['source_thread_id'].",1,'".trim(strip_tags($log['meta']['author_name']))."','".$title."','".$log['meta']['ip']."',".$approvedMap[$log['meta']['status']].",".strtotime($log['meta']['created_at']).",1,0,0,'feedback',1,'".$log['meta']['message']."')";
							$dsql->ExecuteNoneQuery($sql);
							$last_id = $dsql->GetLastID();
							$sql = "INSERT INTO duoshuo_commentmeta (post_id,cid) VALUES (".$log['meta']['post_id'].",".$last_id.")";
							$dsql->ExecuteNoneQuery($sql);
							array_push($aidList,$aid);
						}//没有文章直接略去评论
					}
					break;
				case 'approve':
				case 'spam':
				case 'delete':
					foreach($log['meta'] as $postId){
						$sql = "SELECT title FROM duoshuo_commentmeta WHERE post_id = ".$postId;
						$synced = $dsql->GetOne($sql);
						if(!is_array($synced)){//非create操作的评论，同步过才处理
							continue;
						}
						$sql = "SELECT * FROM #@__feedback WHERE id = ".$synced['cid'];
						$comment = $dsql->GetOne($sql);
						if(!is_array($comment)){
							continue;
						}
						$sql = "UPDATE #@__feedback SET ischeck = ".$actionMap[$log['action']] ." WHERE id = " . $synced['cid'];
						$dsql->ExecuteNoneQuery($sql);
						array_push($aidList,$comment['aid']);
					}
					break;
				case 'delete-forever':
					$log['meta'] = json_decode($log['meta'],false);
					foreach($log['meta'] as $postId){
						$sql = "SELECT title FROM duoshuo_commentmeta WHERE post_id = ".$postId;
						$synced = $dsql->GetOne($sql);
						if(!is_array($synced)){//非create操作的评论，同步过才处理
							continue;
						}
						$sql = "SELECT * FROM #@__feedback WHERE id = ".$synced['cid'];
						$comment = $dsql->GetOne($sql);
						if(!is_array($comment)){
							continue;
						}
						$sql = "DELETE FROM #@__feedback  " . $actionMap[$log['action']] . " WHERE id = " . $synced['cid'];
						$dsql->ExecuteNoneQuery($sql);
						array_push($aidList,$comment['aid']);
					}
					break;
				case 'update'://现在并没有update操作的逻辑
				default:
					break;
			}
		}
		return $aidList;
	}
}