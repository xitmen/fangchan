<?php
/**
 * 出租房模块定义
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
class ChuzuModule extends WeModule {
	public function __construct(){
		L::debug(E_ALL); //开启异常调试模式，异常可在 ./debug/?list 中查看
		global $_W;
	}
	/**
	 * 出租房源管理
	**/
	public function doHouses ()
	{
		global $_GPC, $_W;
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'post')
		{
			$id = intval($_GPC['id']);
			if (!empty($id))
			{
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_chuzu_house')." WHERE id = :id" , array(':id' => $id));
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
				$tagarrs = $_GPC['tags'];
				if(!empty($tagarrs))
				{
					$data['tags'] = ','.implode(',', $tagarrs).',';
				}
				else
				{
					$data['tags'] = '';
				}
				if (!empty($_FILES['pic']['tmp_name'])) {
					$upload = file_upload($_FILES['pic']);
					if (is_error($upload)) {
						message($upload['message'], '', 'error');
					}
					$data['pic'] = $_W['attachurl'].$upload['path'];
				}
				if (empty($id))
				{
					$data['number'] = ComFunc::createOrderNo();
					pdo_insert('ace_chuzu_house', $data);
				} 
				else
				{
					pdo_update('ace_chuzu_house', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('houses', array('op' => 'display')), 'success');
			}
			$branchs = pdo_fetchall("SELECT * FROM ".tablename('ace_branch')." ".$where);
		} 
		elseif($operation == 'display')
		{
			$pindex = max(1, intval($_GPC['page']));
			$psize = 20;
			$where = ' where 1 ';
			if($_GPC['key'])
			{
				$where .= " and (name like '%".$_GPC['key']."%' or number like '%".$_GPC['key']."%')";
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
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_chuzu_house')." ".$where." ORDER BY  torder asc,id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_chuzu_house')." ".$where);
			$pager = pagination($total, $pindex, $psize);
		}
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_chuzu_house')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_chuzu_house', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		elseif ($operation == 'paixu') {
			$ids = $_GPC['ids'];
			foreach($ids as $k=>$id)
			{
			 pdo_update('ace_chuzu_house', array('torder'=>$id), array('id' => $k));
			}
			message('排序成功！', referer(), 'success');
		}
		require_once IA_ROOT.'/source/modules/chuzu/tags.php';  //引入标签
		$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
		$_STATUS = array(
				'-1' => '未发布',
				'1' => '已发布',
				'2' => '已出租',
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
		$item = pdo_fetch("SELECT * FROM ".tablename('ace_chuzu_house')." WHERE id = :id" , array(':id' => $id));
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
					pdo_update('ace_chuzu_house', $data, array('id' => $id));
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
			pdo_update('ace_chuzu_house', $data, array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('photo');	
	}
	/**
	 * 定制管理
	**/
	public function doQueue ()
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
				$where .= " and (phone like '%".$_GPC['key']."%' or zfyx like '%".$_GPC['key']."%')";
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
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_chuzu_queue')." ".$where." ORDER BY  id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_chuzu_queue')." ".$where);
			$pager = pagination($total, $pindex, $psize);
			$regions = pdo_fetchall("SELECT * FROM ".tablename('ace_region')." where parent_id=0 and status=1 ORDER BY torder asc,region_id DESC");
		}
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_chuzu_queue')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_chuzu_queue', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('queue');	
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
	//获取区域
	public function doGetAlltags()
	{
		global $_GPC, $_W;
		$id = $_GPC['id'];
		$tag = $_GPC['tag'];
		$tagarrs = explode(',',$tag);
		require_once IA_ROOT.'/source/modules/chuzu/tags.php';  //引入标签
		$str = '';
		if($id)
		{
			foreach($tags['items'][$id] as $v)
			{
				$str .= '<select name="tags[]">';
				$str .='<option value="">--'.$v['name'].'--</option>';
				foreach($v['items'] as $ks => $vs)
				{
					$str .= '<option value="'.$ks.'" '. (in_array($ks, $tagarrs)?'selected':'') .'>'.$vs.'</option>';
				}
				$str .= '</select>';
			}
		}
		echo $str;
	}
	//获取网点
	public function doGetBranchs()
	{
		global $_GPC, $_W;
		$id = $_GPC['id'];
		$branch_id = $_GPC['branch_id'];
		$str = '<option value="">--网点--</option>';
		if($id)
		{
			$branchs = pdo_fetchall("SELECT * FROM ".tablename('ace_branch')." where province = ".$id." or city = ".$id." or area = ".$id);
			foreach($branchs as $branchs)
			{
				$str .= '<option value="'.$branchs['id'].'" '. ($branch_id == $branchs['id']?'selected':''). '>'.$branchs['name'].'</option>';
			}
		}
		echo $str;
	}
}
