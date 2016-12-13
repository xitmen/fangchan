<?php
/**
 * 上门维修模块定义
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
class ShangmenModule extends WeModule {
	public function __construct(){
		L::debug(E_ALL); //开启异常调试模式，异常可在 ./debug/?list 中查看
		global $_W;
	}
	/**
	 * 商家管理
	**/
	public function doMerchant ()
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
				$item = pdo_fetch("SELECT * FROM ".tablename('ace_shangmen_merchant')." WHERE id = :id" , array(':id' => $id));
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
				$data['is_v'] = $data['is_v']?$data['is_v']:0;
				$data['is_c'] = $data['is_c']?$data['is_c']:0;
				$tagarrs = $_GPC['tags'];
				if(!empty($tagarrs))
				{
					$data['tags'] = ','.implode(',', $tagarrs).',';
				}
				else
				{
					$data['tags'] = '';
				}
				if (empty($id))
				{
					pdo_insert('ace_shangmen_merchant', $data);
				} 
				else
				{
					pdo_update('ace_shangmen_merchant', $data, array('id' => $id));
				}
				message('信息更新成功！', $this->createWebUrl('merchant', array('op' => 'display')), 'success');
			}
			require_once IA_ROOT.'/source/modules/shangmen/tags.php';  //引入标签
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
			$list = pdo_fetchall("SELECT * FROM ".tablename('ace_shangmen_merchant')." ".$where." ORDER BY  torder asc,id DESC LIMIT ".($pindex - 1) * $psize.','.$psize);
			$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ace_shangmen_merchant')." ".$where);
			$pager = pagination($total, $pindex, $psize);
		}
		elseif($operation == 'delete')
		{
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename('ace_shangmen_merchant')." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete('ace_shangmen_merchant', array('id' => $id));
			message('删除成功！', referer(), 'success');
		}
		elseif ($operation == 'paixu') {
			$ids = $_GPC['ids'];
			foreach($ids as $k=>$id)
			{
			 pdo_update('ace_shangmen_merchant', array('torder'=>$id), array('id' => $k));
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
		include $this->template('merchant');	
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
