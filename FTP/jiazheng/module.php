<?php
/**
 * 家政模块定义
 * @author lihui
 * @url
 */
defined('IN_IA') or exit('Access Denied');
use ACE\Module\Common\ComFunc;
use ACE\Module\Common\ReturnMsg;
use ACE\Module\Common\L;
require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';  //版本控制类
require_once IA_ROOT.'/source/modules/acecomm.inc.php';  //通用方法类
require_once IA_ROOT.'/source/modules/aceerrmsg.inc.php';  //通用返回信息类
require_once IA_ROOT.'/source/modules/common.inc.php';
class JiazhengModule extends WeModule {
	public function __construct(){
		L::debug(E_ALL); //开启异常调试模式，异常可在 ./debug/?list 中查看
		global $_W;
	}
	/**
	 * 出租房源管理
	**/
	public function doService ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'post')
		{
			$id = intval($_GPC['id']);
			if (!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_jiazheng_service')." WHERE id = :id" , array(':id' => $id));
				if (empty($item)) {
					message('抱歉，信息不存在或是已经删除！', '', 'error');
				}
				//网点条件、
				if($item['province'])
				{
					$where = ' where province = '.$item['province'];
					if($item['city'])
					{
						$where .= ' and city = '.$item['city'];
						if($item['area'])
						{
							$where .= ' and area = '.$item['area'];
						}
					}
				}
			}
			if (checksubmit('submit'))
			{
			    $data = $_GPC['data'];
				$data['lng'] = $_GPC['lng'];
				$data['lat'] = $_GPC['lat'];
				if (!empty($_FILES['pic']['tmp_name'])) {
					$upload = file_upload($_FILES['pic']);
					if (is_error($upload)) {
						message($upload['message'], '', 'error');
					}
					$data['pic'] = $_W['attachurl'].$upload['path'];
				}
				if (empty($id))
				{
					pdo_insert('ace_jiazheng_service', $data);
				} 
				else
				{
					pdo_update('ace_jiazheng_service', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('service', array('op' => 'display')), 'success');
			}
		} 
		elseif($operation == 'display')
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' where 1 ';
			if($_GPC['key'])
			{
				$where .= " and name like '%".$_GPC['key']."%'";
			}
			if($_GPC['status'])
			{
				$where .= " and status = ".$_GPC['status'];
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
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_jiazheng_service')." ".$where." ORDER BY  torder asc,id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_jiazheng_service')." ".$where);
			$pager = pagination($total, $pindex, $psize);
		}
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_jiazheng_service')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_jiazheng_service', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		elseif ($operation == 'paixu') {
			$ids = $_GPC['ids'];
			foreach($ids as $k=>$id)
			{
			 pdo_update('ace_jiazheng_service', array('torder'=>$id), array('id' => $k));
			}
			message('排序成功！', referer(), 'success');
		}
		$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
		include $this->template('service');	
	}
	
	/**
	 * 工作展示管理
	**/
	public function doPhoto ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$id = $_GPC['id'];
		if(empty($id))
		{
			message('抱歉，路径不正确！');
		}
		$item = pdo_fetch("SELECT * FROM ".tablename('ace_jiazheng_service')." WHERE id = :id" , array(':id' => $id));
		if (empty($item)) {
			message('抱歉，信息不存在或是已经删除！', '', 'error');
		}
		if ($operation == 'post')
		{
			if (checksubmit('submit'))
			{
				if (!empty($_FILES['pic']['tmp_name'])) {
					$upload = file_upload($_FILES['pic']);
					if (is_error($upload)) {
						message($upload['message'], '', 'error');
					}
					if($item['photo'])
					{
						$data['photo'] = $item['photo'].'|'.$_W['attachurl'].$upload['path'];
					}
					else
					{
						$data['photo'] = $_W['attachurl'].$upload['path'];
					}
					pdo_update('ace_jiazheng_service', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('photo', array('op' => 'display', 'id' => $id)), 'success');
			}
		} 
		elseif($operation == 'display')
		{
			if($item['photo'])
			{
				$list = explode('|', $item['photo']);
			}
			else
			{
				$list = array();
			}
		}
		elseif($operation == 'delete')
		{
			$url = $_GPC['url'];
			$list = explode('|', $item['photo']);
			$pics = array();
			foreach($list as $k=>$v)
			{
				if($v != $url)
				{
					$pics[] = $v;
				}
			}
			if(!empty($pics))
			{
				$data['photo'] = implode('|', $pics);
			}
			else
			{
				$data['photo'] = '';
			}
			pdo_update('ace_jiazheng_service', $data, array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('photo');	
	}
	/**
	 * 简历管理
	**/
	public function doResume ()
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
				$where .= " and (name like '%".$_GPC['key']."%' or xueli like '%".$_GPC['key']."%' or gzjy like '%".$_GPC['key']."%' or gangwei like '".$_GPC['key']."')";
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
			if($_GPC['BeginDate'])
			{
				$where .= " and BeginDate >= ".strtotime($_GPC['BeginDate']);
			}
			if($_GPC['EndDate'])
			{
				$where .= " and EndDate <= ".strtotime($_GPC['EndDate']);
			}
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_jiazheng_resume')." ".$where." ORDER BY  id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_jiazheng_resume')." ".$where);
			$pager = pagination($total, $pindex, $psize);
			$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
		}
		else if ($operation == 'post')
		{
			$id = $_GPC['id'];
			$item = pdo_fetch("SELECT * FROM ".tablename('ace_jiazheng_resume')." WHERE id = :id" , array(':id' => $id));
			if (empty($item)) {
				message('抱歉，信息不存在或是已经删除！', '', 'error');
			}
			if (checksubmit('submit'))
			{
			    $data = $_GPC['data'];
				$data['BeginDate'] = strtotime($_GPC['BeginDate']);
				$data['EndDate'] = strtotime($_GPC['EndDate']);
				if (empty($id))
				{
					pdo_insert('ace_jiazheng_resume', $data);
				} 
				else
				{
					pdo_update('ace_jiazheng_resume', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('resume', array('op' => 'display')), 'success');
			}
		} 
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_jiazheng_resume')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_jiazheng_resume', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('resume');	
	}
	/**
	 * 帮找家政管理
	**/
	public function doLook ()
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
				$where .= " and (fwxm like '%".$_GPC['key']."%' or phone like '%".$_GPC['key']."%')";
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
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_jiazheng_look')." ".$where." ORDER BY  id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_jiazheng_look')." ".$where);
			$pager = pagination($total, $pindex, $psize);
			$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
		}
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_jiazheng_look')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_jiazheng_look', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('look');	
	}
	//获取区域
	public function doGetAllRegions()
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
}
