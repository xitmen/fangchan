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
class JiazhengModuleSite extends ComVerControl {
	public function __construct(){
		global $_W;
		ComFunc::$default_referer = preg_replace ( '/do\=[^\&]+/', 'do=Index', ComFunc::getCurrentUrl () ); // 设置错误默认跳转页
		L::debug(); //开启日志调试出现异常后异常信息将直接输出到异常文件中
		//异常可在 ./debug/?list 中查看
	}
	//家政展示列表页
	public function doMobileIndex() 
	{	
	    global $_W, $_GPC;
		(new check())->ckCity();
		$city_id = $_COOKIE['city_id'];
		//查询区域
		$regions = pdo_fetchall("SELECT region_id,region_name FROM ".tablename('ace_region')." where parent_id=".$city_id." and status=1 ORDER BY torder asc,region_id DESC");
		foreach($regions as $k=>$region)
		{
			$regions[$k]['items'] = pdo_fetchall("SELECT id,name FROM ".tablename('ace_jiazheng_service')." where area=".$region['region_id']." order by torder asc,id desc");
			if(empty($regions[$k]['items']))
			{
				unset($regions[$k]);
			}
		}
		include $this->template('index');
	 }
	 
	//家政展示详情页
	public function doMobileInfo() 
	{
		global $_GPC, $_W;
		$id = $_GPC['id'];
		if($id)
		{
			$item = pdo_fetch("SELECT * FROM ".tablename('ace_jiazheng_service')." WHERE id = :id" , array(':id' => $id));
			if (empty($item)) 
			{
				message('抱歉，信息不存在或是已经删除！');
			}
			else
			{
				include $this->template('info');
			}
		}
		else
		{
			message('抱歉，路径错误！');
		}
	}
	
	//找家政
	public function doMobileFind() 
	{
		global $_GPC, $_W;
		checkauth();
		(new check())->ckCity();
		//查看手机是否验证过
		if($_W['fans']['from_user'])
		{
			$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
			$province_id = $_COOKIE['province_id'];
			$city_id = $_COOKIE['city_id'];
			$area_id = $_COOKIE['area_id'];
			//加载定制页面
			include $this->template('find');
		}
		else
		{
			message('抱歉，个人信息获取失败！');
		}
	}
	//保存找家政信息
	public function doMobileSavefind() 
	{
		global $_GPC, $_W;
		checkauth();
		ComFunc::startSession();
		//如果提交验证码表单
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			$data = $_GPC['data'];
			$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
			if(empty($member))
			{
				$data1 = array();
				$data1['from_user'] = $_W['fans']['from_user'];
				$data1['mobile'] = $data['phone'];
				$data['gzdh'] = $data['phone'];
				pdo_insert('ace_members', $data1);
			}
			else
			{
				pdo_update('ace_members', array('mobile' => $data['phone']), array('from_user' => $_W['fans']['from_user']));
				$data['phone'] = $member['mobile'];
				$data['gzdh'] = $member['mobile'];
			}
			$data['name'] = $_GPC['a1'];
			$data['address'] = $_GPC['a2'];
			$data['openid'] = $_W['fans']['from_user'];
			pdo_insert('ace_jiazheng_look', $data);
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
		else
		{
			message('抱歉，您迷路了！');
		}
	}
	//创建简历
	public function doMobileResume() 
	{
		global $_GPC, $_W;
		checkauth();
		(new check())->ckCity();
		//查看手机是否验证过
		if($_W['fans']['from_user'])
		{
			$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
			$province_id = $_COOKIE['province_id'];
			$city_id = $_COOKIE['city_id'];
			$area_id = $_COOKIE['area_id'];
			$areas = pdo_fetchall("SELECT region_id,region_name FROM ".tablename('ace_region')." where parent_id=".$_COOKIE['city_id']." and status=1");
			//加载页面
			include $this->template('resume');
		}
		else
		{
			message('抱歉，个人信息获取失败！');
		}
	}
	//保存简历信息
	public function doMobileSaveresume() 
	{
		global $_GPC, $_W;
		checkauth();
		ComFunc::startSession();
		//如果提交验证码表单
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			$data = $_GPC['data'];
			$data['years'] = $_GPC['nian'];
			$data['month'] = $_GPC['yue'];
			$data['gzjy'] = $_GPC['jy_s'].'-'.$_GPC['jy_s'].'年';
			$data['xinzi'] = $_GPC['xz_s'].'-'.$_GPC['xz_e'];
			$data['openid'] = $_W['fans']['from_user'];
			pdo_insert('ace_jiazheng_resume', $data);
			$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
			if(empty($member))
			{
				$data = array();
				$data['from_user'] = $_W['fans']['from_user'];
				$data['mobile'] = $data['phone'];
				pdo_insert('ace_members', $data);
			}
			else
			{
				pdo_update('ace_members', array('mobile' => $data['phone']), array('from_user' => $_W['fans']['from_user']));
			}
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
		else
		{
			message('抱歉，您迷路了！');
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