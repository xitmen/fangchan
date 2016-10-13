<?php
defined('IN_IA') or exit('Access Denied');
use ACE\Module\Common\ComVerControl;
use ACE\Module\Common\ComFunc;
require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';
class JibenFrontModel extends WeModuleSite{
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
			self::$__this=new IntegalFrontModel($p);
		}
		self::$__this->userauth();
		return self::$__this;
	}
	/**
	 * 产生订单编号 yam 2014年4月18日
	 * @return string 返回订单编号
	 */
	private function createOrderNo(){
		$time=explode(" ",microtime());
		$now=date('YmdHms',time());
		$micro=$time[0]*1000000;
		return $now.str_pad($micro,'6','0',STR_PAD_LEFT);		
	}
	/**
	 * Session 启用方法，在使用session的页面中需先调用此方法
	 * 调用方法 $this->startSession();
	 * @author yam 2014年4月16日
	 */
	private function startSession($first=false){
		global $_W,$_GPC;
		@session_start();
		if(!empty($_W['fans']['from_user']) && $_SESSION['weid']!=$_W['weid']){
			$fans=pdo_fetch("SELECT id FROM ".tablename('ace_members')." WHERE `from_user`=:f_user and `u_id` =:weid",array(':f_user'=>$_W['fans']['from_user'],':weid'=>$_W['weid']));
			if(!empty($fans)){
				$_SESSION['fromUser']=$fans['id'];
				$_SESSION['weid']=$_W['weid'];
			}
			else{
			    if($first) return;
				setcookie('___lasturl',$_SERVER["REQUEST_URI"], time()+3600, '/');
				$this->redrict('mobile.php?act=module&name=usersvip&do=Binding&weid='.$_W['weid']);
				exit;
			}
		}
	}
	/*******************以上为类核心代码请勿修改 yam 2014年4月15日******************************************************************************************************/
	//前台订单状态定义
	private $CUS_ORDER_STATUS=array(
			-1=>'<span style="color:#B70000;">申请取消中</span>',
			0=>'<span style="color:#999;">订单关闭</span>',
			1=>'<span style="color:red;">未付款</span>',
			2=>'<span style="color:#FF6600;">卖家未发货</span>',
			3=>'<span style="color:blue;">卖家已发货</span>',
			4=>'<span style="color:green;">未评论</span>',
			5=>'<span style="color:#aaa;">已评论</span>'
	);
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
	//获取当前用户购物车商品数量 yam 2014年4月17日
	public function doMobileGetCar(){
		global $_GPC, $_W;  //全局变量
		$this->startSession();
		if(!isset($_SESSION['fromUser']) || empty($_SESSION['fromUser']) || !isset($_SESSION['islogin']) || empty($_SESSION['islogin']) || $_SESSION['islogin']=='0'){
			echo '{"status":500}';  //没有登录
			exit;
		}
		$fromUser=$_SESSION['fromUser'];
		$count = pdo_fetchcolumn('select count(*) as `cnt` from '.tablename('ace_orders_products')." where `u_id`=:weid and `wecha_id`=:fid and `car_stat`=1",array(':weid'=>$_W['weid'],':fid'=>$fromUser));
		echo '{"status":200,"count":'.(int)$count.'}';
	}
	/**
	 * 产生一条订单并返回订单id array
	 * @return array 订单id,订单No
	 */
	private function createOrder(){
		global $_GPC, $_W;  //全局变量
		//产生一条订单
		//insert into `ace_orders`
		$orderNo=$this->createOrderNo();
		$o=pdo_insert('ace_integal_orders',array(
			'u_id'=>$_W['weid'],	//商户id
			'totl'=>0,	//订单总价
			'bianhao'=>$orderNo,	//订单编号
			'jiaoyihao'=>'-1',  //交易号
			'address'=>'',  //收货地址
			'wecha_id'=>$_SESSION['fromUser'],  //用户id
			'intime'=>TIMESTAMP,  //下单时间
			'status'=>1  //订单状态
		));
		//获取订单主键
		$oid=pdo_insertid();
		if($o && $oid){
			return array('oid'=>$oid,'orderNo'=>$orderNo);
		}
		return null;
	}
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
				message('修改资料成功！',$this->createMobileUrl('My'),'success');
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
		//var_dump($wc_userinfo);
		//获取用户微信头像及昵称
		if(empty($udata['uname']) || strtolower($udata['uname'])=='null')
			$udata['uname']=$wc_userinfo['nickname'];
		$udata['avatar']=$wc_userinfo['headimgurl'];
	}
	//立即购买+添加到购物车 yam 2014年4月17日
	public function doMobileBuy(){
		error_reporting(1);
		$this->checkLogin(); //验证是否登录
		global $_GPC, $_W;  //全局变量
		if(checksubmit('token')){  //判断是提交过来的数据
			$action=$_GPC['action'];
			$isajax=$_GPC['ajax'];  //是否是ajax提交数据
			$postdata=$_GPC['data'];
			//数据验证
			if(!preg_match("/^\d+$/",$postdata['size']) || !preg_match("/^\d+$/",$postdata['color']) || !preg_match("/^\d+$/",$postdata['count']) || !preg_match("/^\d+$/",$postdata['gid'])){
				if(empty($isajax) || $isajax==null || trim($isajax)==''){
					message('无效数据提交！',referer(), 'error');
					exit;
				}
				else {
					echo '{"status":500,"msg":"提交的数据无效！"}';
					exit;
				}
			}
			$good=pdo_fetch("select * from ".tablename('ace_integal_products')." where `id`=:id and `u_id`=:wid and `status`=1",array(':id'=>$postdata['gid'],':wid'=>$_W['weid']));
			if(empty($good)){
				if(empty($isajax) || $isajax==null || trim($isajax)==''){
					message('产品已删除或者已下架！',$this->createMobileUrl('home'),'error');
					exit;
				}
				else {
					echo '{"status":500,"msg":"商品已删除或已下架！"}';
					exit;
				}
			}
			$lstsize=explode('|',$good['sizes']);
			$lstcolor=explode('|',$good['colors']);
			$data=array();
			$orderNo=$this->createOrderNo();
			$data['p_id']=$good['id'];// int(11) NOT NULL comment '产品序列',
			$data['u_id']=$_W['weid']; //weid
			$data['name']=$good['name'];// varchar(500) NOT NULL comment '产品名称',
			$data['num']=$postdata['count'];// int(11) NOT NULL comment '购买数量',
			$data['price']=$good['price'];// decimal(10,2) NOT NULL comment '产品价格',
			$data['pic']=$good['pic'];// varchar(500) NOT NULL comment '产品图片',
			$data['wecha_id']=$_SESSION['fromUser'];// int(11) NOT NULL comment 'members id',
			$data['info']='尺寸:'.$lstsize[$postdata['size']].';颜色:'.$lstcolor[$postdata['color']]; // varchar(100) NOT NULL comment '选购属性 颜色|尺寸',
			$data['bianhao']=$orderNo;	//订单编号
			$data['jiaoyihao']='-1';  //交易号
			$data['address']='';//收货地址?
			$data['totl']=(float)$good['price']*(int)$data['num'];//订单总价?
			$data['intime']=TIMESTAMP;  //下单时间
			$data['status']=1;  //订单状态
			if($action==="buy"){	//立即购买
				$r=pdo_insert('ace_integal_orders',$data);
				
				if($r){  //确认数据提交成功
					//跳转到确认订单页面
						$this->redrict($this->createMobileUrl("Confrim",array('orderNo'=>$orderNo)));
				}
				else{
					message('下订单失败！请联系客服咨询！',$this->createMobileUrl('home'),'error');
					exit;					
				}
			}
		}
	}
	//订单操作  取消订单  订单支付  确认收货  查看订单   yam 2014-04-24
	public function doMobileConfrim(){
		error_reporting(1);
		//判断用户是否登陆
		global $_GPC,$_W;
		$this->checkLogin();
		//获取订单编号
		$oid=$_GPC['orderNo'];
		//获取当前登陆用户的id  $_SESSION['fromUser']
		$uid=$_SESSION['fromUser'];
		//查询订单 $order
		if(!preg_match("/^\d+$/",$oid)){  //验证订单编号是否合法
			message('订单不存在，请确认来路正确！errorno:1',$this->createMobileUrl('home'),'error');
			exit;
		}
		//确认订单为当前用户及商户订单
		$order=pdo_fetch("select * from ".tablename('ace_integal_orders')." where `bianhao`=:bh and `u_id`=:wid and `wecha_id`=:uid",
				array(':bh'=>$oid,':wid'=>$_W['weid'],':uid'=>$uid));
		if(empty($order)){
			message('订单不存在，请确认来路正确！errorno:2',$this->createMobileUrl('home'),'error');
			exit;
		}
		if(checksubmit('token')){
			//提交数据
			$action=$_GPC['o'];
			if(!empty($action) && $action!=null){
				if($action=="pay"){
					$addr_id=$_GPC['r1'];
					$confirm_price=(int)$order['totl'];  //真实付款金额 扣除优惠
					//查询该用户的总积分
					$info=pdo_fetch("select totalcredit from ".tablename('ace_members')." where `id`=:uid",
							array(':uid'=>$uid));
					if($info['totalcredit']<$confirm_price)
					{
					 message('订单兑换失败，您的积分不足！errorno:1',$this->createMobileUrl('my'),'error');
					}
					$update_sql="";     //更新 优惠券使用数量 产品销量 及 用户消费总额 sql
					//验证地址
					$addr=pdo_fetch("select * from ".tablename('ace_address')." where `id`=:aid and `m_id`=:uid",
							array(':aid'=>$addr_id,':uid'=>$uid));
					if(!empty($addr)){
						
						//更新产品销量 及 验证
						$prd=pdo_fetch("select num,p_id,name from ".tablename('ace_integal_orders')." where `id`=:oid and `u_id`=:wid and `wecha_id`=:uid",
								array(':oid'=>$order['id'],':wid'=>$_W['weid'],':uid'=>$uid));
						if(empty($prd) || $prd=="" || $prd==null){
							message('订单兑换失败，请联系在线客服！errorno:1',$this->createMobileUrl('my'),'error');
							exit;							
						}
						$data=array();
						$data['status']=2;
						$data['jiaoyihao']="[Test]".date('Y-m-d',TIMESTAMP);  //支付宝返回的虚假交易号
						$data['address']=$addr['name'].' '.$addr['phone'].' '.$addr['address'].' '.$addr['youbian'];
						
						//产品销量  及  库存
						$update_sql.="update ".tablename('ace_integal_products')." set `xiaoliang`=`xiaoliang`+".$prd['num'].",`kucun`=`kucun`-".$prd['num']." where id=".$prd['p_id'].";\r\n";
						//用户消费积分
						$update_sql.="update ".tablename('ace_members')." set `totalcredit`=`totalcredit`-".$confirm_price." where `id`=".$uid.";";
						//echo $update_sql;exit;
						if(pdo_run($update_sql)){
							message('订单兑换失败，请联系在线客服！errorno:2',$this->createMobileUrl('my'),'error');
							exit;							
						}
						if(pdo_update('ace_integal_orders',$data,array('id'=>$order['id']))){
						   //积分入账单库
								 $datass['wecha_id']=intval($uid);
								 $datass['u_id']=intval($_W['weid']);
								 $datass['info']='【订单号：'.$oid.'】'.$prd['name'];
								 $datass['value']=$confirm_price;
								 $datass['intime']=time();
								 $datass['type']='5';
								 $datass['class']='1';
								 $datass['symbol']='-';
								 pdo_insert('ace_bill', $datass);
							message('订单兑换成功！',$this->createMobileUrl('my'),'success');
							exit;					
						}
						else{
							message('订单兑换失败，请联系在线客服！errorno:3',$this->createMobileUrl('my'),'error');
							exit;			
						}
					}
					else{
						message('地址查询失败，请联系在线客服！',$this->createMobileUrl('my'),'error');
						exit;						
					}
				}
				elseif($action=='cancel'){
					if(pdo_update('ace_integal_orders',array('status'=>-1),array('id'=>$order['id']))){
						message('订单申请取消成功！请等待客服确认！',$this->createMobileUrl('my'),'success');
						exit;
					}
					else{
						message('订单申请取消失败，请联系在线客服！',$this->createMobileUrl('my'),'error');
						exit;
					}
				}
				elseif($action=='receive'){
					if(pdo_update('ace_integal_orders',array('status'=>4),array('id'=>$order['id']))){
						message('订单确认收货成功！赶快去评论下吧！',$this->createMobileUrl('my'),'success');
						exit;
					}
					else{
						message('订单确认收货失败，请联系在线客服！',$this->createMobileUrl('my'),'error');
						exit;
					}
				}
				else{
					message('Oops, 404 Not Found!',$this->createMobileUrl('home','error'));
					exit;
				}
			}
		}
		else{
			// 通过 $order['id'] 获取对应的产品   $item
			$item=pdo_fetch("select * from ".tablename('ace_integal_orders')." where `id`='".$order['id']."'");
			//通过用户id查找对应地址   $addrs
			$addrs=pdo_fetchall("select * from ".tablename('ace_address')." where `m_id`='".$uid."'");
			if(empty($addrs) || count($addrs)==0){
			    setcookie('___last_url',$_SERVER["REQUEST_URI"], time()+3600, '/');
				message('您还没有设置收货地址，请先去设置收货地址！<br/>然后再到订单管理中去支付！',$this->createMobileUrl('address'),'tips');
				exit;				
			}
			
			include $this->template('order');
		}	
	}
	//我的购物车
	public function doMobileShopCar(){
		global $_GPC,$_W;
		//验证是否登录
		$this->checkLogin();
		//获取登陆用户id
		$uid=$_SESSION['fromUser'];
		if(checksubmit('token')){
			$post_item=$_GPC['item'];
			if(is_array($post_item)){
				$pid="";
				$totalprice=0;
				if($_GPC['op']=='del'){   //删除购物车商品
					foreach ($post_item as $key=>$val){
						if(isset($val['checked']) && $val['checked']=='1'){
							if(!is_integer($key)) continue; //订单编号必须为数字
						}
						$pid.=$key.',';
					}
					$pid=substr($pid,0,strlen($pid)-1);
					if($pid!=""){ 
						if(pdo_query("delete from ".tablename('ace_orders_products')." where `id` in (".$pid.") and `u_id`=:wid and `wecha_id`=:uid",
								array(':wid'=>$_W['weid'],':uid'=>$uid))){
							message("购物车商品删除成功！",$this->createMobileUrl("ShopCar"),'success');
							exit;
						}
						else {
							message("购物车商品删除失败！请联系客服！",$this->createMobileUrl("ShopCar"),'error');
							exit;							
						}
					}
				}
				elseif($_GPC['op']=='buy') {  //从购物车下订单
					foreach ($post_item as $key=>$val){
						if(isset($val['checked']) && $val['checked']=='1'){
							if(!is_integer($key)) continue; //订单编号必须为数字
							//验证数据库是否存在该 订单产品						
							$good=pdo_fetch("select * from ".tablename("ace_orders_products")." where `id`=:id ".
									"and `u_id`=:weid and `wecha_id`=:mid and `car_stat`=1",
									array(':id'=>$key,':weid'=>$_W['weid'],':mid'=>$uid));
							if(!empty($good) && $good!=null){
								$pid.=$key.',';  //订单产品编号
							}
							else {
								continue;	//不存在该订单产品项
							}
							if(((int)$val['num'])==0) $val['num']=1;  //提交产品数量不为数字
							//更新订单产品表中 产品数量以及价格
							//update `ims_ace_orders_products` set `price`=`price`/`num`*1,`num`=1 where `car_stat`=1 and `wecha_id`=11
							$tmp=pdo_query("update ".tablename('ace_orders_products')." set `price`=`price`/`num`*".
									((int)$val['num']).",`num`=".((int)$val['num']).
									" where `id`=".$key." and `wecha_id`=".$uid." and `u_id`=".$_W['weid']);
						}
					}
					if($pid==""){
						message('无效参数，请检查访问来路！',$this->createMobileUrl('home'),'error');
						exit;
					}
					$pid=substr($pid,0,strlen($pid)-1);
					//订单总价
					//select SUM(`price`) from `ims_ace_orders_products` where `id` in (56,57,59) and `u_id`=1 and `wecha_id`=11 and car_stat=1 group by `wecha_id`
					$totalprice=pdo_fetchcolumn("select SUM(`price`) from ".tablename('ace_orders_products')." where ".
							"`id` in (".$pid.") and `u_id`=".$_W['weid']." and `wecha_id`=".$uid." and car_stat=1 ".
							"group by `wecha_id`");
					
					//插入新订单
					$order=$this->createOrder();
					if($order==null){
						message('下订单失败！请联系客服咨询！',$this->createMobileUrl('home'),'error');
						exit;
					}
					//更新所有订单产品项 订单id  取消购物车状态 并 更新订单总价
					if(pdo_query("update ".tablename('ace_orders')." set `totl`=:totl where `id`=:oid and `u_id`=:uid and `wecha_id`=:wid",
							array(':oid'=>$order['oid'],':uid'=>$_W['weid'],':wid'=>$uid,':totl'=>$totalprice))){
						if(pdo_query("update ".tablename('ace_orders_products')." set `car_stat`=0,`o_id`=:oid ".
							"where `id` in (".$pid.") and `u_id`=:uid and `wecha_id`=:wid;",
							array(':oid'=>$order['oid'],':uid'=>$_W['weid'],':wid'=>$uid))){
							message('下订单成功！3秒后自动跳转到支付页面！',
							$this->createMobileUrl('Confrim',array('orderNo'=>$order['orderNo'])),'success');
						}
						else {
							message('下订单失败！请联系客服咨询！',$this->createMobileUrl('home'),'error');
							exit;
						}
					}
					else {
						message('下订单失败！请联系客服咨询！',$this->createMobileUrl('home'),'error');
						exit;
					}
				}
			}
		}
		//查询购物车列表
		$item = pdo_fetchall("SELECT * FROM ".tablename('ace_orders_products')."  WHERE u_id ='".$_W['weid']."' and wecha_id=".$uid." and car_stat=1 ");
		include $this->template('shopcar');
	}
	//前台首页
	public function doMobileHome(){
		global $_GPC, $_W;  //全局变量
		$product_types = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_product_types')."  WHERE u_id ='".$_W['weid']."' and parent_id=0 and status=1 order by torder asc limit 0,8");//产品类别
		$t_ads = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_ads')."  WHERE u_id ='".$_W['weid']."' and type_id=1 order by id desc");//头部广告
		$x_ads = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_products')."  WHERE u_id ='".$_W['weid']."' order by xiaoliang desc limit 0,3");
		//exit;

		//查看 唯积分 是否安装
		//$jifen_a = pdo_fetch("SELECT mid FROM ".tablename('modules')."  WHERE title ='积分商城'");
		if(empty($_W['account']['modules']['integal']))
		{
		 //$jifen = array();
		 message('积分商城暂时没有安装！','','tips');
		}else{//查看积分商城 有没有启用
		 $jifen_q = pdo_fetch("SELECT id FROM ".tablename('site_nav')."  WHERE module ='integal' and weid=".$_W['weid']);
		 if(empty($jifen_q)){
		    //$jifen = array();
		    message('该店铺暂时没有启用积分商城！','','tips');
		 }else{
		    //$jifen = array('1');
		 }
		}
		//查看唯商城 是否安装
		//$shch_a = pdo_fetch("SELECT mid FROM ".tablename('modules')."  WHERE title ='购物商城'");
		if(empty($_W['account']['modules']['veshop']))
		{
		 $shch = array();
		}else{//查看唯商城 有没有启用
		 $shch_q = pdo_fetch("SELECT id FROM ".tablename('site_nav')."  WHERE module ='veshop' and weid=".$_W['weid']);
		 if(empty($shch_q)){
		    $shch = array();
		 }else{
		    $shch = array('1');
		 }
		}
		
		include $this->template('index');
	}
	public function doMobileLogout(){
		$this->checkLogin();
		session_destroy();
		message('退出成功！',$this->createMobileUrl('home'),'success');
	}
	//微信访问用户 注册新账户
	//微信访问用户 注册新账户
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
			$card = pdo_fetch("select id from ".tablename('ace_usersvip_card')." where u_id=".$_W['weid']." order by class asc");
			if(!empty($card)){$udata['class']=$card['id'];}
			$udata['pay_pwd']='123123';
			$this->startSession();
			$u=pdo_insert('ace_members',$udata);
			$_SESSION['fromUser']=pdo_insertid();
			$_SESSION['islogin']="1";
			message('注册微信会员成功，已经为您登录！钱包默认支付密码是：123123',$this->createMobileUrl('home'),'success');
			exit;
		}
		else {
			message('入口异常！',$this->createMobileUrl('home'), 'error');
		}
	} 
	public function doMobileBinding(){
		global $_GPC, $_W;  //全局变量
		checkauth();//校验是否已经关注当前微信公众平台
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
					$this->startSession();
					$_SESSION['fromUser']=$user['id'];
					$_SESSION['islogin']="1";
					message('绑定微信会员成功，已经为您登录！',$this->createMobileUrl('home'),'success');
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
			message('您的微信账户已绑定过！',$this->createMobileUrl('home'), 'error');	
		}
		
	}
	
	/**
	 * 前台登录 yam 2014年4月16日
	 */
	public function doMobileLogin(){
		global $_GPC, $_W;  //全局变量
		$this->startSession();
		if(checksubmit('token')){
			$udata=$_GPC['data'];
			$udata['password']=$this->getpass($udata['password']);
			$user = pdo_fetch("SELECT id FROM ".tablename('ace_members')." WHERE `uname`=:uname and `password`=:pass and `u_id` =:weid",
					array(':uname' => $udata['uname'],
							':pass'=>$udata['password'],
							':weid'=>$_W['weid']));
			if($user){
				$_SESSION['fromUser']=$user['id'];
				$_SESSION['islogin']="1";
				message('登录成功！',$this->createMobileUrl('home'),'success');				
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
				message('注册成功，已经为您登录！',$this->createMobileUrl('home'),'success');			
			}
			else{
				message('您填写的用户名已存在，请重新输入！',referer(), 'error');
			}
		}
		else{
			include $this->template('register');
		}
	}
	
	public function doMobileAds(){//广告
		global $_GPC, $_W;		
		$id = intval($_GPC['id']);
		$x_ads = pdo_fetch("SELECT * FROM ".tablename('ace_integal_ads')."  WHERE id =".$id." and u_id ='".$_W['weid']."'");//底部广告
		include $this->template('ads');
	}
	public function doMobileProduct_type_zis(){//产品类别子类页面
		global $_GPC, $_W;
		
		$t_ads = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_ads')."  WHERE u_id ='".$_W['weid']."' and type_id=1 order by id desc");//头部广告
		
		$cid = intval($_GPC['cid']?$_GPC['cid']:0);
		$product_types = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_product_types')."  WHERE u_id ='".$_W['weid']."' and parent_id=".$cid." and status=1 order by torder asc");//产品类别
		foreach($product_types as $k=>$product_type)//查找该类下的所有子类
		{
		  $p_types = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_product_types')."  WHERE u_id ='".$_W['weid']."' and parent_id=".$product_type['id']." and status=1 order by torder asc");//产品类别
		  $product_types[$k]['subclass'] = $p_types;
		}
		
		$class = pdo_fetch("SELECT * FROM ".tablename('ace_integal_product_types')."  WHERE u_id ='".$_W['weid']."' and id=".$cid." order by id desc");//查找该类信息
		
		//当前类下热销产品处理 获取当前类下所有的子类id
		$ids = $this->cat_trees($_W['weid'], 'ace_product_types',$cid);
		$rexiao_products = pdo_fetchall("SELECT id,pic,price,xiaoliang FROM ".tablename('ace_integal_products')."  WHERE u_id ='".$_W['weid']."' and status=1 and type_id in(".$ids.") order by xiaoliang desc limit 0,3");
		include $this->template('class');
	}
	public function doMobileProduct_lists(){//产品列表页面
		global $_GPC, $_W;
		$cid = intval($_GPC['cid']?$_GPC['cid']:'0');
		$t_ids = $this->cat_trees($_W['weid'], 'ace_integal_product_types',$cid);//价格统计用
		//获取该类所有的兄弟类 
		if($cid=='0'){
		 $class['parent_id']=0;
		}else{
		$class = pdo_fetch("SELECT parent_id FROM ".tablename('ace_integal_product_types')."  WHERE id=$cid ");
		}
		$class = pdo_fetchall("SELECT id,name FROM ".tablename('ace_integal_product_types')."  WHERE u_id ='".$_W['weid']."' and parent_id=".$class['parent_id']." and status=1 order by torder asc");
		
		if($_GPC['action']=='px')//排序
		{
			$px=array(
			'0'=>' order by yulan desc ',
			'1'=>' order by intime desc ',
			'2'=>' order by price desc ',
			'3'=>' order by price asc ',
			'4'=>' order by r_num desc ',
			'5'=>' order by xiaoliang desc ');
			//获取当前类下所有的子类id
			$ids = $this->cat_trees($_W['weid'], 'ace_integal_product_types',$cid );
			$where = " and type_id in (".$ids.") ".$px[$_GPC['a']];
		}
		elseif($_GPC['action']=='sx')//筛选
		{
			$arr=array();
			foreach($class as $k=>$clas){$arr[$k]=$clas['id'];}
			$sx=array(array(
			'0'=>' and (price between 0 and 499) order by intime desc ',
			'1'=>' and (price between 500 and 999) order by intime desc ',
			'2'=>' and (price between 1000 and 1499)  order by intime desc ',
			'3'=>' and (price between 1500 and 1999)  order by intime desc ',
			'4'=>' and (price between 2000 and 2999)  order by intime desc ',
			'5'=>' and price>=3000 order by intime desc ',),$arr);
			if($_GPC['a']=='1')//按类别筛选
			{
			 //获取当前类下所有的子类id
			 $ids = $this->cat_trees($_W['weid'], 'ace_integal_product_types',$sx[$_GPC['a']][$_GPC['b']] );
			 $where = " and type_id in (".$ids.") order by intime desc";
			}
			else if($_GPC['a']=='0')//价格别筛选
			{
			 //获取当前类下所有的子类id
			 $ids = $this->cat_trees($_W['weid'], 'ace_integal_product_types',$cid );
			 $where = $sx[$_GPC['a']][$_GPC['b']];
			}
		}
		else{
		//获取当前类下所有的子类id
		$ids = $this->cat_trees($_W['weid'], 'ace_integal_product_types',$cid );
		$where = " and type_id in (".$ids.") order by intime desc";
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_products') . " WHERE u_id =".$_W['weid']."  and status=1 $where");
		$pager = pagination($total, $pindex, $psize);
		$products = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_products')."  WHERE u_id ='".$_W['weid']."' and status=1 $where LIMIT ".($pindex - 1) * $psize.','.$psize);//产品

		
		include $this->template('product_lists');
	}
	public function doMobileProduct(){//产品详细页面
		global $_GPC, $_W;
		$gid = intval($_GPC['gid']);
		$goods = pdo_fetch("SELECT * FROM ".tablename('ace_integal_products')."  WHERE u_id ='".$_W['weid']."' and status=1 and id =".$gid);//产品
		$t_ads = explode('|',$goods['photos']);$k_p=explode('|',$goods['express']);
		$goods['express']="快递：¥{$k_p[0]} EMS：¥{$k_p[1]}平邮：¥{$k_p[2]}";
		$sizes = explode('|',$goods['sizes']);
		$colors = explode('|',$goods['colors']);
		//评价
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_review') . " WHERE  p_id=".$gid." and u_id =".$_W['weid']);
		$pager = pagination($total, $pindex, $psize);
		$reviews = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_review')."  WHERE p_id ='".$gid."' and u_id=".$_W['weid']."  LIMIT ".($pindex - 1) * $psize.','.$psize);//产品
		include $this->template('goods');
	}
	public function doMobileSearch(){//产品搜索
        global $_GPC, $_W;
		
		$where = ' and name like "%'.$_GPC['key'].'%" order by intime desc';


		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_products') . " WHERE u_id =".$_W['weid']."  and status=1 $where");
		$pager = pagination($total, $pindex, $psize);
		$products = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_products')."  WHERE u_id ='".$_W['weid']."' and status=1 $where LIMIT ".($pindex - 1) * $psize.','.$psize);//产品
		include $this->template('search');
	}
	public function doMobileReview(){//评论
        global $_GPC, $_W;
		$this->checkLogin();  //检查用户是否登录
		$uid=$_SESSION['fromUser'];
		
		$product = pdo_fetch("SELECT * FROM ".tablename('ace_integal_orders')."  WHERE id =".$_GPC['id']);
		if($_GPC['submit'])
		{
		 $member = pdo_fetch("SELECT * FROM ".tablename('ace_members')."  WHERE id =".$uid);//查询访问者表
		 //插入评论表中
		 $data['xing'] = $_GPC['xing'];
		 $data['content'] = $_GPC['content'];
		 $data['p_id'] = $product['p_id'];
		 $data['wecha_id'] = $uid;
		 $data['u_id'] = $_W['weid'];
		 $data['intime'] = TIMESTAMP;
		 $data['info'] = $product['info'];
		 $data['wname'] = $member['uname'];
		 $data['pic'] = $member['avatar'];
		 $f = pdo_insert('ace_integal_review', $data);
		 if($f)//插入成功
		 {
		    //修改产品订单表对应的状态
			pdo_update('ace_integal_orders', array('is_pingjia' => '1','status' => '5'), array('id' => $_GPC['id']));
			//修改该产品的评价数量+1
			mysql_query('update '.tablename('ace_integal_products').' set r_num=r_num+1 where id='.$product['p_id']);
			message('评论成功！', $this->createMobileUrl('order_lists'), 'success');
		 }
		 else{
		    message('评论失败！', $this->createMobileUrl('order_lists'), 'error');
		 }
		}else{
		 $info = pdo_fetch("SELECT * FROM ".tablename('ace_members')."  WHERE id =".$uid);
		}
		include $this->template('review');
	}
	/**
	 * 移除数组中的某个元素
	 * @param array 数组变量
	 * @param int 元素索引
	 */
	private function arrayRm(&$arr,$index){
		array_splice($arr,$index,1);
	}
	
	//added: 修改前台显示风格及样式  修改代码   yam 2014-04-23
	public function doMobileCoupon(){//我的优惠劵
        global $_GPC, $_W;
		$this->checkLogin();  //检查用户是否登录
		$uid=$_SESSION['fromUser'];
		if(!empty($_GPC['op']) && $_GPC['op']=='get' && !empty($_GPC['cpid']) && preg_match("/^\d+$/",$_GPC['cpid']))
		{
			//验证是否是正确的优惠券
			$it=pdo_fetch("select * from ".tablename('ace_coupon')." where `id`=:id and `u_id`=:uid and `status`=1",array(':id'=>$_GPC['cpid'],':uid'=>$_W['weid']));
			//验证是否已经领取过
			$has=pdo_fetch("select * from ".tablename('ace_cp_member')." where `u_id`=:wid and `wecha_id`=:uid and `cp_id`=:cid",array(':wid'=>$_W['weid'],':uid'=>$uid,':cid'=>$it['id']));
			if(!empty($it) && $it!=null && empty($has)){
				pdo_insert('ace_cp_member',array('u_id'=>$_W['weid'],'wecha_id'=>$uid,'cp_id'=>$it['id'])); //领取优惠券
			}
		}
		$info = pdo_fetch("SELECT * FROM ".tablename('ace_members')." WHERE id =".$uid);
		//查询用户所有的优惠券
		$coupon = pdo_fetchall("SELECT * FROM ".tablename('vw_cm')." WHERE u_id ='".$_W['weid']."' and wecha_id=".$uid." and `status`=1 order by `endtime`");
		$delids="";    //超过期限3天要删除的
		$uncoupon=array();  //失效的优惠券集合
		$cpids=array();  //已领取优惠券id集合
		foreach ($coupon as $k=>$v){
			//使用次数超过限定  或  超过优惠券有效期
			if($v['limitcount']<=$v['usecount'] || $v['endtime']<TIMESTAMP){
				$coupon[$k]['canuse']=0;
				array_push($uncoupon,$v);
				if(((int)$v['endtime']+259200)<=TIMESTAMP){  //超过设定期限3天
					$delids.=$v['id'].',';   //要清楚的 ims_ace_cp_member id
				}
			}
			else{
				$coupon[$k]['canuse']=1;
			}
			array_push($cpids,$v['cpid']);	//已领取的优惠券存入
		}
		//清理已失效的优惠券
		if($delids!=""){
			@pdo_query("delete from ".tablename('ace_cp_member')." where id in (".substr($delids,0,strlen($delids)-1).")");
		}
		$sql_in="";
		$cancp=array();
		if(count($cpids)>0){
			if(count($cpids)==1)
				$sql_in=" `id`!=".$cpids[0]." and ";
			else
				$sql_in=" `id` not in (".implode(',',$cpids).")"." and ";
		}
		//查询还可以领取的优惠券
		$cancp = pdo_fetchall("SELECT * FROM ".tablename('ace_coupon')."  WHERE ".
				$sql_in.
				"u_id ='".$_W['weid']."' and ".
				"`endtime`>".TIMESTAMP." and `status`=1 ".
				"order by `endtime`");
			
		include $this->template('coupon');
	}
	public function doMobileCoupon3(){//我的积分劵
        global $_GPC, $_W;
		$this->checkLogin();  //检查用户是否登录
		$uid=$_SESSION['fromUser'];
		
		$info = pdo_fetch("SELECT * FROM ".tablename('ace_members')." WHERE id =".$uid);
		//查询用户所有的积分劵
		$coupon = pdo_fetchall("SELECT * FROM ".tablename('vw_cm')." WHERE u_id ='".$_W['weid']."' and wecha_id=".$uid." and type=3");	
		include $this->template('coupon3');
	}
	/**
	 * 前台-我的订单
	 */
	public function doMobileMy(){
		global $_GPC, $_W;  //全局变量
		//判断用户是微信用户还是直接访问用户
		$this->checkLogin();  //检查用户是否登录
		$uid=$_SESSION['fromUser'];
		$info = pdo_fetch("SELECT * FROM ".tablename('ace_members')."  WHERE id =".$uid);
		//订单数量统计
		$orders_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_orders') . " WHERE wecha_id =".$uid);
		//未付款数量统计
		$wfk_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_orders') . " WHERE wecha_id =".$uid." and status=1");
		//待发货数量统计
		$fh_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_orders') . " WHERE wecha_id =".$uid." and status=2");
		//待评价数量统计
		$pj_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_orders'). " WHERE wecha_id =".$uid." and is_pingjia=0 and status=4");
		//收货地址数量统计
		$address_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_address') . " WHERE m_id =".$uid);
		//积分劵数量统计
		$coupon3_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('vw_cm') . " WHERE u_id =".$_W['weid']." and wecha_id=$uid and type=3");
		include $this->template('my');
	}
	public function doMobileOrder_lists()
	{
	    global $_GPC, $_W;  //全局变量
	    //判断用户是微信用户还是直接访问用户
		$this->checkLogin();  //检查用户是否登录
		$uid=$_SESSION['fromUser'];
		$info = pdo_fetch("SELECT * FROM ".tablename('ace_members')."  WHERE id =".$uid);
		//查找所有的订单
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		if($_GPC['status']=='4')
		{
		  $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_orders'). " WHERE  wecha_id =".$uid." and is_pingjia=0 and status=4");
		  $pager = pagination($total, $pindex, $psize);
		  $orders = pdo_fetchall("SELECT o.*,w.name FROM ".tablename('ace_integal_orders').' o,'.tablename('wechats').' w'."  WHERE o.wecha_id ='".$uid."'  and o.status=4 and o.is_pingjia=0 and o.u_id=w.weid  order by o.id desc LIMIT ".($pindex - 1) * $psize.','.$psize);//产品
		}else
		{
		 if(isset($_GPC['status']))
		 {
		  $where = ' and o.status='.$_GPC['status'];
		  $pwhere = ' and status='.$_GPC['status'];
		 }
		 $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_integal_orders') . " WHERE wecha_id =".$uid.$pwhere);
		 $pager = pagination($total, $pindex, $psize);
		 $orders = pdo_fetchall("SELECT o.*,w.name FROM ".tablename('ace_integal_orders').' o,'.tablename('wechats').' w'."  WHERE o.wecha_id ='".$uid."' and o.u_id=w.weid $where order by o.id desc LIMIT ".($pindex - 1) * $psize.','.$psize);//产品
		}		
		$STATUS=$this->CUS_ORDER_STATUS;
	    include $this->template('order_lists');
	}
	public function doMobileAddress(){//收货地址
		global $_GPC, $_W;
		$this->checkLogin();
		//$this->startSession();
		$uid=$_SESSION['fromUser'];
		$info = pdo_fetch("SELECT * FROM ".tablename('ace_members')."  WHERE id =".$uid);
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'post') {
			//增查地址
			$id = intval($_GPC['id']);
			if (!empty($id)) {
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_address')." WHERE id = :id" , array(':id' => $id));
				if (empty($item)) {
					message('抱歉，地址不存在或是已经删除！', '', 'error');
				}
			}
			
			if (isset($_GPC['submit'])) {
			    $data=$_GPC['data'];
				$data['m_id'] = intval($uid);
				
				if (empty($id)) {
				    pdo_update('ace_address', array('state' => '0'), array('m_id' => intval($uid)));
				    $data['state']=1;
					pdo_insert('ace_address', $data);
				} else {
					pdo_update('ace_address', $data, array('id' => $id));
				}
				if($_COOKIE['___last_url'])
				{
				   $___last_url=$_COOKIE['___last_url'];
				   setcookie('___last_url',$_SERVER["REQUEST_URI"], time(), '/');
				   message('地址更新成功！', $___last_url, 'success');
				}
				else{
				   message('地址更新成功！', $this->createMobileUrl('address', array('op' => 'display')), 'success');
				}
			}
		} elseif ($operation == 'display') {
			$condition = '';
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_address')." WHERE m_id = '".intval($uid)."' $condition ORDER BY  id DESC ");
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_address')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，地址不存在或是已经被删除！');
			}			
			pdo_delete('ace_address', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}elseif ($operation == 'state') {
			//增查地址
			$id = intval($_GPC['id']);
			if (!empty($id)) {
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_address')." WHERE id = :id" , array(':id' => $id));
				if (empty($item)) {
					message('抱歉，地址不存在或是已经删除！', '', 'error');
				}
			}
				pdo_update('ace_address', array('state' => '0'), array('m_id' => intval($uid)));
				pdo_update('ace_address', array('state' => '1'), array('id' => $id));
				message('地址设置默认成功！', $this->createMobileUrl('address', array('op' => 'display')), 'success');

		}
		include $this->template('address');
	}
	//自定义函数
	private function doMobileProduct_types($id){//判断该类下是否还存在子类
		global $_GPC, $_W;
		$class = pdo_fetch("SELECT id FROM ".tablename('ace_integal_product_types')."  WHERE status=1 and parent_id =".$id);
		if(!empty($class))
		{
			return 'Product_type_zis';//类别页
		}else{
			return 'Product_lists';//产品列表页
		}
	}	
	private function cat_trees($uid, $tables,$cat_id) {
		global $res_func_cats;
		$result = pdo_fetchall("SELECT * FROM ".tablename($tables)." WHERE parent_id = '$cat_id' and u_id = '{$uid}' and status=1 ORDER BY torder");
		//显示每个子节点
		foreach ($result as $row) {
			//取得层级level
			$res_func_cats[]=$row['id'];
			//递归
			$this->cat_trees($uid,$tables,$row['id']);
		}
		$ids=$res_func_cats;
		$ids[]=$cat_id;
		$ids = implode(",",$ids);
		return $ids;
	}
	private function Number($table,$where)//统计数量
	{
	 global $_GPC, $_W;
	 $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($table) . " WHERE u_id = '{$_W['weid']}' $where");
	 return $total;
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
	public function doMobileOrder_product()//订单产品详细
	{
	 global $_GPC, $_W;
	 $o_id=$_GPC['o_id'];$status=$_GPC['status'];
	 $str='';
	 $results = pdo_fetchall("SELECT * FROM ".tablename('ace_integal_orders')." WHERE id = '$o_id'");
	 foreach($results as $result)
	 {
	            $str.='<div class="product_list">';
				  $str.='<div class="img">';
				  $str.='<img src="'.$this->is_img('./resource/attachment/'.$result['pic']).'"/>';
				  $str.='</div>';
				  $str.='<div class="info">';
				     $str.='<div class="name">'.$result['name'].'</div>';
					 $str.='<div class="price">总积分:<img src="./source/modules/integal/style/images/jb.png" >'.$result['price'].'</div>';
					 $str.='<div class="num">数量:'.$result['num'].'</div>';
					 $str.='<div class="in">产品信息:<b>'.$result['info'].'</b></div>';
					 if($status=='4' && $result['is_pingjia']=='0')
					 {
					  $str.='<div class="in"><b><a href="'.$this->createMobileUrl('review', array('id' => $result['id'])).'" style="line-height:1.5em;">去评价</a></b></div>';
					 }
					  else if($status=='5' && $result['is_pingjia']=='1')
					 {					  
					   $str.='<div class="in"><b><a style="color:green;">已评价</a></b></div>';
					 }
				  $str.='</div>';
				  $str.='</div>';
	 }
	 echo $str;
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