<?php
defined('IN_IA') or exit('Access Denied');
use ACE\Module\Common\ComFunc;
use ACE\Module\Common\ReturnMsg;
use ACE\Module\check;
require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';
require_once IA_ROOT.'/source/modules/aceerrmsg.inc.php';  //通用返回信息类
require_once IA_ROOT.'/source/modules/check.php';
class UsersvipFrontModel extends WeModuleSite{
	private static $__this=null;
	
	private $cfg_session_domain="/";    //session跨域参数
	private $cfg_session_expire=3600;   //session有效时间 单位秒
	private $cfg_session_pre="__sess";  //session加密参数
	
	/**
	 * 直接访问用户返回true 微信用户返回false
	 * @return boolean
	 */
	function userauth() {
		if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')>0){
			return false;
		} else {
			return true;
		}
	}
	public function __construct($p){
		$this->module = $p->module;
		$this->weid = $p->weid;
		$this->inMobile = $p->inMobile;
	}
	public static function E($p){
		if(self::$__this==null){
			self::$__this=new UsersvipFrontModel($p);
		}
		self::$__this->userauth();
		return self::$__this;
	}
	
	/**
	 * Session 启用方法，在使用session的页面中需先调用此方法
	 * 调用方法 $this->startSession();
	 * @author yam 2014年4月16日
	 */
	private function startSession($first=false){
		ComFunc::startSession();
	}
	/*******************以上为类核心代码请勿修改 yam 2014年4月15日******************************************************************************************************/

	
	/**
	 * 密码算法
	 * @param string 提交密码
	 * @author yam 2014年4月16日
	 */
	private function getpass($key){
		return substr(md5($key.'_VesOp'),0,15);
	}
	/**
	 * 页面跳转方法
	 * @param unknown_type 跳转到指定url
	 * @author yam 2014年4月16日
	 */
	private function redrict($url){
		header("Location: ".$url);
		exit;
	}
	/**
	 * 检查用户是否登录
	 * @author yam 2014年4月16日
	 */
	private function checkLogin(){
		ComFunc::checkLogin();
	}
	/*一下为登陆注册绑定登出函数*/
	/**
	 * 2014年06月30日
	 * @author anyo
	 */
	private function getReferUri(){
		$goto=referer();
		if($_COOKIE['___lasturl']){
			$goto=$_COOKIE['___lasturl'];
		}
		return $goto;
	}
	public function doMobileLogout(){
		$this->checkLogin();
		session_destroy();
		message('退出成功！',$this->getReferUri(),'success');
	}
	///微信访问用户 注册新账户
	public function doMobileNew(){
		global $_GPC, $_W;  //全局变量
		checkauth();//校验是否已经关注当前微信公众平台
		//查询微信用户是否已存在于 ace_members表中
		$user = pdo_fetch("SELECT id FROM ".tablename('ace_members')." WHERE `from_user`=:f_user and `u_id` =:weid",array(':f_user'=>$_W['fans']['from_user'],':weid'=>$_W['weid']));
		if(empty($user)){  //不存在于 ace_members表中
			$udata=array(
					'from_user'=>$_W['fans']['from_user'],
					'u_id'=>$_W['weid'],
					'uname'=>'null',
					'password'=>'null'
			);
			$this->wechatUser($udata);
			//给该用户设置一个最低等级 vz_ace_usersvip_card
			$card = pdo_fetch("select id,format from ".tablename('ace_usersvip_card')." where u_id=".$_W['weid']." order by class asc");
			if(!empty($card)){$udata['class']=$card['id'];}
			 //求最大的会员卡号
			 $m_num = pdo_fetch('SELECT max(card_num) FROM ' . tablename('ace_members') . " WHERE u_id = '{$_W['weid']}'");
			 if($m_num['max(card_num)'])
			 {
			  $s=$m_num['max(card_num)']+1;
			 }else{
			  $s=1;
			 }
			 $l=strlen($s);
			 if($l>5)
			 {
			  $s=$s;
			 }else{
			  $s=str_repeat("0",(5-$l)).$s;
			 }
			 
			$udata['card_num']=$s;
			$udata['pay_pwd']='123123';
			ComFunc::startSession();
			$u=pdo_insert('ace_members',$udata);
			$_SESSION['fromUser']=pdo_insertid();
			$_SESSION['islogin']="1";
			$_SESSION['weid']=$_W['weid'];
			//message('注册微信会员成功，已经为您登录！钱包默认支付密码是：123123',$this->getReferUri(),'success');
			ComFunc::redrictReferer($this->getReferUri());
			exit;
		}
		else {
			message('入口异常！',$this->getReferUri(), 'error');
		}
	}
	
	public function doMobileBinding(){
		global $_GPC, $_W;  //全局变量
		//checkauth();//校验是否已经关注当前微信公众平台
		//查询微信用户是否已存在于 ace_members表中
		$user = pdo_fetch("SELECT id FROM ".tablename('ace_members')." WHERE `from_user`=:f_user and `u_id` =:weid",array(':f_user'=>$_W['fans']['from_user'],':weid'=>$_W['weid']));
		if(empty($user)){  //不存在于 ace_members表中
			//开始验证账户，并绑定
			if(checksubmit('token')){
				$udata=$_GPC['data'];
				$udata['password']=$this->getpass($udata['password']);
				$user = pdo_fetch("SELECT id FROM ".tablename('ace_members')." WHERE `uname`=:uname and `password`=:pass and `u_id` =:weid",
						array(':uname' => $udata['uname'],
								':pass'=>$udata['password'],
								':weid'=>$_W['weid']));
				if(!empty($user)){
					$this->wechatUser($user);
					$u=pdo_update('ace_members',array('from_user'=>$_W['fans']['from_user'],'avatar'=>$user['avatar']),array('id'=>$user['id']));
					ComFunc::startSession();
					$_SESSION['fromUser']=$user['id'];
					$_SESSION['islogin']="1";
					$_SESSION['weid']=$_W['weid'];
					message('绑定微信会员成功，已经为您登录！',$this->getReferUri(),'success');
				}
				else {
					message('您的输入的用户名或密码不正确！',referer(), 'error');
				}
			}
			else {
				include $this->template('welogin');
			}
		}
		else {
			message('您的微信账户已绑定过！',$this->getReferUri(), 'error');
		}
	
	}
	/**
	 * 前台登录 yam 2014年4月16日
	 */
	public function doMobileLogin(){
		global $_GPC, $_W;  //全局变量
		ComFunc::startSession();
		if(checksubmit('token')){
			$udata=$_GPC['data'];
			$udata['password']=$this->getpass($udata['password']);
			$user = pdo_fetch("SELECT id FROM ".tablename('ace_members')." WHERE `uname`=:uname and `password`=:pass and `u_id` =:weid",
					array(':uname' => $udata['uname'],
							':pass'=>$udata['password'],
							':weid'=>$_W['weid']));
			if($user){
				$_SESSION['fromUser']=$user['id'];
				$_SESSION['weid']=$_W['weid'];
				$_SESSION['islogin']="1";
				//echo $this->getReferUri();
				message('登录成功！',$this->getReferUri(),'success');
			}
			else{
				message('登录失败，用户名或密码错误，请重新输入！',referer(), 'error');
			}
		}
		else {
			include $this->template('login');
		}
	}
	/**
	 * 用户注册 yam 2014年4月16日
	 */
	public function doMobileReg(){  //用户注册
		global $_GPC, $_W;  //全局变量
		$this->startSession();
		if(checksubmit('token')){
			$udata=$_GPC['data'];
			if(!isset($udata['uname']) || trim($udata['uname'])=="" ||!isset($udata['password']) || trim($udata['password'])==""){
				message('您没有输入用户名或者密码！，请重新输入！',referer(), 'error');
			}
			if(!empty($udata['mobile']) && !preg_match("/^(\d{11})$|^(\d{3,4}-\d{7,8})$/", $udata['mobile'])){
				message('您输入的手机号码不正确！，请重新输入！',referer(), 'error');
			}
			if(!empty($udata['qqnum']) && !preg_match("/^\d+$/", $udata['qqnum'])){
				message('您输入的QQ号码不正确！，请重新输入！',referer(), 'error');
			}
			$udata['u_id']=$_W['weid']; //商户编号
			$udata['password']=$this->getpass($udata['password']);
			$hasM = pdo_fetch("SELECT * FROM ".tablename('ace_members')." WHERE `uname`=:uname and `u_id`=:uid" , array(':uname' => $udata['uname'],':uid'=>$_W['weid']));
			if(!$hasM){
				$u=pdo_insert('ace_members',$udata);
				$_SESSION['fromUser']=pdo_insertid();
				$_SESSION['islogin']="1";
				$_SESSION['weid']=$_W['weid'];
				message('注册成功，已经为您登录！',$this->getReferUri(),'success');
			}
			else{
				message('您填写的用户名已存在，请重新输入！',referer(), 'error');
			}
		}
		else{
			include $this->template('register');
		}
	}
	
	/**+++++++++++++++++++++以上为与登陆注册绑定相关函数+++++++++++++++++++++++++++++++**/
	
	/**
	 * 编辑用户个人资料  yam  2014-04-28
	 */
	public function doMobileProfile(){
		error_reporting(1);
		global $_GPC,$_W;
		$this->checkLogin();
		$uid=$_SESSION['fromUser'];
		$user=pdo_fetch('select * from '.tablename('ace_members').' where `id`=:uid',array(':uid'=>$uid));
		if(checksubmit('token')){
			$ud=$_GPC['data'];
			$isvalide=true;
			if($ud['avatar']!='' && !preg_match("/^http:\/\/.+\.(jpg|png|gif|jpeg|bmp)$/", $ud['avatar'])){
				$isvalide=false;
			}
			if($ud['mobile']!='' && !preg_match("/^\d+$/",$ud['mobile'])){
				$isvalide=false;
			}
			if($ud['qqnum']!='' && !preg_match("/^\d+$/",$ud['qqnum'])){
				$isvalide=false;
			}
			if($ud['avatar']==''){
				$ud['avatar']=$user['avatar'];
			}
			if($isvalide){
				@pdo_update('ace_members',$ud,array('id'=>$uid));
				message('修改资料成功！',$this->createMobileUrl('my_money'),'success');
			}
		}
		if($_GPC['action']=='getheader'){
			if(!isset($user['from_user']) || $user['from_user']==''){
				exit('{"code":"4","msg":"您还没有绑定微信账户！\r\n请先使用微信访问绑定后获取微信头像！"}');
			}
			$tmp=array('uname'=>'','avatar'=>'');
			$this->wechatUser($tmp);
			if(empty($tmp['avatar'])) exit('{"code":"4","msg":"微信通信失败，请稍等片刻后再试！"}');
			exit('{"code":"0","header":"'.$header.'"}');
		}
		if(empty($user)) message("未知来路，请检测来路是否正确！",$this->createMobileUrl('Home'),'error') && exit;
		include $this->template('profile');
	}
	/**
	 * 获取微信用户昵称及头像
	 * @param array 用户信息
	 * @param boolen 是否仅获得头像 默认否
	 * @author yam 2014-04-28
	 */
	private function wechatUser(&$udata){
		global $_GPC,$_W;
		$wc_acceount=$_W['account'];
		if(empty($_W['fans']['from_user'])) return;
		//获取access_token
		$access_token= account_weixin_token($wc_acceount);
		$openid=$_W['fans']['from_user'];
		$url="https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
		$content=ihttp_get($url);
		if(empty($content)) return;
		$wc_userinfo = @json_decode($content['content'], true);
		if(empty($wc_userinfo) || !is_array($wc_userinfo)) {
			return;
		}
		//存在异常信息 如 48001 api未授权
		if(isset($wc_userinfo['errcode'])){
			return;
		}
		//获取用户微信头像及昵称
		if(empty($udata['uname']) || strtolower($udata['uname'])=='null')
			$udata['uname']=$wc_userinfo['nickname'];
		$udata['avatar']=$wc_userinfo['headimgurl'];
	}

	//首页
    public function doMobileIndex()
	{
	    global $_GPC, $_W;  //全局变量
		(new check())->ckCity();
		$city = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$_COOKIE['city_id']." and status=1");
		$topAds = pdo_fetchall("SELECT * FROM ".tablename('ace_jiben_ads')." where type=1 ORDER BY torder asc,id DESC");
		$topBottom = pdo_fetchall("SELECT * FROM ".tablename('ace_jiben_ads')." where type=2 ORDER BY torder asc,id DESC");
		include $this->template('index');
	}
	
	//城市切换
    public function doMobileCity()
	{
	    global $_GPC, $_W;  //全局变量
		$op = $_GPC['op'];
		if($op == 'change')
		{
			$city_id = $_GPC['city_id'];
			$province_id = $_GPC['province_id'];
			setcookie("city_id", $city_id, time()+3600*72);
			setcookie("province_id", $province_id, time()+3600*72);
			setcookie("area_id", '', time()-1);
			$url = $_COOKIE['___lasturl'];
			setcookie("___lasturl", '', time()-1);
			$this->redrict($url);
		}
		else
		{
			$city_id = $_COOKIE['city_id'];
			if($city_id)
			{
				$city_name = $region = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$city_id." and status=1");
			}
			$ipInfos = (new check())->GetIpLookup(); //baidu.com IP地址  
			//定位
			$key_city = $ipInfos['city'];
			if(!empty($key_city))
			{
				$city = $region = pdo_fetch("SELECT region_name,region_id,parent_id FROM ".tablename('ace_region')." where region_name like '%".$key_city."%' and status=1");
			}
			include $this->template('city');
		}
	}
	
	//企业
    public function doMobileQiye()
	{
	    global $_GPC, $_W;  //全局变量
		(new check())->ckCity();
		include $this->template('qiye');
	}
	
   //发布房源首页
    public function doMobilePublish()
	{
	    global $_GPC, $_W;  //全局变量
		checkauth();
		(new check())->ckCity();
		$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
		//先判断手机号和openid是否在黑名单中
		if(!empty($member))
		{
			$blacklist = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE mobile = '".$member['mobile']."' or openid = '".$_W['fans']['from_user']."'");
		}
		else
		{
			$blacklist = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE openid = '".$_W['fans']['from_user']."'");
		}
		if(!empty($blacklist))
		{
			message('您被拉入黑名单！');
		}
		include $this->template('publish');
	}
	public function doMobileSavePublish()
	{
	    global $_GPC, $_W;  //全局变量
		checkauth();
		ComFunc::startSession();
		(new check())->ckCity();
		//如果提交验证码表单
		if($_GPC['op'] == 'submit')
		{
			if($_GPC['number'])
			{
				pdo_update('ace_members', array('mobile' => $_GPC['number']), array('from_user' => $_W['fans']['from_user']));
			}
			$type = explode('|', $_GPC['type']);
			if($type[0] == 'e')//二手房
			{
				$this->redrict($this->createMobileUrl('ershou_publish', array('type' => $type[1])));
			}
			else if($type[0] == 'c')//出租房
			{
				$this->redrict($this->createMobileUrl('chuzu_publish', array('type' => $type[1])));
			}
			else
			{
				message('来路不明！');
			}
		}
		else
		{
			message('来路不明！');
		}
		
	}
	//二手房发布
	public function doMobileErshou_publish()
	{
		global $_GPC, $_W;
		checkauth();
		$op = $_GPC['op']?$_GPC['op']:'display';
		$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
		//先判断手机号和openid是否在黑名单中
		if(!empty($member))
		{
			$blacklist = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE mobile = '".$member['mobile']."' or openid = '".$_W['fans']['from_user']."'");
		}
		else
		{
			$blacklist = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE openid = '".$_W['fans']['from_user']."'");
		}
		if(!empty($blacklist))
		{
			message('您被拉入黑名单！');
		}
		//查看是不是联盟中介的成员
		$alliance = pdo_fetch("SELECT * FROM ".tablename('ace_alliances_member')." WHERE openid = '".$_W['fans']['from_user']."'");
		if($op == 'display')
		{
			$city_name = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$_COOKIE['city_id']." and status=1");
			$areas = pdo_fetchall("SELECT region_id,region_name FROM ".tablename('ace_region')." where parent_id=".$_COOKIE['city_id']." and status=1");
			$tags = array('1' => '住宅','2' => '别墅','3' => '写字楼','4' => '商铺','5' => '商住两用');
			include $this->template('ershou_publish');
		}
		else if($op == 'submit' && $this->repeatSubmit())
		{
			$data = $_GPC['data'];
			$detail = $_GPC['detail'];
			if(!empty($detail['photo']))
			{
				$photos = explode('|', $detail['photo']);
				if(!empty($photos))
				{
					$photos_arr = array_filter($photos);
					if(!empty($photos_arr))
					{
						$detail['photo'] = implode('|', $photos_arr);
					}
				}
			}
			if(!empty($_GPC['tese']))
			{
				//特色
				$detail['tese'] = implode(' ', $_GPC['tese']);
			}
			if(!empty($alliance))
			{
				$data['source'] = 3;
				$data['a_id'] = $alliance['a_id'];
			}
			//标签
			$data['tags'] = ','.implode(',', $_GPC['tags']).',';
			$data['openid'] = $_W['fans']['from_user'];
			$data['intime'] = time();
			$detail['tel'] = $member['mobile'];
			$data['number'] = ComFunc::createOrderNo();
			pdo_insert('ace_ershou_house', $data);
			$detail['h_id'] = pdo_insertid();
			//匹配网点
			$where = ' where province = '.$data['province'];
			if($data['city'])
			{
				$where .= ' and city = '.$data['city'];
				if($data['area'])
				{
					$where .= ' and area = '.$data['area'];
				}
			}
			$where .= " and including_community like '%".$data['name']."%'";
			$branch = pdo_fetch("SELECT * FROM ".tablename('ace_branch')." ".$where." order by id asc");
			if(!empty($branch))
			{
				$detail['branch_id'] = $branch['id'];
			}
			pdo_insert('ace_ershou_house_details', $detail);
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
	}
	//出租房发布
	public function doMobileChuzu_publish()
	{
		global $_GPC, $_W;
		checkauth();
		$op = $_GPC['op']?$_GPC['op']:'display';
		$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
		//先判断手机号和openid是否在黑名单中
		if(!empty($member))
		{
			$blacklist = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE mobile = '".$member['mobile']."' or openid = '".$_W['fans']['from_user']."'");
		}
		else
		{
			$blacklist = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE openid = '".$_W['fans']['from_user']."'");
		}
		if(!empty($blacklist))
		{
			message('您被拉入黑名单！');
		}
		//查看是不是联盟中介的成员
		$alliance = pdo_fetch("SELECT * FROM ".tablename('ace_alliances_member')." WHERE openid = '".$_W['fans']['from_user']."'");
		if($op == 'display')
		{
			$city_name = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$_COOKIE['city_id']." and status=1");
			$areas = pdo_fetchall("SELECT region_id,region_name FROM ".tablename('ace_region')." where parent_id=".$_COOKIE['city_id']." and status=1");
			$tags = array('1' => '住宅','2' => '别墅','3' => '写字楼','4' => '商铺','5' => '商住两用');
			include $this->template('chuzu_publish');
		}
		else if($op == 'submit')
		{
			$data = $_GPC['data'];
			$detail = $_GPC['detail'];
			if(!empty($detail['photo']))
			{
				$photos = explode('|', $detail['photo']);
				if(!empty($photos))
				{
					$photos_arr = array_filter($photos);
					if(!empty($photos_arr))
					{
						$detail['photo'] = implode('|', $photos_arr);
					}
				}
			}
			if($data['type'] == 1)//住宅
			{
				//配套
				$detail['peitao'] = implode(' ', $_GPC['peitao']);
			}
			if(!empty($alliance))
			{
				$data['source'] = 3;
				$data['a_id'] = $alliance['a_id'];
			}
			//标签
			$data['tags'] = ','.implode(',', $_GPC['tags']).',';
			$data['openid'] = $_W['fans']['from_user'];
			$data['intime'] = time();
			$detail['tel'] = $member['mobile'];
			$data['number'] = ComFunc::createOrderNo();
			pdo_insert('ace_chuzu_house', $data);
			$detail['h_id'] = pdo_insertid();
			//匹配网点
			$where = ' where province = '.$data['province'];
			if($data['city'])
			{
				$where .= ' and city = '.$data['city'];
				if($data['area'])
				{
					$where .= ' and area = '.$data['area'];
				}
			}
			$where .= " and including_community like '%".$data['name']."%'";
			$branch = pdo_fetch("SELECT * FROM ".tablename('ace_branch')." ".$where." order by id asc");
			if(!empty($branch))
			{
				$detail['branch_id'] = $branch['id'];
			}
			pdo_insert('ace_chuzu_house_details', $detail);
			
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
	}
	
	//使用须知
    public function doMobileXuzhi()
	{
	    global $_GPC, $_W;  //全局变量
		(new check())->ckCity();
		checkauth();
		$list = pdo_fetchall("SELECT * FROM ".tablename('ace_jiben_notices')."  ORDER BY torder asc,id DESC");
		include $this->template('xuzhi');
	}
	//使用须知
    public function doMobileXuzhi_info()
	{
	    global $_GPC, $_W;  //全局变量
		(new check())->ckCity();
		checkauth();
		if($_GPC['id'])
		{
			$item = pdo_fetch("SELECT * FROM ".tablename('ace_jiben_notices')."  where id=".$_GPC['id']);
		}
		else
		{
			message('您迷路了，请原路返回吧！');
		}
		include $this->template('xuzhi_info');
	}
	//广告详情
    public function doMobileAds_info()
	{
	    global $_GPC, $_W;  //全局变量
		(new check())->ckCity();
		checkauth();
		if($_GPC['id'])
		{
			$item = pdo_fetch("SELECT * FROM ".tablename('ace_jiben_ads')."  where id=".$_GPC['id']);
		}
		else
		{
			message('您迷路了，请原路返回吧！');
		}
		include $this->template('ads_info');
	}
	//投诉评价
    public function doMobileTousu()
	{
	    global $_GPC, $_W;  //全局变量
		checkauth();
		(new check())->ckCity();
		//如果提交验证码表单
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			if($_GPC['mobile'])
			{
				$mobile = $_GPC['mobile'];
				pdo_update('ace_members', array('mobile' => $mobile), array('from_user' => $_W['fans']['from_user']));
			}
			else
			{
				$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
				$mobile = $member['mobile'];
			}
			$data = $_GPC['data'];
			$data['phone'] = $mobile;
			$data['openid'] = $_W['fans']['from_user'];
			$data['intime'] = time();
			pdo_insert('ace_tousu_pingjia', $data);
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
		else
		{
			$city_name = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$_COOKIE['city_id']." and status=1");
			$province_name = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$_COOKIE['province_id']." and status=1");
			if($_COOKIE['area_id'])
			{
				$area_name = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$_COOKIE['area_id']." and status=1");
			}
			$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
		}
		include $this->template('tousu');
	}
	//我的
    public function doMobileMy()
	{
	    global $_GPC, $_W;  //全局变量
		checkauth();
		$tmp = array('uname'=>'','avatar'=>'');
		$this->wechatUser($tmp);
		(new check())->ckCity();
		include $this->template('my');
	}
	//我的关注
    public function doMobileMyguanzhu()
	{
	    global $_GPC, $_W;  //全局变量
		checkauth();
		$op = $_GPC['op']?$_GPC['op']:'display';
		if($op == 'display')
		{
			$where = " where a.openid='".$_W['fans']['from_user']."'";
			
			//查询我关注的二手房
			$list1s = pdo_fetchall("SELECT e.*,a.url,a.id FROM ".tablename('ace_attention')." a,".tablename('ace_ershou_house')." e ".$where." and a.type = 1 and a.s_id = e.id ORDER BY a.id DESC");
			$total1 = count($list1s);
			
			//查询我关注的出租房
			$list2s = pdo_fetchall("SELECT c.*,a.url,a.id FROM ".tablename('ace_attention')." a,".tablename('ace_chuzu_house')." c ".$where." and a.type = 2 and a.s_id = c.id ORDER BY a.id DESC");
			$total2 = count($list2s);
			
			//查询我关注的新房
			$list3s = pdo_fetchall("SELECT n.*,a.url,a.id FROM ".tablename('ace_attention')." a,".tablename('ace_new_house')." n ".$where." and a.type = 3 and a.s_id = n.id ORDER BY a.id DESC");
			$total3 = count($list3s);
			
			//查询我关注的家政
			$list4s = pdo_fetchall("SELECT j.*,a.url,a.id FROM ".tablename('ace_attention')." a,".tablename('ace_jiazheng_service')." j ".$where." and a.type = 4 and a.s_id = j.id ORDER BY a.id DESC");
			$total4 = count($list4s);
			
			//查询我关注的上门
			$list5s = pdo_fetchall("SELECT s.*,a.url,a.id FROM ".tablename('ace_attention')." a,".tablename('ace_shangmen_merchant')." s ".$where." and a.type = 5 and a.s_id = s.id ORDER BY a.id DESC");
			$total5 = count($list5s);
			
			//查询我关注的包厢
			$list6s = pdo_fetchall("SELECT b.*,a.url,a.id FROM ".tablename('ace_attention')." a,".tablename('ace_baoxiang_resource')." b ".$where." and a.type = 6 and a.s_id = b.id ORDER BY a.id DESC");
			$total6 = count($list6s);
			
			include $this->template('myguanzhu');
		}
		else if($op == 'quxiao')
		{
			$id = $_GPC['id'];
			if($id)
			{
				$f = pdo_delete('ace_attention', array('id' => $id,'openid' =>$_W['fans']['from_user']));
				echo $f;
			}
			else
			{
				echo 0;
			}
		}
	}
	//我的发布
    public function doMobileMyfabu()
	{
	    global $_GPC, $_W;  //全局变量
		checkauth();
		$op = $_GPC['op']?$_GPC['op']:'display';
		if($op == 'display')
		{
			$where = " where openid='".$_W['fans']['from_user']."'";
			//查询我发布的二手房
			$list1s = pdo_fetchall("SELECT * FROM ".tablename('ace_ershou_house').$where." ORDER BY id DESC");
			$total1 = count($list1s);
			
			//查询我发布的出租房
			$list2s = pdo_fetchall("SELECT * FROM ".tablename('ace_chuzu_house').$where." ORDER BY id DESC");
			$total2 = count($list2s);
			
			//查询我发布的新房
			//$list3s = pdo_fetchall("SELECT * FROM ".tablename('ace_new_house').$where." ORDER BY id DESC");
			//$total3 = count($list3s);
			
			//查询我发布的简历
			$list4s = pdo_fetchall("SELECT * FROM ".tablename('ace_jiazheng_resume').$where." ORDER BY id DESC");
			$total4 = count($list4s);
			
			//查询我发布的上门维修
			$list5s = pdo_fetchall("SELECT * FROM ".tablename('ace_shangmen_merchant').$where." ORDER BY id DESC");
			$total5 = count($list5s);
			
			//查询我发布的包厢
			$list6s = pdo_fetchall("SELECT * FROM ".tablename('ace_baoxiang_resource').$where." ORDER BY id DESC");
			$total6 = count($list6s);
			$xing = array('1' => 'one','2' => 'two','3' => 'three','4' => 'four','5' => 'five');
			
			include $this->template('myfabu');
		}
		else if($op == 'xiajia')
		{
			$type = $_GPC['type'];
			$id = $_GPC['id'];
			$data['status'] = '-1';
			if($type == 1)
			{
				pdo_update('ace_ershou_house', $data, array('id' => $id, 'openid'=>$_W['fans']['from_user']));
				echo 1;
			}
			else if($type == 2)
			{
				pdo_update('ace_chuzu_house', $data, array('id' => $id, 'openid'=>$_W['fans']['from_user']));
				echo 1;
			}
			else if($type == 3)
			{
				//pdo_update('ace_jiazheng_resume', $data, array('id' => $id, 'openid'=>$_W['fans']['from_user']));
			}
			else if($type == 4)
			{
				pdo_update('ace_shangmen_merchant', ['status'=>2], array('id' => $id, 'openid'=>$_W['fans']['from_user']));
				echo 1;
			}
			
			else if($type == 5)
			{
				pdo_update('ace_baoxiang_resource', $data, array('id' => $id, 'openid'=>$_W['fans']['from_user']));
				echo 1;
			}
			else
			{
				echo 0;
			}
		}
	}
	
	//我的预约
	public function doMobileMyyuyue()
	{
		global $_GPC, $_W;  //全局变量
		checkauth();
		$op = $_GPC['op']?$_GPC['op']:'display';
		if($op == 'display')
		{
			$where = " where a.openid='".$_W['fans']['from_user']."'";
			
			//查询我预约的二手房
			$list1s = pdo_fetchall("SELECT e.*,a.id as aid FROM ".tablename('ace_ershou_queue')." a,".tablename('ace_ershou_house')." e ".$where." and e.id = a.house_id  ORDER BY a.id DESC");
			$total1 = count($list1s);
			
			//查询我预约的出租房
			$list2s = pdo_fetchall("SELECT c.*,a.id as aid FROM ".tablename('ace_chuzu_queue')." a,".tablename('ace_chuzu_house')." c ".$where." and c.id = a.house_id ORDER BY a.id DESC");
			$total2 = count($list2s);
			
			//查询我预约的新房
			$list3s = pdo_fetchall("SELECT * FROM ".tablename('ace_new_msg')." where openid='".$_W['fans']['from_user']."'  ORDER BY id DESC");
			$typs = array('1'=>'开盘通知','2'=>'变价通知','3'=>'优惠通知');
			$total3 = count($list3s);
			
			//查询我预约的家政
			$list4s = pdo_fetchall("SELECT * FROM ".tablename('ace_jiazheng_look')." where openid='".$_W['fans']['from_user']."'  ORDER BY id DESC");
			$total4 = count($list4s);
			
			include $this->template('myyuyue');
		}
		else if($op == 'quxiao')
		{
			$id = $_GPC['id'];
			$type = $_GPC['type'];
			if($id)
			{
				if($type == 1)
				{
					$f = pdo_delete('ace_ershou_queue', array('id' => $id,'openid' =>$_W['fans']['from_user']));
					echo $f;
				}
				else if($type == 2)
				{
					$f = pdo_delete('ace_chuzu_queue', array('id' => $id,'openid' =>$_W['fans']['from_user']));
					echo $f;
				}
				else if($type == 3)
				{
					$f = pdo_delete('ace_new_msg', array('id' => $id,'openid' =>$_W['fans']['from_user']));
					echo $f;
				}
				else if($type == 4)
				{
					$f = pdo_delete('ace_jiazheng_look', array('id' => $id,'openid' =>$_W['fans']['from_user']));
					echo $f;
				}
				else
				{
					echo 0;
				}
			}
			else
			{
				echo 0;
			}
		}
	}
	//我的投诉
	public function doMobileMytousu()
	{
		global $_GPC, $_W;  //全局变量
		checkauth();
		$op = $_GPC['op']?$_GPC['op']:'display';
		if($op == 'display')
		{
			$where = " where openid='".$_W['fans']['from_user']."'";
			
			//查询通过处理的投诉
			$list1s = pdo_fetchall("SELECT * FROM ".tablename('ace_tousu_pingjia')." ".$where." and status = 1");
			$total1 = count($list1s);
			
			//查询未通过处理的投诉
			$list2s = pdo_fetchall("SELECT * FROM ".tablename('ace_tousu_pingjia')." ".$where." and status = -1");
			$total2 = count($list2s);
			include $this->template('mytousu');
		}
		else if($op == 'del')
		{
			$id = $_GPC['id'];
			if($id)
			{

				$f = pdo_delete('ace_tousu_pingjia', array('id' => $id,'openid' =>$_W['fans']['from_user']));
				echo $f;
			}
			else
			{
				echo 0;
			}
		}
	}
	//红包
	public function doMobileHongbao()
	{
		global $_GPC, $_W;  //全局变量
		checkauth();
		if($_GPC['op'] == 'submit' && $this->repeatSubmit())
		{
			$data = $_GPC['data'];
			$data['openid'] = $_W['fans']['from_user'];
			$data['intime'] = time();
			pdo_insert('ace_uservip_hongbao', $data);
			message('提交成功！', $this->createMobileUrl('index'), 'success');
		}
		$member = pdo_fetch("SELECT mobile FROM ".tablename('ace_members')." WHERE from_user = :from_user" , array(':from_user' => $_W['fans']['from_user']));
		include $this->template('hongbao');
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
	public function SpHtml2Text($str)
	{
	    $str=htmlspecialchars_decode($str);
		$str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU","",$str);
		$alltext = "";
		$start = 1;
		for($i=0;$i<strlen($str);$i++)
		{
			if($start==0 && $str[$i]==">")
			{
				$start = 1;
			}
			else if($start==1)
			{
				if($str[$i]=="<")
				{
					$start = 0;
					$alltext .= " ";
				}
				else if(ord($str[$i])>31)
				{
					$alltext .= $str[$i];
				}
			}
		}
		$alltext = str_replace(""," ",$alltext);
		$alltext = preg_replace("/&([^;&]*)(;|&)/","",$alltext);
		$alltext = preg_replace("/[ ]+/s"," ",$alltext);
		return $alltext;
	}
	private function is_img($url)
	{
	    if(is_file($url))
		{
			return $url;
		}
		else
		{
			return '/resource/image/nopic.jpg';
		}
	}
	
	function sub_str($s,$l)
	{
	 $ls=mb_strlen($s,"UTF-8");
	 if($ls>$l)
	 {
		$str=mb_substr($s,0,$l,"UTF-8").'...';
	 }
	 else
	 {
		$str=mb_substr($s,0,$l,"UTF-8");
	 }
	 return $str;
	}
	
}
?>