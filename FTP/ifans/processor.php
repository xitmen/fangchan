<?php
/**
 * 大转盘模块处理程序
 *
 * @author 
 * @url http://www.vezhi.net
 */
defined('IN_IA') or exit('Access Denied');

class WheelModuleProcessor extends WeModuleProcessor {
	public function respond() {
		$content = $this->message['content'];
		//这里定义此模块进行消息处理时的具体过程, 请查看ACE文档来编写你的代码
	}
}