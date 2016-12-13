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
class BaoxiangModuleSite extends ComVerControl {
	public function __construct(){
		global $_W;
		ComFunc::$default_referer = preg_replace ( '/do\=[^\&]+/', 'do=Index', ComFunc::getCurrentUrl () ); // 设置错误默认跳转页
		L::debug(); //开启日志调试出现异常后异常信息将直接输出到异常文件中
		//异常可在 ./debug/?list 中查看
	}
	//包厢首页
	public function doMobileIndex() 
	{	
	    global $_W, $_GPC;
		(new check())->ckCity();
		$type = intval($_GPC['type'])?intval($_GPC['type']):1;//类型
		$condition = " where status = 1 ";
		if($type)
		{
			$condition .= " and type = " . $type;
		}
		$city_id = $_COOKIE['city_id'];
		if($city_id)
		{
			$condition .= " and city = " . $city_id;
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
			unset($_COOKIE['area_id']);
		}
		//关键字
		$key = $_GPC['key'];
		if($key)
		{
		  $condition .= " and name like '%" . $key ."%'";
		}
		$items = pdo_fetchall("SELECT * FROM ".tablename('ace_baoxiang_resource')." ".$condition." order by torder asc,id desc");
		require_once IA_ROOT.'/source/modules/baoxiang/tags.php';  //引入标签
		$xing = array('1' => 'one','2' => 'two','3' => 'three','4' => 'four','5' => 'five');
		include $this->template('index');
	 }
	 
	 //包厢详情
	public function doMobileInfo() 
	{
		global $_GPC, $_W;  //全局变量
		(new check())->ckCity();
		$id = intval($_GPC['id']);
		$item = pdo_fetch("SELECT * FROM ".tablename('ace_baoxiang_resource')." where id = ".$id);
		if(empty($item))
		{
			message('抱歉，信息不存在或是已经删除！', '', 'error');
		}
		else if ($item['status'] == -1) 
		{
			message('抱歉，该包厢暂未通过审核！');
		}
		$xing = array('1' => '一','2' => '二','3' => '三','4' => '四','5' => '五');
		include $this->template('info');
	}
	//商家入驻
	public function doMobileJoin() 
	{
		global $_GPC, $_W;  //全局变量
		checkauth();
		ComFunc::startSession();
		(new check())->ckCity();
		$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
		//如果提交验证码表单
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			if($_GPC['mobile'])
			{
				if(empty($member))
				{
					$data = array();
					$data['from_user'] = $_W['fans']['from_user'];
					$data['mobile'] = $_GPC['mobile'];
					pdo_insert('ace_members', $data);
				}
				else
				{
					pdo_update('ace_members', array('mobile' => $_GPC['mobile']), array('from_user' => $_W['fans']['from_user']));
				}
			}
			$type = $_GPC['type'];
			$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=".$_COOKIE['city_id']." and status=1");
			include $this->template('join_in');exit;
		}
		require_once IA_ROOT.'/source/modules/baoxiang/tags.php';  //引入标签
		include $this->template('join');
	}
	
	//关注
	public function doMobileGuanzhu() 
	{
		global $_GPC, $_W;
		checkauth();
		ComFunc::startSession();
		if(!empty($_GPC['id']) && !empty($_GPC['type']) && !empty($_GPC['url']))
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
					$data['url'] = $_GPC['url'];
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
	//保存定制
	public function doMobileSavejoin() 
	{
		global $_GPC, $_W;
		checkauth();
		ComFunc::startSession();
		//如果提交验证码表单
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			$data = $_GPC['data'];
			$data['peitao'] = $data['peitao'].' '.$_GPC['peitao'];
			$data['openid'] = $_W['fans']['from_user'];
			pdo_insert('ace_baoxiang_resource', $data);
			message('提交成功！', $this->createMobileUrl('index'), 'success');
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
	 public function getRegion($id)
	{
		global $_GPC, $_W;
		if($id)
		{
			$region = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$id." and status=1");
			return $region['region_name'];
		}
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

}