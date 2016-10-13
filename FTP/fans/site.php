<?php
/**
 * 粉丝管理模块微站定义
 *
 * @author ACE
 */
defined('IN_IA') or exit('Access Denied');

class FansModuleSite extends WeModuleSite {
	
	
	

	
		//查询用户所在分组 
		//用户openid
	public function dofindusergroup($useropenid){
		global $_GPC,$_W;
		//获取access_token
		$wc_acceount=$_W['account'];
		$access_token= account_weixin_token($wc_acceount);
		$openid=$_W['fans']['from_user'];
		$usergroup = '{"openid":"{$useropenid}"}';
		$usergroupurl="https://api.weixin.qq.com/cgi-bin/groups/getid?access_token={$access_token}";
		$usergroupcontent=ihttp_post($usergroupurl,$usergroup);
		$wc_usergroup = @json_decode($usergroupcontent['content'], true);
				if($wc_usergroup){
			
			}else{
				message('查询失败', referer(), 'error');}
		
		}
	//修改分组名称
	public function doeditgroupcgroup($id,$editname){
		global $_GPC,$_W;
		//获取access_token
		$wc_acceount=$_W['account'];
		$access_token= account_weixin_token($wc_acceount);
		$editgroupcgroup = '{"group":{"id":"{$id}","name":"{$editname}"}}';
		$editgroupurl="https://api.weixin.qq.com/cgi-bin/groups/update?access_token={$access_token}";
		$editgroupcontent=ihttp_post($editgroupurl,$editgroupcgroup);
		$wc_editgroup = @json_decode($editgroupcontent['content'], true);
		$wc_usergroup = @json_decode($usergroupcontent['content'], true);
		if($wc_usergroup){
			   message('修改成功', referer(), 'error');
			}else{
				message('修改失败，请重新修改', referer(), 'error');}
		
		
		}
		
		//移动用户分组
	public function domoveusergroup($openid,$groupid){
		global $_GPC,$_W;
		//获取access_token
		$wc_acceount=$_W['account'];
		$access_token= account_weixin_token($wc_acceount);
		$openid=$_W['fans']['from_user'];
		
		$moveusergroupcgroup = '{"openid":"oDF3iYx0ro3_7jD4HFRDfrjdCM58","to_groupid":108}';
		$moveusergroupurl="https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token={$access_token}";
		$moveusergroupcontent=ihttp_post($moveusergroupurl,$moveusergroupcgroup);
		$wc_moveusergroup = @json_decode($moveusergroupcontent['content'], true);
		if($wc_usergroup){
			   message('移动成功', referer(), 'error');
			}else{
				message('移动失败，请重新移动', referer(), 'error');}
		}	
	public function doWebDisplay() {
		global $_GPC, $_W;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 50;
		
		$where = '';
		$starttime = empty($_GPC['start']) ? strtotime('-1 month') : strtotime($_GPC['start']);
		$endtime = empty($_GPC['end']) ? TIMESTAMP : strtotime($_GPC['end']) + 86399;
		$where .= " AND createtime >= '$starttime' AND createtime < '$endtime'";
		
		$fields = pdo_fetchall("SELECT field, title FROM ".tablename('profile_fields'), array(), 'field');
		$select = array();
		if (!empty($_GPC['select'])) {
			foreach ($_GPC['select'] as $field) {
				if (isset($fields[$field])) {
					$select[] = $field;
				}
			}
		}
		
		$list = pdo_fetchall("SELECT from_user, weid, createtime ".(!empty($select) ? ",`".implode('`,`', $select)."`" : '')." FROM ".tablename('fans')." WHERE follow = 1 AND weid = '{$_W['weid']}' AND from_user <> '' $where ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('fans')." WHERE follow = 1 AND weid = '{$_W['weid']}' $where ");
		$pager = pagination($total, $pindex, $psize);
		include $this->template('display');
	}

	public function doWebAsyn() {
		global $_GPC, $W;
		include $this->template('asyn');
	}
	
	public function doWebProfile() {
		global $_W, $_GPC;
		$from_user = $_GPC['from_user'];
		if (checksubmit('submit')) {
			if (!empty($_GPC)) {
				foreach ($_GPC as $field => $value) {
					if (!isset($value) || in_array($field, array('from_user','act', 'name', 'token', 'submit', 'session'))) {
						unset($_GPC[$field]);
						continue;
					}
				}
				fans_update($from_user, $_GPC);
			}
			message('更新资料成功！', referer(), 'success');
		}
		$form = array(
			'birthday' => array(
				'year' => array(date('Y'), '1914'),
			),
			'bloodtype' => array('A', 'B', 'AB', 'O', '其它'),
			'education' => array('博士','硕士','本科','专科','中学','小学','其它'),
			'constellation' => array('水瓶座','双鱼座','白羊座','金牛座','双子座','巨蟹座','狮子座','处女座','天秤座','天蝎座','射手座','摩羯座'),
			'zodiac' => array('鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'),
		);
		$profile = fans_search($from_user);
		include $this->template('profile');
	}

	public function doWebGroup() {
		 global $_GPC,$_W;
		$wc_acceount=$_W['account'];
		$access_token= account_weixin_token($wc_acceount);
		
		$pindex = max(1, intval($_GPC['page']));
		$psize = 50;
		$where = '';
		$starttime = empty($_GPC['start']) ? strtotime('-1 month') : strtotime($_GPC['start']);
		$endtime = empty($_GPC['end']) ? TIMESTAMP : strtotime($_GPC['end']) + 86399;
		$where .= " AND createtime >= '$starttime' AND createtime < '$endtime'";
		 $select = array();
		  
		 
		 
		//获取分组列表
		$groupurl="https://api.weixin.qq.com/cgi-bin/groups/get?access_token={$access_token}";
		$groupcontent=ihttp_get($groupurl);
		$wc_group = @json_decode($groupcontent['content'], true);
		//var_dump($wc_group);
		//查询粉丝列表
		$list = pdo_fetchall("SELECT from_user,nickname,avatar,weid, createtime ".(!empty($select) ? ",`".implode('`,`', $select)."`" : '')." FROM ".tablename('fans')." WHERE follow = 1 AND weid = '{$_W['weid']}' AND from_user <> '' $where ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
		//获取粉丝昵称及头像
		foreach ($list as $v){
			$openid=$v['from_user'];
					$url="https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
		$content=ihttp_get($url);
		$wc_userinfo = @json_decode($content['content'], true);
		$u=pdo_update('fans',array('nickname'=>$wc_userinfo['nickname'],'avatar'=>$wc_userinfo['headimgurl']),array('from_user'=>$v['from_user']));
			}
		//用户分页
		$pindex = max(1, intval($_GPC['page']));
		$psize = 50;
		$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('fans')." WHERE follow = 1 AND weid = '{$_W['weid']}' $where ");
		$pager = pagination($total, $pindex, $psize);
		
		include $this->template('group'); 
	}

	public function doWebLocation() {
		message('用户地理位置功能可以近似实时的获取粉丝用户的位置信息, 方便更好的进行针对性营销. 此功能正在开发中, 近两日会通过自动更新功能发布.');
	}

	public function doWebSettings() {
		message('粉丝管理选项, 这里可以与公众平台的粉丝数据进行同步. 此功能正在开发中, 近两日会通过自动更新功能发布.');
	}

	public function doMobileProfile() {
		global $_W, $_GPC;
		if (empty($_W['fans']['from_user'])) {
			message('非法访问，请重新点击链接进入个人中心！');
		}
		$title = '我的资料';
		if (checksubmit('submit')) {
			if (!empty($_GPC)) {
				$from_user = $_W['fans']['from_user'];
				foreach ($_GPC as $field => $value) {
					if (!isset($value) || in_array($field, array('from_user','act', 'name', 'token', 'submit', 'session'))) {
						unset($_GPC[$field]);
						continue;
					}
				}
				fans_update($from_user, $_GPC);
			}
			message('更新资料成功！', referer(), 'success');
		}
		$profile = fans_search($_W['fans']['from_user']);

		$form = array(
			'birthday' => array(
				'year' => array(date('Y'), '1914'),
			),
			'bloodtype' => array('A', 'B', 'AB', 'O', '其它'),
			'education' => array('博士','硕士','本科','专科','中学','小学','其它'),
			'constellation' => array('水瓶座','双鱼座','白羊座','金牛座','双子座','巨蟹座','狮子座','处女座','天秤座','天蝎座','射手座','摩羯座'),
			'zodiac' => array('鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'),
		);
		include $this->template('profile');
	}

	public function doMobileRequire($fields = array(), $forward = '') {
		global $_W, $_GPC;
		if (empty($_W['fans']['from_user'])) {
			message('非法访问，请重新点击链接进入个人中心！');
		}
		$title = '完善资料';

		if (checksubmit('submit')) {
			$from_user = $_W['fans']['from_user'];
			$record = array_elements($fields, $_GPC);
			foreach ($record as $field => $value) {
				if (in_array($field, array('from_user','act', 'name', 'token', 'submit', 'session'))) {
					unset($record[$field]);
				}
				if(empty($value)) {
					message('请填写完整所有资料.', referer(), 'error');
				}
			}
			fans_update($from_user, $record);
		} else {
			$profile = fans_search($_W['fans']['from_user'], $fields);
			$form = array(
				'birthday' => array(
					'year' => array(date('Y'), '1914'),
				),
				'bloodtype' => array('A', 'B', 'AB', 'O', '其它'),
				'education' => array('博士','硕士','本科','专科','中学','小学','其它'),
				'constellation' => array('水瓶座','双鱼座','白羊座','金牛座','双子座','巨蟹座','狮子座','处女座','天秤座','天蝎座','射手座','摩羯座'),
				'zodiac' => array('鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'),
			);
			include $this->template('require');
			exit;
		}
	}
}
