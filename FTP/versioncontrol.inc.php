<?php
namespace ACE\Module\Common;
require_once dirname(__FILE__).'/acecomm.inc.php';
require_once dirname(__FILE__).'/aceerrmsg.inc.php';
/**
 * 添加版本控制 每个子版本父类
 * @author yam add 2015年01月21日
 * 使用方法：
use ACE\Module\Common\ComVerControl;
require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';

class TestModuleSite extends ComVerControl {
	private function dealWithVersion(){
		global $_W;
	    if(in_array($_W['weid'],array(13,24,5))){  //当weid为13,24,5调用自定义版本
		    //require_once  dirname(__FILE__).'/version/myversion.ver.php';
		    $vertn=MyVersion::E($this);
		    if($vertn===true){
			    exit;
		    }
		}
	}
	public function doMobileHome(){
		self::dealWithVersion(); //此方法加入版本控制，进行版本判断
		//do something...
		//默认版本处理的代码
	}
	public function doMobileLogin(){
		//所有版本通用的类似登陆这类的方法，如果有版本差异处理需
		//添加版本控制，并在相应版本加入 doLogin() 方法；
	}
}

class MyVersion extends ComVerControl {
	const INNER_VERSION ='3.3.11';
	public static function E($p,$invoke=true){
		if(self::$__this==null){
			self::$__this=new AceJYVer($p);
			self::$super=$p;
		}
		if($invoke){
			return self::C();
		}
		return self::$__this;
	}
	public function doHome(){
		//do something...
		//自定义版本处理的代码
	}
}
 */
class ComVerControl extends \WeModuleSite {
	protected static $__this = null;
	protected static $super = null;
	/**
	 * 析构函数 在创建版本子类时传入当前对象 $this
	 * @param unknown_type $p 当前对象$this
	 * @author yam add 2015年01月21日
	 */
	public function __construct($p = null) {
		if ($p == null)
			return;
		$this->module = $p->module;
		$this->weid = $p->weid;
		$this->inMobile = $p->inMobile;
	}
	/**
	 * 自动执行子类函数
	 * @author yam add 2015年01月21日
	 */
	protected static function C() {
		global $_GPC;
		if(!empty($_GPC['act']) && $_GPC['act']=='entry' && !empty($_GPC['eid'])){
			$menu=pdo_fetch('select * from '.tablename('modules_bindings').' where `eid`=:eid',array(':eid'=>$_GPC['eid']));
			if(!empty($menu)){
				$_GPC['do']=$menu['do'];
			}
			unset($menu);
		}
		if(empty($_GPC['do'])) return false;
		$method=$_GPC['do'];
		if(!empty($method)){
			$method='do'.ucfirst($method);
			if(method_exists(self::$__this,$method)){
				call_user_func(array(	self::$__this,$method));
				return true;
			}else{
				ob_clean();
				return false;
			}
		}
	}
}
