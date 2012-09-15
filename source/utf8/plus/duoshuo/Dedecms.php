<?php

class Duoshuo_Dedecms extends Duoshuo_Abstract{
	
	const VERSION = '0.2.1';
	
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
				'value'	=>	0,
				'info'	=>	'开启SEO优化',
				'type'	=>	'int',
			),
			'log_synced'	=>	array(
				'value'	=>	0,
				'info'	=>	'手动评论备份完成数(不累计)',
				'type'	=>	'int',
			),
			'synchronized'	=>	array(
				'value'	=>	'',
				'info'	=>	'同步到多说完成进度',
				'type'	=>	'string',
			),
			'debug'	=>	array(
				'value'	=>	0,
				'info'	=>	'是否显示出错消息(建议只在多说弹出错误提示时临时开启)',
				'type'	=>	'int',
			)
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
			'local_api_url' => $cfg_basehost.$cfg_cmspath.'/plus/duoshuo/api.php',
			'plugin_version' => self::VERSION,
			'url'			=>	$cfg_basehost.$cfg_cmspath.$cfg_indexurl,
			'siteurl'		=>	$cfg_basehost.$cfg_cmspath,
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
			return array();
		}
		if(!empty($meta['thread_key'])){
			$aid = $meta['thread_key'];
			$sql = "SELECT title FROM #@__archives WHERE id = $aid";
			$thread = $dsql->GetOne($sql);
			if(is_array($thread)){
				//注意防止sql注入 title,author_name,message
				$title = addslashes($thread['title']);
				$threadKey = $meta['thread_key'];
				$author_name = addslashes(trim(strip_tags($meta['author_name'])));
				$ip = $meta['ip'];
				$ischeck = self::$approvedMap[$meta['status']];
				$dtime = strtotime($meta['created_at']);
				$message = addslashes($meta['message']);
				$sql = "INSERT INTO #@__feedback (aid,typeid,username,arctitle,ip,ischeck,dtime,mid,bad,good,ftype,face,msg) VALUES ("
				."$threadKey,1,'$author_name','$title','$ip',$ischeck,'$dtime',1,0,0,'feedback',1,'$message')";
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
			$arc = new Archives($aid);
			if($arc){
				$arc->MakeHtml();
			}
		}
	}
	
	/**
	 * 将文章和评论内容同步到多说，用于以前的评论显示和垃圾评论过滤
	 */
	public function export(){
		global $dsql;
		
		@set_time_limit(0);
		@ini_set('memory_limit', '256M');
		@ini_set('display_errors', $this->getOption('debug'));
		
		$progress = $this->getOption('synchronized');
		
		if (!$progress || is_numeric($progress))//	之前已经完成了导出流程
			$progress = 'thread/0';
		
		list($type, $offset) = explode('/', $progress);
		
		try{
			switch($type){
				case 'thread':
					$limit = 10;
					$dsql->SetQuery("SELECT aid FROM `#@__feedback` where `aid` > $offset group by aid order by aid asc limit $limit");
					$dsql->Execute();
					$aidArray = array();
					while($row = $dsql->GetArray())
					{
						$aidArray[] = $row['aid'];
						
					}
					if(count($aidArray)>0){
						$aids = implode(',', $aidArray);
						$dsql->SetQuery("SELECT * FROM `#@__archives` where `id` in ($aids)");
						$dsql->Execute();
						$threads = array();
						while($row = $dsql->GetArray())
						{
							$arc = new Archives($row['id']);
							$arc->Fields['arcurl'] = $arc->GetTrueUrl(null);
							$threads[] = $arc->Fields;
						}
						$count = $this->exportThreads($threads);
					}else{
						$count = 0;
					} 
					break;
				case 'post':
					$limit = 50;
					$dsql->SetQuery("SELECT cid FROM `duoshuo_commentmeta` group by cid");
					$dsql->Execute();
					$cidFromDuoshuo = array();
					while($row = $dsql->GetArray())
					{
						$cidFromDuoshuo[] = $row['cid'];
					}
					$dsql->SetQuery("SELECT * FROM `#@__feedback` order by id asc limit $offset,$limit ");
					$dsql->Execute();
					$comments = array();
					while($row = $dsql->GetArray())
					{
						$comments[] = $row;
					}
					$count = $this->exportPosts($comments,$cidFromDuoshuo);
					break;
				default:
			}
			
			if ($count == $limit){
				$progress = $type . '/' . ($offset + $limit);
			}
			elseif($type == 'thread')
				$progress = 'post/0';
			elseif($type == 'post')
				$progress = time();
			
			$this->updateOption('synchronized', $progress);
			$response = array(
				'progress'=>$progress,
				'code'	=>	0
			);
			return $response;
		}
		catch(Duoshuo_Exception $e){
			$this->updateOption('synchronized', $progress);
			$this->sendException($e);
		}
	}
	
	/**
	 * 从服务器pull评论到本地 用于dede后台手动同步的ajax请求
	 *
	 * @param array $input
	 */
	public function syncLog(){
		$syncLock = $this->getOption('sync_lock');//检查是否正在同步评论 同步完成后该值会置0
		if(!isset($syncLock) || $syncLock > time()- 900){//正在或15分钟内发生过写回但没置0
			$response = array(
					'code'	=>	Duoshuo_Exception::SUCCESS,
					'response'=> '同步中，最近同步启动时间：'.$this->timeFormat($syncLock),
			);
			return;
		}
	
		$this->updateOption('sync_lock',  time());
	
		$last_sync = $this->getOption('last_sync');
		
		$log_synced = $this->getOption('log_synced');
		
		$limit = 20;
	
		$params = array(
				'since_id' => $last_sync,
				'limit' => $limit,
				'order' => 'asc',
		);
	
		$client = $this->getClient();
	
		$posts = array();
		$affectedThreads = array();
		$max_sync_id = 0;
	
		$response = $client->getLogList($params);
	
		$count = count($response['response']);
			
		foreach($response['response'] as $log){
			switch($log['action']){
				case 'create':
					$affected = $this->createPost($log['meta']);
					break;
				case 'approve':
				case 'spam':
				case 'delete':
					$affected = $this->moderatePost($log['action'], $log['meta']);
					break;
				case 'delete-forever':
					$affected = $this->deleteForeverPost($log['meta']);
					break;
				case 'update'://现在并没有update操作的逻辑
				default:
					$affected = array();
			}
			//合并

			$affectedThreads = array_merge($affectedThreads, $affected);
				
			if ($log['log_id'] > $max_sync_id)
				$max_sync_id = $log['log_id'];
		}
			
		$params['since_id'] = $max_sync_id;
	
		//唯一化
		$aidList = array_unique($affectedThreads);
	
		if ($max_sync_id > $last_sync)
			$this->updateOption('last_sync', $max_sync_id);
	
		$this->updateOption('sync_lock',  0);
	
		//更新静态文件
		if ($this->getOption('seo_enabled'))
			$this->refreshThreads($aidList);
	
		$this->updateOption('sync_lock', 1);
		
		if($count == $limit){//如果返回和最大请求条数一致，则再取一次
			$progress = 'post/'.($log_synced + $count);
			$this->updateOption('log_synced', $log_synced + $count);
		}else{
			$progress = time();
			$this->updateOption('log_synced', 0);
		}
		$response = array(
				'progress'=>$progress,
				'code'	=>	0
		);
		return $response;
	}
	
	function exportThreads($threads){
		if (count($threads) === 0)
			return 0;
	
		$params = array(
				'threads'	=>	array(),
		);
		foreach($threads as $index => $thread){
			$params['threads'][] = $this->packageThread($thread);
		}
	
		$remoteResponse = $this->getClient()->request('POST','threads/import', $params);
	
		return count($threads);
	}
	
	function exportPosts($posts,$postIdsFromDuoshuo){
		if (count($posts) === 0)
			return 0;
	
		$params = array(
				'posts'	=>	array()
		);
	
		foreach($posts as $comment){
			if(!in_array($comment['id'],$postIdsFromDuoshuo))
				$params['posts'][] = $this->packagePost($comment);
		}
		if(count($params['posts']) > 0){
			$remoteResponse = $this->getClient()->request('POST', 'posts/import', $params);
		}
		return count($posts);
	}
	
	public function timeFormat($time) {
		return date('Y-m-d H:i:s', $time);
	}
	
	public function statusFormat($status) {
		switch($status) {
			case 1 : return 'approved';
			case 0 : return 'pending';
		}
	}
	
	public function getTables() {
		return array(
			'thread'	=>	array('archives'),
			'post'		=>	array('feedback')
		);
	}

	public function packagePost($post) {
		return array(
			'post_key'	=>	$post['id'],
			'thread_key'	=>	$post['aid'],
			'author_key'	=>	$post['mid'],
			'author_name'	=>	$post['username'],
			'created_at'	=>	$this->timeFormat($post['dtime']),
			'ip'			=>	$post['ip'],
			'status'		=>	$this->statusFormat($post['ischeck']),
			'message'		=>	$post['msg'],
			'likes'			=>	$post['good'],
			'dislikes'		=>	$post['bad']
		);
	}

	public function packageThread($thread) {
		global $cfg_basehost,$cfg_cmspath;
		$data = array(
			'thread_key'	=>	$thread['id'],
			'title'			=>	$thread['title'],
			'created_at'	=>	$this->timeFormat($thread['pubdate']),
			'author_key'	=>	$thread['mid'],
			'updated_at'	=>	$this->timeFormat($thread['lastpost']),
			'likes'			=>	$thread['goodpost'],
			'dislikes'		=>	$thread['badpost'],
			'excerpt'		=>	$thread['description'],
			'ip'			=>	$thread['userip'],
			'source'		=>	'dedecms',
		);
		if(isset($thread['body']))
			$data['content'] = $thread['body'];
		else if(isset($thread['introduce']))
			$data['content'] = $thread['introduce'];
		else 
			$data['content'] = '';
		
		if(!empty($thread['arcurl'])){
			if(strpos($thread['arcurl'],$cfg_basehost)){
				$data['url'] = $thread['arcurl'];
			}
			else{
				$data['url'] = $cfg_basehost.$cfg_cmspath.$thread['arcurl'];
			}
		}
		if(!empty($thread['litpic'])  && !preg_match('/\/images\/defaultpic.gif/',$thread['litpic'])){
			if(preg_match('/http:\/\//',$thread['litpic'])){
				$data['images'] = json_encode(array($thread['litpic']));
			}else{
				$data['images'] = json_encode(array($cfg_basehost.$cfg_cmspath.$thread['litpic']));
			}
			
		}
		$data['meta'] = json_encode($this->myUnset($thread, array('id', 'title', 'pubdate', 'mid', 'lastpost',
									'goodpost', 'badpost', 'description', 'userip', 'body', 'introduce')));
		return $data;
	}
	
	public function myUnset($data, $keys) {
		if(!is_array($data)) return array();
		foreach($keys as $key) {
			if(isset($data[$key]))
				unset($data[$key]);
		}
		return $data;
	}
}