<?php
defined('IN_IA') or exit('Access Denied');
use ACE\Module\Common\ComVerControl;
use ACE\Module\Common\ComFunc;
use ACE\Module\Common\L;
use ACE\Module\Common\WxFunc;
use ACE\Module\Common\ReturnMsg;
use ACE\Module\check;
require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';  //版本控制类
require_once IA_ROOT.'/source/modules/acecomm.inc.php';  //通用方法类
require_once IA_ROOT.'/source/modules/aceerrmsg.inc.php';  //通用返回信息类
require_once IA_ROOT.'/source/modules/common.inc.php';  //通用返回信息类
require_once IA_ROOT.'/source/modules/check.php';
class ShangmenModuleSite extends ComVerControl {
	public function __construct(){
		global $_W;
		ComFunc::$default_referer = preg_replace ( '/do\=[^\&]+/', 'do=Index', ComFunc::getCurrentUrl () ); // 设置错误默认跳转页
		L::debug(); //开启日志调试出现异常后异常信息将直接输出到异常文件中
		//异常可在 ./debug/?list 中查看
	}
	//首页
	public function doMobileIndex() 
	{	
	    global $_W, $_GPC;
		(new check())->ckCity();
		require_once IA_ROOT.'/source/modules/shangmen/tags.php';  //引入标签
		include $this->template('index');
	 }
	 //商家表页
	public function doMobileList() 
	{	
	    global $_W, $_GPC;
		(new check())->ckCity();
		$condition = " where status = 1 ";
		$tagarrs = array();
		$tag = $_GPC['tags'];
		if($tag)
		{
			$condition .= " and tags like '%," . $tag .",%'";
		}
		$city_id = $_COOKIE['city_id'];
		if($city_id)
		{
			$condition .= " and city = " . $city_id;
		}
		if($_GPC['v'])
		{
			$condition .= " and is_v = " . $_GPC['v'];
		}
		if($_GPC['c'])
		{
			$condition .= " and is_c = " . $_GPC['c'];
		}
		//查询区域
		$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=".$city_id." and status=1 ORDER BY torder asc,region_id DESC");
		$area_id = $_GPC['area_id'];
		if($area_id)
		{
			$region_name = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$area_id." and status=1");
			$condition .= " and area = " . $area_id;
			setcookie("area_id", $area_id, time()+3600*72);
		}
		else
		{
			$region_name = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$city_id." and status=1");
			setcookie("area_id", $area_id, time()-1);
		}
		$items = pdo_fetchall("SELECT * FROM ".tablename('ace_shangmen_merchant')." ".$condition." order by torder asc,id desc");
		require_once IA_ROOT.'/source/modules/shangmen/tags.php';  //引入标签
		include $this->template('list');
	 }
	 
	 //商家入驻
	public function doMobileJoin() 
	{
		global $_GPC, $_W;
		checkauth();
		(new check())->ckCity();
		//查看手机是否验证过
		if($_W['fans']['from_user'])
		{
			$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
			//如果提交验证码表单
			if($_GPC['op'] == 'submit' && $this->repeatSubmit())
			{
				$mobile = $_GPC['mobile'];
				if(empty($member))
				{
					$data = array();
					$data['from_user'] = $_W['fans']['from_user'];
					$data['mobile'] = $mobile;
					pdo_insert('ace_members', $data);
				}
				else
				{
					pdo_update('ace_members', array('mobile' => $mobile), array('from_user' => $_W['fans']['from_user']));
				}
			}
			else
			{
				$mobile = $member['mobile'];
			}
			if(empty($mobile))//没有手机验证过
			{
				//加载发送验证码页面
				include $this->template('msg');
			}
			else
			{
				$province_id = $_COOKIE['province_id'];
				$city_id = $_COOKIE['city_id'];
				$area_id = $_COOKIE['area_id'];
				require_once IA_ROOT.'/source/modules/shangmen/tags.php';  //引入标签
				$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=".$city_id." and status=1 ORDER BY torder asc,region_id DESC");
				//加载定制页面
				include $this->template('join');
			}
		}
		else
		{
			message('抱歉，个人信息获取失败！');
		}
	}
	//保存商家入驻
	public function doMobileSaveJion() 
	{
		global $_GPC, $_W;
		checkauth();
		ComFunc::startSession();
		//如果提交验证码表单
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			$data = $_GPC['data'];
			$data['openid'] = $_W['fans']['from_user'];
			$tagarrs = $_GPC['tags'];
			if(!empty($tagarrs))
			{
				$data['tags'] = ','.implode(',', $tagarrs).',';
			}
			else
			{
				$data['tags'] = '';
			}
			pdo_insert('ace_shangmen_merchant', $data);
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
		
	}
	 //关注
	public function doMobileGuanzhu() 
	{
		global $_GPC, $_W;
		checkauth();
		ComFunc::startSession();
		if(!empty($_GPC['id']) && !empty($_GPC['type']))
		{
			if($_W['fans']['from_user'])
			{
				//判断是否关注过
				$attention = pdo_fetch("SELECT * FROM ".tablename('ace_attention')." WHERE openid = :openid and s_id = :s_id and type = :type" , array(':s_id' => $_GPC['id'],':type' => $_GPC['type'],':openid' => $_W['fans']['from_user']));
				if(empty($attention))
				{
					$data = array();
					$data['type'] = $_GPC['type'];
					$data['s_id'] = $_GPC['id'];
					$data['url'] = '';
					$data['openid'] = $_W['fans']['from_user'];
					pdo_insert('ace_attention', $data);
					echo json_encode(
						array('ret' => '1', 'msg' => '关注成功')
					);
				}
				else
				{
					echo json_encode(
						array('ret' => '0', 'msg' => '您已经关注过')
					);
				}
			}
			else
			{
				echo json_encode(
					array('ret' => '0', 'msg' => '获取不到个人资料')
				);
			}
		}
		else
		{
			echo json_encode(
				array('ret' => '0', 'msg' => '缺少参数')
			);
		}
	}
	//是否已关注
	public function IsGuanzhu($id) 
	{
		global $_GPC, $_W;
		ComFunc::startSession();
		if($_W['fans']['from_user'])
		{
			//判断是否关注过
			$attention = pdo_fetch("SELECT * FROM ".tablename('ace_attention')." WHERE openid = :openid and s_id = :s_id and type = :type" , array(':s_id' => $id,':type' => 5,':openid' => $_W['fans']['from_user']));
			if(empty($attention))
			{
				RETURN 1;
			}
			else
			{
				RETURN 0;
			}
		}
		else
		{
			RETURN 2;
		}
	}
	 /**
	 * 手机短信通知接口
	 * lihui2016年9月2日
	 */
	public function doMobileYzm(){
	    global $_GPC, $_W;  //全局变量
	    $phone = $_GPC['phone'];
		echo (new ReturnMsg)->sendPhoneMsg($phone);
	}
	 /**
	 * 防止用户重复提交
	 */
	public  function repeatSubmit(){
		global $_GPC;
		ComFunc::startSession();
		if(isset($_GPC['token']))
		{  //检测是否提交数据过来
			if($_GPC['token'] != $_SESSION['token'])
			{
				$_SESSION['token'] = $_GPC['token'];
				return true;
			}
			else 
			{
				message('抱歉，数据不能重复提交！', $this->createMobileUrl('index'), 'error');
				return false;
			}
		}
		return true;
	}
	 public function getRegion($id)
	{
		global $_GPC, $_W;
		if($id)
		{
			$region = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$id." and status=1");
			return $region['region_name'];
		}
	}

}