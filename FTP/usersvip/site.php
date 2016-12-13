<?php
/**
 * 会员专区模块微站定义
 *
 * @author vezhi.net
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class UsersvipModuleSite extends WeModuleSite {
    //会员权限
	private $CUS_PRIVILEGE_STATUS=array(
			1=>'可享受积分制度',
			2=>'可享受折扣优惠'
			);
	/*** ************************************************************************************************前台-代码*******************************************************************************/
	
	
	/**
	 *前台-会员专区  
	 */
	public function doMobileLogout(){//退出登录
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileLogout();
	}
	public function doMobileBinding(){  //微信账户绑定会员账户  yam 2014年4月17日
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileBinding();
	}
	public function doMobileNew(){
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileNew();
	}
	public function doMobileLogin(){//登陆
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileLogin();
	}
	public function doMobileReg(){//注册
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileReg();
	}
	
	
	public function doMobileCity(){//城市切换
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileCity();
	}
	public function doMobileIndex(){//首页
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileIndex();
	}

	public function doMobileQiye(){//企业
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileQiye();
	}
	public function doMobilePublish(){//发布房源首页
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobilePublish();
	}
	public function doMobileSavePublish(){//发布房源首页
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileSavePublish();
	}
	public function doMobileErshou_publish(){//二手房发布
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileErshou_publish();
	}
	public function doMobileChuzu_publish(){//出租房发布
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileChuzu_publish();
	}
	public function doMobileYzm(){//验证码
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileYzm();
	}
	
	public function doMobileXuzhi(){//使用须知
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileXuzhi();
	}
	public function doMobileXuzhi_info(){//使用须知
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileXuzhi_info();
	}
	public function doMobileAds_info(){//广告详情
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileAds_info();
	}
	public function doMobileTousu(){//投诉评价
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileTousu();
	}
	public function doMobileMy(){//我的
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileMy();
	}
	public function doMobileMyguanzhu(){//我的关注
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileMyguanzhu();
	}
	public function doMobileMyfabu(){//我的发布
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileMyfabu();
	}
	public function doMobileMyyuyue(){//我的预约
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileMyyuyue();
	}
	public function doMobileMytousu(){//我的投诉
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileMytousu();
	}
	public function doMobileHongbao(){//红包界面
		require_once 'front.cls.php';
		UsersvipFrontModel::E($this)->doMobileHongbao();
	}
	/**以上为注册登陆相关函数**/

	/*** ************************************************************************************************后台-代码*******************************************************************************/

	/**
	 * 投诉评价
	**/
	public function doWebTousu ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if($operation == 'display')
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' where 1 ';
			if($_GPC['key'])
			{
				$where .= " and name like '%".$_GPC['key']."%'";
			}
			if($_GPC['province'])
			{
				$where .= " and province = ".$_GPC['province'];
			}
			if($_GPC['city'])
			{
				$where .= " and city = ".$_GPC['city'];
			}
			if($_GPC['area'])
			{
				$where .= " and area = ".$_GPC['area'];
			}
			if($_GPC['status'])
			{
				$where .= " and status = ".$_GPC['status'];
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_tousu_pingjia')." ".$where." ORDER BY  id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_tousu_pingjia')." ".$where);
			$pager = pagination($total, $pindex, $psize);
			$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
		}
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_tousu_pingjia')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_tousu_pingjia', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		elseif($operation == 'status')
		{
			$id = intval($_GPC['id']);
			$status = intval($_GPC['status']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_tousu_pingjia')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_update('ace_tousu_pingjia', array('status'=>$status), array('id' => $id));
			message('处理成功！', referer(), 'success');
		}
		include $this->template('tousu');	
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
	//获取区域
	public function doWebgetAllRegions()
	{
		global $_GPC, $_W;
		$id = $_GPC['id'];
		$str = '<option value="">--'.$_GPC['names'].'--</option>';
		if($id)
		{
			$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=".$id." and status=1 ORDER BY torder asc,region_id DESC");
			foreach($regions as $region)
			{
				$str .= '<option value="'.$region['region_id'].'">'.$region['region_name'].'</option>';
			}
		}
		echo $str;
	}
}