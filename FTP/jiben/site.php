<?php
/**
 * 唯商城模块微站定义
 *
 * @author vezhi.net
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class JibenModuleSite extends WeModuleSite {
	/**
	 * 后台-区域管理
	 */
	public function doWebRegion() {
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') 
		{
			if (!empty($_GPC['torder'])) {
				foreach ($_GPC['torder'] as $region_id => $torder) 
				{
					pdo_update('ace_region', array('torder' => $torder), array('region_id' => $region_id));
				}
				message('排序更新成功！', $this->createWebUrl('region', array('op' => 'display')), 'success');
			}			
			$arr_type=$this->cat_tree("ace_region");
			include $this->template('region');
		} 
		elseif ($operation == 'post') 
		{
			$parentid = intval($_GPC['parentid']);
			$region_id = intval($_GPC['region_id']);
			if(!empty($region_id)) {
				$category = pdo_fetch("SELECT * FROM ".tablename('ace_region')." WHERE region_id = '$region_id'");
			} else {
				$category = array(
					'torder' => 0,
				);
			}
			if (!empty($parentid)) {
				$parent = pdo_fetch("SELECT * FROM ".tablename('ace_region')." WHERE region_id = '$parentid'");
				if (empty($parent)) {
					message('抱歉，上级分类不存在或是已经被删除！', $this->createWebUrl('region'), 'error');
				}
			}
			if (checksubmit('submit')) 
			{
				if (empty($_GPC['region_name'])) 
				{
					message('抱歉，请输入名称！');
				}
				$data = array(
					'region_name' => $_GPC['region_name'],
					'torder' => intval($_GPC['torder']),
					'parent_id' => intval($parentid),
					'region_type' => ($_GPC['region_type']=='')?'0':$_GPC['region_type']+1,
				);
				if (!empty($region_id)) 
				{
					unset($data['parent_id']);unset($data['region_type']);
					pdo_update('ace_region', $data, array('region_id' => $region_id));
				} else {
					pdo_insert('ace_region', $data);
					$region_id = pdo_insertid();
				}
				message('更新成功！', $this->createWebUrl('region', array('op' => 'display')), 'success');
			}
			include $this->template('region');
		}
        elseif ($operation == 'status') 
		{
			    $region_id = intval($_GPC['region_id']);
				if (!empty($region_id)) 
				{
				    $data = array(
					'status' => $_GPC['status'],
				    );
					pdo_update('ace_region', $data, array('region_id' => $region_id));
				} 
				message('更新成功！', $this->createWebUrl('region', array('op' => 'display')), 'success');
		}
		elseif ($operation == 'delete') 
		{
			$region_id = intval($_GPC['region_id']);
			$category = pdo_fetch("SELECT region_id FROM ".tablename('ace_region')." WHERE region_id = '$region_id'");
			if (empty($category)) {
				message('抱歉，信息不存在或是已经被删除！', $this->createWebUrl('region', array('op' => 'display')), 'error');
			}
			pdo_delete('ace_region', array('region_id' => $region_id, 'parent_id' => $region_id), 'OR');
			message('分类删除成功！', $this->createWebUrl('region', array('op' => 'display')), 'success');
		}
	}
	/**
	 * 后台-网点管理
	 */
	public function doWebBranch() {
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') 
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' ';
			//条件搜索
			if($_GPC['key'])
			{
				$where = " where name like '%".$_GPC['key']."%' or number like '%".$_GPC['key']."%' or linkman like '%".$_GPC['key']."%' or tel like '%".$_GPC['key']."%' or address like '%".$_GPC['key']."%' or adds like '%".$_GPC['key']."%'";
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_branch')." ".$where." ORDER BY id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_branch').' '.$where);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('branch');
		} 
		elseif ($operation == 'post') 
		{
			$id = intval($_GPC['id']);
			if(!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_branch')." WHERE id = '$id'");
			}
			if (checksubmit('submit')) 
			{
				$data = $_GPC['data'];
				if (empty($data['name'])) 
				{
					message('抱歉，请输入名称！');
				}
				$data['lng'] = $_GPC['lng'];
				$data['lat'] = $_GPC['lat'];
				//获取位置
				if($data['province'])
				{
					$data['adds'] = $this->getRegion($data['province']);
				}
				if($data['city'])
				{
					$data['adds'] .= ' '.$this->getRegion($data['city']);
				}
				if($data['area'])
				{
					$data['adds'] .= ' '.$this->getRegion($data['area']);
				}
				if (!empty($_FILES['pic']['tmp_name'])) {
					$upload = file_upload($_FILES['pic']);
					if (is_error($upload)) {
						message($upload['message'], '', 'error');
					}
					$data['pic'] = $_W['attachurl'].$upload['path'];
				}
				if (!empty($id)) 
				{
					pdo_update('ace_branch', $data, array('id' => $id));
				} else {
					pdo_insert('ace_branch', $data);
				}
				message('更新成功！', $this->createWebUrl('branch', array('op' => 'display')), 'success');
			}
			$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
			include $this->template('branch');
		}
		elseif ($operation == 'delete') 
		{
			$id = intval($_GPC['id']);
			$category = pdo_fetch("SELECT id FROM ".tablename('ace_branch')." WHERE id = '$id'");
			if (empty($category)) {
				message('抱歉，信息不存在或是已经被删除！', $this->createWebUrl('branch', array('op' => 'display')), 'error');
			}
			pdo_delete('ace_branch', array('id' => $id));
			message('删除成功！', $this->createWebUrl('branch', array('op' => 'display')), 'success');
		}
	}
	/**
	 * 后台-登录管理
	 */
	public function doWebLogin() {
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') 
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' ';
			//条件搜索
			if($_GPC['key'])
			{
				$where = " where name like '%".$_GPC['key']."%'";
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_ip')." ".$where." ORDER BY id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_ip').' '.$where);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('login');
		} 
		elseif ($operation == 'post') 
		{
			$id = intval($_GPC['id']);
			if(!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_ip')." WHERE id = '$id'");
			}
			if (checksubmit('submit')) 
			{
				$data = $_GPC['data'];
				if (empty($data['name'])) 
				{
					message('抱歉，请输入名称！');
				}
				if (!empty($id)) 
				{
					pdo_update('ace_ip', $data, array('id' => $id));
				} else {
					pdo_insert('ace_ip', $data);
				}
				message('更新成功！', $this->createWebUrl('login', array('op' => 'display')), 'success');
			}
			include $this->template('login');
		}
		elseif ($operation == 'delete') 
		{
			$id = intval($_GPC['id']);
			$category = pdo_fetch("SELECT id FROM ".tablename('ace_ip')." WHERE id = '$id'");
			if (empty($category)) {
				message('抱歉，信息不存在或是已经被删除！', $this->createWebUrl('login', array('op' => 'display')), 'error');
			}
			pdo_delete('ace_ip', array('id' => $id));
			message('删除成功！', $this->createWebUrl('login', array('op' => 'display')), 'success');
		}
	}
	
	/**
	 * 后台-黑名单管理
	 */
	public function doWebBlacklist() {
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') 
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' where 1 ';
			//条件搜索
			if($_GPC['key'])
			{
				$where .= " and mobile like '%".$_GPC['key']."%' and openid like '%".$_GPC['key']."%'";
			}
			if($_GPC['type'])
			{
				$where .= " and type = " . $_GPC['type'];
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_blacklists')." ".$where." ORDER BY id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_blacklists').' '.$where);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('blacklist');
		} 
		elseif ($operation == 'post') 
		{
			$id = intval($_GPC['id']);
			if(!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_blacklists')." WHERE id = '$id'");
			}
			if (checksubmit('submit')) 
			{
				$data = $_GPC['data'];
				if (empty($data['mobile']) && empty($data['openid'])) 
				{
					message('抱歉，请输入电话或者openid！');
				}
				if (!empty($id)) 
				{
					pdo_update('ace_blacklists', $data, array('id' => $id));
				} else {
					pdo_insert('ace_blacklists', $data);
				}
				message('更新成功！', $this->createWebUrl('blacklist', array('op' => 'display')), 'success');
			}
			include $this->template('blacklist');
		}
		elseif ($operation == 'delete') 
		{
			$id = intval($_GPC['id']);
			$category = pdo_fetch("SELECT id FROM ".tablename('ace_blacklists')." WHERE id = '$id'");
			if (empty($category)) {
				message('抱歉，信息不存在或是已经被删除！', $this->createWebUrl('blacklist', array('op' => 'display')), 'error');
			}
			pdo_delete('ace_blacklists', array('id' => $id));
			message('删除成功！', $this->createWebUrl('blacklist', array('op' => 'display')), 'success');
		}
	}
	
	/**
	 * 后台-联盟中介管理
	 */
	public function doWebAlliance() {
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') 
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' where 1 ';
			//条件搜索
			if($_GPC['key'])
			{
				$where .= " and name like '%".$_GPC['key']."%'";
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_alliances')." ".$where." ORDER BY id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_alliances').' '.$where);
			$pager = pagination($total, $pindex, $psize);
			include $this->template('alliance');
		} 
		elseif ($operation == 'post') 
		{
			$id = intval($_GPC['id']);
			if(!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_alliances')." WHERE id = '$id'");
			}
			if (checksubmit('submit')) 
			{
				$data = $_GPC['data'];
				if (empty($data['name'])) 
				{
					message('抱歉，请输入联盟中介名称！');
				}
				if (!empty($id)) 
				{
					pdo_update('ace_alliances', $data, array('id' => $id));
				} else {
					pdo_insert('ace_alliances', $data);
				}
				message('更新成功！', $this->createWebUrl('alliance', array('op' => 'display')), 'success');
			}
			include $this->template('alliance');
		}
		elseif ($operation == 'delete') 
		{
			$id = intval($_GPC['id']);
			$category = pdo_fetch("SELECT id FROM ".tablename('ace_alliances')." WHERE id = '$id'");
			if (empty($category)) {
				message('抱歉，信息不存在或是已经被删除！', $this->createWebUrl('alliance', array('op' => 'display')), 'error');
			}
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_alliances_member').' where a_id = '.$id);
			if($total == 0)
			{
				pdo_delete('ace_alliances', array('id' => $id));
				message('删除成功！', $this->createWebUrl('alliance', array('op' => 'display')), 'success');
			}
			else
			{
				message('抱歉，该联盟中介存在成员，请先删除其成员！');
			}
		}
	}
	
	/**
	 * 后台-联盟成员管理
	 */
	public function doWebAlliance_member() {
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') 
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' where 1 ';
			//条件搜索
			if($_GPC['key'])
			{
				$where .= " and name like '%".$_GPC['key']."%' and openid like '%".$_GPC['key']."%'";
			}
			if($_GPC['a_id'])
			{
				$where .= " and a_id = ".$_GPC['a_id'];
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_alliances_member')." ".$where." ORDER BY id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_alliances_member').' '.$where);
			$pager = pagination($total, $pindex, $psize);
			$alliances = pdo_fetchall("SELECT * FROM ".tablename('ace_alliances'));
			include $this->template('alliance_member');
		} 
		elseif ($operation == 'post') 
		{
			$id = intval($_GPC['id']);
			if(!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_alliances_member')." WHERE id = '$id'");
			}
			if (checksubmit('submit')) 
			{
				$data = $_GPC['data'];
				if (empty($data['a_id'])) 
				{
					message('抱歉，请选择中介！');
				}
				if (empty($data['name'])) 
				{
					message('抱歉，请输入成员名称！');
				}
				if (empty($data['openid'])) 
				{
					message('抱歉，请输入成员openid！');
				}
				if (!empty($id)) 
				{
					pdo_update('ace_alliances_member', $data, array('id' => $id));
					//如果更换联盟
					if($item['a_id'] != $data['a_id'])
					{
						$u_data = array('source' => 3, 'a_id' => $data['a_id']);
						pdo_update('ace_chuzu_house', $u_data, array('openid' => $data['openid']));
						pdo_update('ace_ershou_house', $u_data, array('openid' => $data['openid']));
					}
				} else {
					pdo_insert('ace_alliances_member', $data);
					$u_data = array('source' => 3, 'a_id' => $data['a_id']);
					pdo_update('ace_chuzu_house', $u_data, array('openid' => $data['openid']));
					pdo_update('ace_ershou_house', $u_data, array('openid' => $data['openid']));
				}
				message('更新成功！', $this->createWebUrl('alliance_member', array('op' => 'display')), 'success');
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_alliances'));
			include $this->template('alliance_member');
		}
		elseif ($operation == 'delete') 
		{
			$id = intval($_GPC['id']);
			$category = pdo_fetch("SELECT id FROM ".tablename('ace_alliances_member')." WHERE id = '$id'");
			if (empty($category)) {
				message('抱歉，信息不存在或是已经被删除！', $this->createWebUrl('alliance_member', array('op' => 'display')), 'error');
			}
			pdo_delete('ace_alliances_member', array('id' => $id));
			$u_data = array('source' => 2, 'a_id' => '');
			pdo_update('ace_chuzu_house', $u_data, array('openid' => $category['openid']));
			pdo_update('ace_ershou_house', $u_data, array('openid' => $category['openid']));
			message('删除成功！', $this->createWebUrl('alliance_member', array('op' => 'display')), 'success');
		}
	}
	
	//获取中介名称
	public function getAlliance($id)
	{
		global $_GPC, $_W;
		if($id)
		{
			$region = pdo_fetch("SELECT name FROM ".tablename('ace_alliances')." where id=".$id);
			return $region['name'];
		}
	}

	//获取区域
	public function doWebGetAllRegions()
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
	public function getRegion($id)
	{
		global $_GPC, $_W;
		if($id)
		{
			$region = pdo_fetch("SELECT region_name FROM ".tablename('ace_region')." where region_id=".$id." and status=1");
			return $region['region_name'];
		}
	}
    public function cat_tree($tables, $cat_id = 0, $level = 0) {
		global $res_func_cat;
		$result = pdo_fetchall("SELECT * FROM ".tablename($tables)." WHERE parent_id = '$cat_id'  ORDER BY status desc, torder asc");
		//显示每个子节点
		foreach ($result as $row) {
			//取得层级level
			$res_func_cat[$row['region_id']]=$row;
			$res_func_cat[$row['region_id']]['level']=$level;
			//递归
			$this->cat_tree($tables, $row['region_id'], $level+1);
		}
		return $res_func_cat;
	}
}