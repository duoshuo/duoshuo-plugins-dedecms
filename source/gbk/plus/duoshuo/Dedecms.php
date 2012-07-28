<?php

class Duoshuo_Dedecms extends Duoshuo_Abstract{
	
	const VERSION = '0.2.0';
	
	public static $commentTag = '{dede:duoshuo/}';
	
	public static $approvedMap = array(
		'pending' => '0',
		'approved' => '1',
		'deleted' => '2',
		'spam' => '3',
		'thread-deleted'=>'4',
	);
	public static $actionMap = array(
		'create' => '0',
		'update' => '0',
		'approve' => '1',
		'delete' => '2',
		'spam' => '3',
		'delete-forever' => '4',
	);
	/**
	 *
	 * @var array
	 */
	public static $errorMessages = array();
	
	public static $EMBED = false;
	
	public static function getInstance(){
		if (self::$_instance === null)
			self::$_instance = new self();
		return self::$_instance;
	}
	
	public static function timezone(){
		global $cfg_cli_time;
		return $cfg_cli_time;
	}
	
	/**
	 * 保存多说设置
	 * @param 键 $key
	 * @param 值 $value
	 * @param 键名 $info
	 * @param 类型 $type
	 * @param 组别 $groupid
	 */
	public function updateOption($key, $value, $info = NULL,$type = NULL,$groupid = NULL){
		global $dsql;
		$oldvalue = $this->getOption($key);
		if($oldvalue===NULL){
			$info = isset($info) ? $info : '多说设置项'; //默认值
			$type = isset($type) ? $type : 'string';	//默认值
			$groupid = isset($groupid) ? $groupid : 8;	//默认值
			
			$sql = "INSERT into #@__sysconfig (varname, value, info, type, groupid) values ('duoshuo_$key','$value','$info','$type',$groupid)";
		}
		else{
			$sql = "UPDATE #@__sysconfig SET "
			.(" value = '$value'")
			.(isset($info) ? ",info = '$info',": "")
			.(isset($type) ? ",type = '$type',": "")
			.(isset($groupid) ? ",groupid = '$groupid' ": "")
			." WHERE varname = 'duoshuo_$key'";
		}
		$option = $dsql->ExecuteNoneQuery($sql);
		$this->options[$key] = $value;
		return $option;
	}
	
	public function getOption($key){
		if(isset($this->options[$key])){
			return $this->options[$key];
		}else{
			global $dsql;
			$sql = "SELECT value FROM #@__sysconfig WHERE varname = 'duoshuo_$key'";
			$value = $dsql->GetOne($sql);
			if(is_array($value)){
				$this->options[$key] = $value['value'];
				return $value['value'];
			}
			else{
				return NULL;
			}
		}
	}
	
	public function checkDefaultSettings(){
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
			'sync_lock'		=>	array(
				'value'	=>	0,
				'info'	=>	'多说正在同步时间(0表示同步正常完成)',
				'type'	=>	'int',
			),
			'last_sync'	=>	array(
				'value'	=>	0,
				'info'	=>	'已完成的最后同步记录id',
				'type'	=>	'int',
			),
			'seo_enabled'	=>	array(
				'value'	=>	1,
				'info'	=>	'开启SEO优化',
				'type'	=>	'int',
			),
		);
		
		//sync_to_local
		
		foreach ($duoshuoDefaultSettings as $key => $defaultSetting){
			$setting = $this->getOption($key);
			if(!isset($setting) || $setting === NULL){
				$this->updateOption($key, $defaultSetting['value'],
				$defaultSetting['info'], $defaultSetting['type']);
			}
		}
	}
	
	public static function currentUrl(){
		$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
		$php_self	 = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
		$path_info	= isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
		$relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
		return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
	}
	
	/**
	 *  打包选项信息
	 *  例如：pageckageOptions();
	 *
	 * @access	public
	 * @return	array
	 */
	public function packageOptions()
	{
		global $cfg_webname,$cfg_description,$cfg_basehost,$cfg_indexurl,$cfg_adminemail,$cur_url,$cfg_cli_time;
		$params = array(
			'name'			=>	htmlspecialchars_decode($cfg_webname),
			'short_name'	=>	$this->options['short_name'],
			'system'		=>	'dedecms',
			'callback'		=>	self::currentUrl(),
			'local_api_url' => $cfg_basehost.'/plus/duoshuo/api.php',
			'plugin_version' => self::VERSION,
			'url'			=>	$cfg_basehost.$cfg_indexurl,
			'siteurl'		=>	$cfg_basehost,
			'admin_email'	=>	$cfg_adminemail,
			'timezone'		=>	'UTC' . ($cfg_cli_time>=0 ? '+' : '') . $cfg_cli_time,
			'sync_log'		=>	'1',
		);
		return $params;
	}
	
	static function sendException($e){
		$response = array(
			'code'	=>	$e->getCode(),
			'errorMessage'=>$e->getMessage(),
		);
		echo json_encode($response);
		exit;
	}
	
	public function createPost($meta){
		global $dsql;
		//查找同步记录
		$postId = $meta['post_id'];
		$sql = "SELECT * FROM duoshuo_commentmeta WHERE post_id = $postId";
		$synced = $dsql->GetOne($sql);
		if(is_array($synced)){//create操作的评论，没同步过才处理
			return null;
		}
		if(!empty($meta['source_thread_id'])){
			$aid = $meta['source_thread_id'];
			$sql = "SELECT title FROM #@__archives WHERE id = $aid";
			$thread = $dsql->GetOne($sql);
			if(is_array($thread)){
				//注意防止sql注入 title,author_name,message
				$title = addslashes($thread['title']);
				$sourceThreadId = $meta['source_thread_id'];
				$author_name = addslashes(iconv("UTF-8","GBK",trim(strip_tags($meta['author_name']))));
				$ip = $meta['ip'];
				$ischeck = self::$approvedMap[$meta['status']];
				$dtime = strtotime($meta['created_at']);
				$message = addslashes(iconv("UTF-8","GBK",strip_tags($meta['message'])));
				$sql = "INSERT INTO #@__feedback (aid,typeid,username,arctitle,ip,ischeck,dtime,mid,bad,good,ftype,face,msg) VALUES ("
				."$sourceThreadId,1,'$author_name','$title','$ip',$ischeck,'$dtime',1,0,0,'feedback',1,'$message')";
				$dsql->ExecuteNoneQuery($sql);
				$last_id = $dsql->GetLastID();
				$sql = "INSERT INTO duoshuo_commentmeta (post_id,cid) VALUES ($postId,$last_id)";
				$dsql->ExecuteNoneQuery($sql);
				return array($aid);
			}//没有文章直接略去评论
		}
		return null;
	}
	
	public function moderatePost($action, $postIdArray){
		global $dsql;
		$aidList = array();
		foreach($postIdArray as $postId){
			$sql = "SELECT * FROM duoshuo_commentmeta WHERE post_id = $postId";
			$synced = $dsql->GetOne($sql);
			if(!is_array($synced)){//非create操作的评论，同步过才处理
				continue;
			}
			$cid = $synced['cid'];
			$sql = "SELECT * FROM #@__feedback WHERE id = $cid";
			$comment = $dsql->GetOne($sql);
			if(!is_array($comment)){
				continue;
			}
			$ischeck = self::$actionMap[$action];
			$sql = "UPDATE #@__feedback SET ischeck = $ischeck WHERE id = $cid";
			$dsql->ExecuteNoneQuery($sql);
			$aidList[] = $comment['aid'];
		}
		return $aidList;
	}
	
	public function deleteForeverPost($postIdArray){
		global $dsql;
		$aidList = array();
		foreach($postIdArray as $postId){
			$sql = "SELECT * FROM duoshuo_commentmeta WHERE post_id = ".$postId;
			$synced = $dsql->GetOne($sql);
			if(!is_array($synced)){//非create操作的评论，同步过才处理
				continue;
			}
			$cid = $synced['cid'];
			$sql = "SELECT * FROM #@__feedback WHERE id = $cid";
			$comment = $dsql->GetOne($sql);
			if(!is_array($comment)){
				continue;
			}
			$sql = "DELETE FROM #@__feedback WHERE id = $cid";
			$dsql->ExecuteNoneQuery($sql);
			$aidList[] = $comment['aid'];
		}
		return $aidList;
	}
	
	public function refreshThreads($aidList){
		foreach($aidList as $aid){
			echo 0.3;
			$arc = new Archives($aid);
			echo 0.5;
			$arc->MakeHtml();
			echo 1;
		}
	}
	
	public function userData($userId){	// null 代表当前登录用户，0代表游客
		if ($userId === null)
			$current_user = wp_get_current_user();
		elseif($userId != 0)
			$current_user = get_user_by( 'id', $userId);
		
		if (isset($current_user) && $current_user->ID) {
			$avatar_tag = get_avatar($current_user->ID);
			$avatar_data = array();
			preg_match('/(src)=((\'|")[^(\'|")]*(\'|"))/i', $avatar_tag, $avatar_data);
			$avatar = str_replace(array('"', "'"), '', $avatar_data[2]);
			
			return array(
				'id' => $current_user->ID,
				'name' => $current_user->display_name,
				'avatar' => $avatar,
				'email' => $current_user->user_email,
			);
		}
		else{
			return array();
		}
	}
}