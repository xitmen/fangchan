<?php
use ACE\Module\Common\ComFunc;
use ACE\Module\Common\ReturnMsg;
use ACE\Module\Common\L;
use ACE\Module\Common\EcryptAES;
require_once IA_ROOT . '/source/modules/acecomm.inc.php'; // 通用方法类
require_once IA_ROOT . '/source/modules/aceerrmsg.inc.php'; // 通用返回信息类

class RTool{
	const API_URI='http://temp.intemash.com';
	const MEM_API_URL='http://121.43.154.92:8080';
	const CRM_API_URL='http://crm.xinglaigroup.com:8088';
	const SECERT="14da91d6168c2a50";
	private static $API_METHOD_MAP=array('add'=>'/Interfaces/addUserRelation',
				'edit'=>'/Interfaces/editUserRelation',
				'get'=>'/Interfaces/getUserRelation',
				'erole'=>'/Interfaces/editUserRole',
				'addjf'=>'/Newinterfaces/addUserJfAction',
				'editjf'=>'/Interfaces/editUserJf',
				'userbase'=>'/Interfaceusers/editUserbase',
				'suggestlog'=>'/Interfaceusers/editUserbase/suggestlog',
				'investlog'=>'/Interfaceusers/editUserbase',
				'getuserjf'=>'/Newinterfaces/getUserJf',
				'getuserjfdetail'=>'/Newinterfaces/getUserJfDetail',
				'wechatconnect'=>'/Newinterfaces/wechatConnect',
				'ifwinaward'=>'/Interfacegames/ifWinAward',
				);
	
	private static $MEM_API_METHOD_MAP=array('applyfounder'=>'/applySharesInfoJson.json',
				'suggestgood'=>'/Interfacegoods/suggestGood',
				'goodtype'=>'/Interfacegoods/getGoodtypes');
	private static $CRM_API_METHOD_MAP=array('getProjectInfo'=>'/xl-league/welcome/search');
	public static function C($method,$content=array()){
		if(empty($method) || empty($content)){
			return false;
		}
		$apiurl='';
		$apimethod='';
		if(array_key_exists($method,self::$API_METHOD_MAP)){
			$apiurl=self::API_URI;
			$apimethod=self::$API_METHOD_MAP[$method];
		}
		elseif(array_key_exists($method,self::$MEM_API_METHOD_MAP)){
			$apiurl=self::MEM_API_URL;
			$apimethod=self::$MEM_API_METHOD_MAP[$method];
		}elseif(array_key_exists($method,self::$CRM_API_METHOD_MAP)){
			$apiurl=self::CRM_API_URL;
			$apimethod=self::$CRM_API_METHOD_MAP[$method];
		}
		else{
			return false;
		}
		$data_string = json_encode($content);
		$ch = curl_init($apiurl.$apimethod);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data_string))
		);
		$result = curl_exec($ch);
		//var_dump($result);
		if(empty($result)){
			return false;
		}
		
		$result=json_decode($result,true);
		
		//get project info from crm
		if($method == "getProjectInfo")return $result;
		if(!empty($result) && $result['status']==false){
			L::log('invitation-info',$result['message']);
			L::log('invitation-detail',$content);
		}
		return $result;
	}
	public static function encode($str){
		return EcryptAES::encrypt($str,self::SECERT);
	}
	public static function decode($str){
		return EcryptAES::decrypt($str,self::SECERT);
	}
	public static function createShareUrl($order,$option,$short=true,$auth_url=false){
		global $_W;
		if($short)
			$url=ComFunc::getCurrentUrl(true).'/s/'.$_W['weid'].'/'.$option.'/'.$order;
		else
			$url=ComFunc::getCurrentUrl(true).'/mobile.php?act=module&name=restaurant&do=Share&weid='.$_W['weid'].'&oid='.$order.'&option='.$option;
		if($auth_url)
			$url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$_W['account']['key'].'&redirect_uri='.rawurlencode($url).'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
		return $url;
	}
	
	
	
}