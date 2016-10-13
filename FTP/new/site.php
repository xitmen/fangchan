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
class NewModuleSite extends ComVerControl {
	public function __construct(){
		global $_W;
		ComFunc::$default_referer = preg_replace ( '/do\=[^\&]+/', 'do=Index', ComFunc::getCurrentUrl () ); // 设置错误默认跳转页
		L::debug(); //开启日志调试出现异常后异常信息将直接输出到异常文件中
		//异常可在 ./debug/?list 中查看
	}
	//新房展示列表页
	public function doMobileIndex() 
	{	
	    global $_W, $_GPC;
		checkauth();
		(new check())->ckCity();
		$condition = " where status = 1 ";
		$city_id = $_COOKIE['city_id'];
		$condition .= " and city = " . $city_id;
		//关键字
		$key = $_GPC['key'];
		if($key)
		{
		  $condition .= " and name like '%" . $key ."%'";
		}
		$items = pdo_fetchall("SELECT * FROM ".tablename('ace_new_house')." ".$condition." order by torder asc,id desc");
		$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
		include $this->template('index');
	 }
	 
	//新房展示详情页
	public function doMobileInfo() 
	{
		global $_GPC, $_W;
		checkauth();
		$id = $_GPC['id'];
		if($id)
		{
			$item = pdo_fetch("SELECT * FROM ".tablename('ace_new_house')." WHERE id = :id" , array(':id' => $id));
			if (empty($item)) 
			{
				message('抱歉，信息不存在或是已经删除！');
			}
			else if ($item['status'] != 1) 
			{
				message('抱歉，该房已下架！');
			}
			else
			{
				//标签
				$tags = explode('/', $item['tags']);
				//户型
				$types = pdo_fetchall("SELECT * FROM ".tablename('ace_new_types')." where h_id = ".$item['id']);
				$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
				include $this->template('info');
			}
		}
		else
		{
			message('抱歉，路径错误！');
		}
	}
	
	//保存定制
	public function doMobileSaveDingzhi() 
	{
		global $_GPC, $_W;
		checkauth();
		ComFunc::startSession();
		(new check())->ckCity();
		$province_id = $_COOKIE['province_id'];
		$city_id = $_COOKIE['city_id'];
		$area_id = $_COOKIE['area_id'];
		//如果提交验证码表单
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			$data = array();
			$data['tel'] = $_GPC['mobile'];
			$data['type'] = $_GPC['type'];
			$data['info'] = $_GPC['info'];
			$data['province'] = $province_id;
			$data['city'] = $city_id;
			$data['area'] = $area_id;
			$data['openid'] = $_W['fans']['from_user'];
			pdo_insert('ace_new_msg', $data);

			$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
			if(empty($member))
			{
				$data = array();
				$data['from_user'] = $_W['fans']['from_user'];
				$data['mobile'] = $data['tel'];
				pdo_insert('ace_members', $data);
			}
			else
			{
				pdo_update('ace_members', array('mobile' => $data['tel']), array('from_user' => $_W['fans']['from_user']));
			}
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
		else if($_GPC['op'] == 'ajax')
		{
			$data = array();
			$data['tel'] = $_GPC['mobile'];
			$data['type'] = $_GPC['type'];
			$data['info'] = $_GPC['info'];
			$data['province'] = $province_id;
			$data['city'] = $city_id;
			$data['area'] = $area_id;
			$data['openid'] = $_W['fans']['from_user'];
			pdo_insert('ace_new_msg', $data);
			echo '1';
		}
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