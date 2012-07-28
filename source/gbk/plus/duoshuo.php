<?php
if (!extension_loaded('json'))
	include_once DEDEROOT.'/plus/duoshuo/compat_json.php';

require_once DEDEROOT.'/plus/duoshuo/Exception.php';
require_once DEDEROOT.'/plus/duoshuo/Client.php';
require_once DEDEROOT.'/plus/duoshuo/Abstract.php';
require_once DEDEROOT.'/plus/duoshuo/Dedecms.php';