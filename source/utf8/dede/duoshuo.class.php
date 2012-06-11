<?php
/**
 * 多说插件 类定义
 *
 * @version        $Id: duoshuo.class.php 0 10:17 2012-4-27 xiaowu $
 * @package        DedeCMS.DUOSHUO
 * @copyright      Copyright (c) 2012 - , Duoshuo, Inc.
 * @link           http://www.duoshuo.com
 */
// 引入多说配置文件
include_once DEDEDATA.'/duoshuo.inc.php';

class Duoshuo{
	
	const DOMAIN = 'duoshuo.com';
	const STATIC_DOMAIN = 'static.duoshuo.com';
	const VERSION = '0.2.0';
	
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
	 *
	 * @var string
	 */
	static $pluginDirUrl;
	
	/**
	 *
	 * @var array
	 */
	static $errorMessages = array();
	
	static $EMBED = false;
	
	static $commentTag;
	
	function __construct()
	{
		global $cfg_duoshuo;
		if(!isset($cfg_duoshuo) || !isset($cfg_duoshuo['short_name']) || !isset($cfg_duoshuo['secret']))
		{
			$params = $this->packageOptions();
			if(isset($this->short_name)){
				$params['short_name'] = $this->short_name;
			}
			$url = 'http://' . $this->DOMAIN . '/connect-site/?'. http_build_query($params, null, '&');
			header("Location:" . $url, true);
			exit;
		}
		$this->shortName = $cfg_duoshuo['short_name'];
		$this->secret = $cfg_duoshuo['secret'];
		$this->commentTag = $cfg_duoshuo['tag'];
	}
	
	//保存多说设置
	function save_config(){
		if(empty($_GET) || isset($_GET['short_name']) || isset($_GET['secret'])){
			return  false;	
		}
		
		$short_name = $_GET['short_name'];
		$secret = $_GET['secret'];
		
		//保存Cache信息
		$cacheFile = DEDEDATA.'/duoshuo.inc.php';
		$cacheStr = <<<EOT
<?php if(!defined('DEDEINC')) exit("Request Error!");
global \$cfg_duoshuo;
\$cfg_duoshuo = array();
\$cfg_duoshuo['short_name'] = '$short_name';
\$cfg_duoshuo['secret'] = '$secret';
\$cfg_duoshuo['tag']='{dede:duoshuo/}';
?>
EOT;
		file_put_contents($cacheFile, $cacheStr);
		
		$this->shortName = $short_name;
		$this->secret = $secret;
	}
	
	function replace_comment_tag(){
		$ajax_comment_file = DEDETEMPLATE.'/default/ajaxfeedback.htm';
		$ajax_comment_file_bak = DEDETEMPLATE.'/default/ajaxfeedback.bak.htm';
	
		if(file_exists($ajax_comment_file)){
			if(!file_exists($ajax_comment_file_bak)){
				$ret = copy($ajax_comment_file, $ajax_comment_file_bak);
				if(!$ret){
					$ret = '备份'.$ajax_comment_file.'失败，请检查该目录权限，或手动复制到'.$ajax_comment_file_bak;
					return ret;
				}
			}
			file_put_contents($ajax_comment_file, $this->commentTag);
			return ret;
		}
	}
	
	function manage_comments(){
		$ajax_comment_file = DEDETEMPLATE.'/default/ajaxfeedback.htm';

		$tag_replaced =  false;

		if(file_exists($ajax_comment_file)){

			$comment_content = file_get_contents($ajax_comment_file);

			if(strpos($comment_content,$this->tag)!==false){
				$tag_replaced = true;
			}
		}

		require DEDEADMIN.'/templets/duoshuo_manage.htm';

	}
	/**
	 *  打包选项信息
	 *  例如：pageckageOptions();
	 *
	 * @access    public
	 * @return    array
	 */
	function packageOptions()
	{
		$cur_url = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		global $cfg_webname,$cfg_description,$cfg_basehost,$cfg_indexurl,$cfg_adminemail,$cur_url;
		$params = array(
				'name'			=>	htmlspecialchars_decode($cfg_webname),
				'description'	=>	htmlspecialchars_decode($cfg_description),
				'system'		=>	'dedecms',
				'callback'	=>	$cur_url,
				'url'		=>	$cfg_basehost.$cfg_indexurl,
				'siteurl'	=>	$cfg_basehost,
				'admin_email'	=>	$cfg_adminemail,
				//'timezone'	=> $cfg_cli_time,
		);
		return $params;
	}
	
}