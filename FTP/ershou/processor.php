<?php
defined('IN_IA') or exit('Access Denied');
use ACE\Module\Common\ComFunc;
use ACE\Module\Common\WxFunc;

require_once IA_ROOT . '/source/modules/versioncontrol.inc.php'; // 版本控制类
require_once IA_ROOT . '/source/modules/acecomm.inc.php'; // 通用方法类
require_once IA_ROOT . '/source/modules/aceerrmsg.inc.php'; // 通用返回信息类
require_once IA_ROOT . '/source/modules/common.inc.php';
class ErshouModuleProcessor extends WeModuleProcessor {
	
	private function _emjio($str) {
		$str = '{"result_str":"'.$str.'"}';	//组合成json格式
		$strarray = json_decode($str,true);	//json转换为数组，利用 JSON 对 \uXXXX 的支持来把转义符恢复为 Unicode 字符（by 梁海）
		return $strarray['result_str'];
	}
	

	private function _createAuthUrl($url='',$scope='snsapi_base'){
		global $_W;
		$rurl='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$_W['account']['key'];
		$rurl.='&redirect_uri='.rawurlencode($url);
		$rurl.='&response_type=code&scope='.$scope.'&state=STATE#wechat_redirect';
		return $rurl;
	}
	
	public function respond() {
		global $_W;
		$keyword = $this->message['content'];
		$user = pdo_fetch('select * from ' . tablename('ace_members') . ' where `from_user`=:opid', array(':opid' => $this->message['from']));
		//新用户直接完成member注册
		if (empty($user)) {
			$udata = array('uname' => 'null', 'avatar' => '');
			ComFunc::wechatUser($udata, $this->message['from']);
			$user = array('from_user' => $this->message['from'], 'uname' => $udata['uname'], 'avatar' => $udata['avatar'], 'u_id' => $_W['weid']);
			pdo_insert('ace_members', $user);
			RTool::C('add', array('uuid' => $this->message['from'], 'puuid' => '', 'nickname' => $user['uname'], 'role1' => 0, 'role2' => 0, 'role3' => 0));
		}
		//如果用户头像为空，尝试获取用户头像及昵称
		if (empty($user['avatar'])) {
			$udata = array('uname' => 'null', 'avatar' => '');
			ComFunc::wechatUser($udata, $this->message['from']);
			pdo_update('ace_members', array('from_user' => $this->message['from'],
				'uname' => $udata['uname'],
				'avatar' => $udata['avatar']), array('id' => $user['id']));
		}
		return $this->respText('\:0');
	}
}
