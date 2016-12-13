<?php
/**
 * 包厢模块定义
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
class BaoxiangModule extends WeModule {
	public function __construct(){
		L::debug(E_ALL); //开启异常调试模式，异常可在 ./debug/?list 中查看
		global $_W;
	}
	/**
	 * 包厢管理
	**/
	public function doHouses ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if($_COOKIE['province'])
		{
			$_GPC['province'] = $_COOKIE['province'];
		}
		if($_COOKIE['city'])
		{
			$_GPC['city'] = $_COOKIE['city'];
		}
		if ($operation == 'post')
		{
			$id = intval($_GPC['id']);
			if (!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_baoxiang_resource')." WHERE id = :id" , array(':id' => $id));
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
				if (!empty($_FILES['pic']['tmp_name'])) {
					$upload = file_upload($_FILES['pic']);
					if (is_error($upload)) {
						message($upload['message'], '', 'error');
					}
					$data['pic'] = $_W['attachurl'].$upload['path'];
				}
				if (empty($id))
				{
					pdo_insert('ace_baoxiang_resource', $data);
				} 
				else
				{
					pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('houses', array('op' => 'display')), 'success');
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
			if($_GPC['type'])
			{
				$where .= " and type = ".$_GPC['type'];
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
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_baoxiang_resource')." ".$where." ORDER BY  torder asc,id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_baoxiang_resource')." ".$where);
			$pager = pagination($total, $pindex, $psize);
		}
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_baoxiang_resource')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_baoxiang_resource', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		elseif ($operation == 'paixu') {
			$ids = $_GPC['ids'];
			foreach($ids as $k=>$id)
			{
			 pdo_update('ace_baoxiang_resource', array('torder'=>$id), array('id' => $k));
			}
			message('排序成功！', referer(), 'success');
		}
		$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
		if($_GPC['province'])
		{
			$citys = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=".$_GPC['province']." and status=1 ORDER BY torder asc,region_id DESC");
		}
		if($_GPC['city'])
		{
			$areas = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=".$_GPC['city']." and status=1 ORDER BY torder asc,region_id DESC");
		}
		require_once IA_ROOT.'/source/modules/baoxiang/tags.php';  //引入标签
		$_STATUS = array(
				'-1' => '未发布',
				'1' => '已发布',
			);
		include $this->template('houses');	
	}
	
	/**
	 * 相册管理
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
		$item = pdo_fetch("SELECT * FROM ".tablename('ace_baoxiang_resource')." WHERE id = :id" , array(':id' => $id));
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
					pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
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
			pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('photo');	
	}
	/**
	 * 酒店大堂相册管理
	**/
	public function doDatang ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$id = $_GPC['id'];
		if(empty($id))
		{
			message('抱歉，路径不正确！');
		}
		$item = pdo_fetch("SELECT * FROM ".tablename('ace_baoxiang_resource')." WHERE id = :id and type = :type" , array(':id' => $id, ':type' => 1));
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
					if($item['datang_photo'])
					{
						$data['datang_photo'] = $item['datang_photo'].'|'.$_W['attachurl'].$upload['path'];
					}
					else
					{
						$data['datang_photo'] = $_W['attachurl'].$upload['path'];
					}
					pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('datang', array('op' => 'display', 'id' => $id)), 'success');
			}
		} 
		elseif($operation == 'display')
		{
			if($item['datang_photo'])
			{
				$list = explode('|', $item['datang_photo']);
			}
			else
			{
				$list = array();
			}
		}
		elseif($operation == 'delete')
		{
			$url = $_GPC['url'];
			$list = explode('|', $item['datang_photo']);
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
				$data['datang_photo'] = implode('|', $pics);
			}
			else
			{
				$data['datang_photo'] = '';
			}
			pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('datang');	
	}
	/**
	 * 菜谱相册管理
	**/
	public function doCaipu ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$id = $_GPC['id'];
		if(empty($id))
		{
			message('抱歉，路径不正确！');
		}
		$item = pdo_fetch("SELECT * FROM ".tablename('ace_baoxiang_resource')." WHERE id = :id and type = :type" , array(':id' => $id, ':type' => 1));
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
					if($item['caipu_photo'])
					{
						$data['caipu_photo'] = $item['caipu_photo'].'|'.$_W['attachurl'].$upload['path'];
					}
					else
					{
						$data['caipu_photo'] = $_W['attachurl'].$upload['path'];
					}
					pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('caipu', array('op' => 'display', 'id' => $id)), 'success');
			}
		} 
		elseif($operation == 'display')
		{
			if($item['caipu_photo'])
			{
				$list = explode('|', $item['caipu_photo']);
			}
			else
			{
				$list = array();
			}
		}
		elseif($operation == 'delete')
		{
			$url = $_GPC['url'];
			$list = explode('|', $item['caipu_photo']);
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
				$data['caipu_photo'] = implode('|', $pics);
			}
			else
			{
				$data['caipu_photo'] = '';
			}
			pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('caipu');	
	}
	/**
	 * 特色菜谱相册管理
	**/
	public function doTese ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		$id = $_GPC['id'];
		if(empty($id))
		{
			message('抱歉，路径不正确！');
		}
		$item = pdo_fetch("SELECT * FROM ".tablename('ace_baoxiang_resource')." WHERE id = :id and type = :type" , array(':id' => $id, ':type' => 1));
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
					if($item['tese_photo'])
					{
						$data['tese_photo'] = $item['tese_photo'].'|'.$_W['attachurl'].$upload['path'];
					}
					else
					{
						$data['tese_photo'] = $_W['attachurl'].$upload['path'];
					}
					pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('tese', array('op' => 'display', 'id' => $id)), 'success');
			}
		} 
		elseif($operation == 'display')
		{
			if($item['tese_photo'])
			{
				$list = explode('|', $item['tese_photo']);
			}
			else
			{
				$list = array();
			}
		}
		elseif($operation == 'delete')
		{
			$url = $_GPC['url'];
			$list = explode('|', $item['tese_photo']);
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
				$data['tese_photo'] = implode('|', $pics);
			}
			else
			{
				$data['tese_photo'] = '';
			}
			pdo_update('ace_baoxiang_resource', $data, array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('tese');	
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
