<?php
namespace ACE\Module\Common;
/**
 * 公共返回信息类
 * @author anyo
 * 使用方法：
 * use ACE\Module\Common\ComFunc;
 * use ACE\Module\Common\ReturnMsg;
 * require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';
 * $msg=new ReturnMsg(错误码,错误信息,[其他信息数组]);
 */
class ReturnMsg{
	private $errcode=0;
	private $errmsg='';
	private $otherinfo=array();
	public function __construct($errcode,$errmsg,$otherinfo=array()){
		$this->errcode=$errcode;
		$this->errmsg=$errmsg;
		$this->otherinfo=$otherinfo;
	}
	/**
	 * 获取返回信息的消息字符串
	 * @return string
	 */
	public function getMessage(){
		return $this->errmsg;
	}
	/**
	 * 手机发送验证码
	**/
	public function sendPhoneMsg($phone)
	{
		global $_W, $_GPC;
		$data = array();
		if($phone!='')
		{
			//先判断手机号和openid是否在黑名单中 如果在则不发送
			$item = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE mobile = '".$phone."' or openid = '".$_W['fans']['from_user']."'");
			if(!empty($item))
			{
				$data['ret'] = 0;
				$data['msg'] = '您被拉入黑名单！';
			}
			else
			{
				//判断当天发送的短信是否超过3条
				$t = time();
				$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
				$end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
				$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_note_logs').' where intime > ' . $start . ' and intime < '. $end ." and openid = '".$_W['fans']['from_user']."'");
				if($total < 3)
				{
					$number=rand(111111,999999);
					$msg  =rawurlencode("您收到的短信验证码是：".$number."，请牢记！10分钟有效。【吉本良全】");
					$url = "http://sh2.ipyy.com/smsJson.aspx?action=send&userid=&account=jksc692&password=632874&mobile=".$phone."&content=".$msg."&sendTime=&extno=";
					$res = file_get_contents($url);
					$res = json_decode($res, true);
					if($res['returnstatus'] == 'Success')
					{
						$data['ret'] = 1;
						$data['msg'] = '发送成功！';
						$data['code'] = $number;
						
						$arr['openid'] = $_W['fans']['from_user'];
						$arr['tel'] = $phone;
						$arr['code'] = $number;
						$arr['intime'] = time();
						pdo_insert('ace_note_logs', $arr);
					}
					else
					{
						$data['ret'] = 0;
						$data['msg'] = '暂时不能发送短息！';
					}
				}
				else
				{
					//连续2天超过6条拉黑
					$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_note_logs').' where intime > ' . ($start - 86400) . ' and intime < '. $end ." and openid = '".$_W['fans']['from_user']."'");
					if($total > 5)
					{
						$data['ret'] = 0;
						$data['msg'] = '您被拉入黑名单！';
						
						$arr1['openid'] = $_W['fans']['from_user'];
						$arr1['mobile'] = '';
						$arr1['type'] = 2;
						pdo_insert('ace_blacklists', $arr1);
					}
					else
					{
						$data['ret'] = 0;
						$data['msg'] = '对不起，您当天发送的验证码已经超过3条，请明天再试！';
					}
				}
			}
		}
		else
		{
			$data['ret'] = 0;
			$data['msg'] = '请填写手机号！';
		}
		return json_encode($data);
	}
	/**
	 * 获取返回信息的状态码
	 * @return number
	 */
	public function getErrorCode(){
		return $this->errcode;
	}
	public function __toString(){
		return ComFunc::jsonErrMsg($this->errcode,$this->errmsg,$this->otherinfo);
	}
}