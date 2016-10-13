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
	/**以上为注册登陆相关函数**/
	
	public function doMobileRecharge(){
		global $_W, $_GPC;
		@session_start();
		if(!empty($_W['fans']['from_user']) && $_SESSION['weid']!=$_W['weid']){
			$fans=pdo_fetch("SELECT id FROM ".tablename('ace_members')." WHERE `from_user`=:f_user and `u_id` =:weid",array(':f_user'=>$_W['fans']['from_user'],':weid'=>$_W['weid']));
			if(!empty($fans)){
				$_SESSION['fromUser']=$fans['id'];
				$_SESSION['weid']=$_W['weid'];
			}
		}
		if(empty($_SESSION['fromUser'])){
			message('您没有登陆，无法访问充值页！',referer(),'error');
			exit;
		}
		if(!empty($_GPC['action']) && $_GPC['action']=='recharge'){
			if(preg_match('/^\d+(\.\d{1,2})?$/', $_GPC['money'])){
				$oid=$this->createOrderNo().$this->createRndStr();
				$exfee=$this->getGift(floatval($_GPC['money']));
				$dat=array('rmid'=>$oid,'content'=>'在线充值'.$_GPC['money'].'元','fee'=>floatval($_GPC['money']),'exfee'=>$exfee,'createtime'=>TIMESTAMP,'weid'=>$_W['weid'],'mid'=>$_SESSION['fromUser'],'openid'=>'');
				if(pdo_insert('ace_recharge_list',$dat)){
					message($oid,referer(),'success','ajax');
					exit;
				}
				message('创建充值订单失败，暂时无法充值！',referer(),'error','ajax');
			}
			else{
				message('充值金额输入有误，请检查金额是否输入正确',referer(),'error','ajax');
			}
			exit;
		}
		include $this->template('recharge');
	}
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