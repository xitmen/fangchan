<?php
namespace ACE\Module\Common;
/**
 * 微信内部公共方法
 * @author yam 2015年03月25日
 *
 */
class WxFunc{
	private static function wxQrcode($barcode=array()){
		global $_W;
		if(empty($barcode)) return null;
		$wc_acceount = $_W ['account'];
		$access_token = account_weixin_token ( $wc_acceount );
		$result=ihttp_post('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token,json_encode($barcode));
		return json_decode($result['content'],true);
	}
	private static function wxQrInsert($scene_id='',$result=array(),$data=array()){
		global $_W;
		if(empty($result)) return null;
		$insert = array(
				'weid' => $_W['weid'],
				'qrcid' => $scene_id,
				'keyword' => $data['keyword'],
				'name' => $data['scene-name'],
				'model' => $data['qrc-model'],
				'ticket' => $result['ticket'],
				'url' => $result['url'],
				'expire' => $result['expire_seconds'],
				'createtime' => time(),
				'status' => '1',
		);
		pdo_insert('qrcode', $insert);
		return $result['url'];
	}
	public static function createTempQrcode($keyword,$sname,$expire=3000){
		$barcode = array('expire_seconds' => '','action_name' => '','action_info' => array('scene' => array('scene_id' => ''),	),);
		$qrcid = pdo_fetchcolumn("SELECT qrcid FROM ".tablename('qrcode')." WHERE model = '1' ORDER BY qrcid DESC");
		$barcode['action_info']['scene']['scene_id'] = !empty($qrcid) ? ($qrcid+1) : 100001;
		$barcode['expire_seconds'] = $expire;
		$barcode['action_name'] = 'QR_SCENE';
		$result = self::wxQrcode($barcode);
		if(!empty($result)){
			if(isset($result['errcode']))
				return $result;
			elseif(isset($result['ticket'])){
				$result=self::wxQrInsert($barcode['action_info']['scene']['scene_id'],$result,array('keyword'=>$keyword,'scene-name'=>$sname,'qrc-model'=>1));
				if($result!=null)
					return new ReturnMsg(0,$result);
				else
					return new ReturnMsg(-1,'生成临时二维码失败2');
			}
		}
		return new ReturnMsg(-1,'生成临时二维码失败1');
	}
	public static function createQrcode($keyword,$sname){
		$barcode = array('action_name' => '','action_info' => array('scene' => array('scene_id' => ''),),	);
		$qrcid = pdo_fetchcolumn("SELECT qrcid FROM ".tablename('qrcode')." WHERE model = '2' ORDER BY qrcid DESC");
		$barcode['action_info']['scene']['scene_id'] = !empty($qrcid) ? ($qrcid+1) : 1;
		if ($barcode['action_info']['scene']['scene_id'] > 100000) {
			return new  ReturnMsg(-1,"抱歉，永久二维码已经生成最大数量，请先删除一些。");
		}
		$barcode['action_name'] = 'QR_LIMIT_SCENE';
		$result = self::wxQrcode($barcode);
		if(!empty($result)){
			if(isset($result['errcode']))
				return $result;
			elseif(isset($result['ticket'])){
				$result=self::wxQrInsert($barcode['action_info']['scene']['scene_id'],$result,array('keyword'=>$keyword,'scene-name'=>$sname,'qrc-model'=>2));
				if($result!=null)
					return new ReturnMsg(0,$result);
				else
					return new ReturnMsg(-1,'生成二维码失败2');
			}
		}
		return new ReturnMsg(-1,'生成二维码失败1');
	}
	/**
	 * 获取微信用户昵称及头像
	 * @param array 用户信息 array('uname','avatar')
	 * @author yam 2014-04-28
	 */
	public static function getWxUser(&$udata,$openid='') {
		global $_GPC, $_W;
		$wc_acceount = $_W ['account'];
		$openid=empty($openid)?$_W ['fans'] ['from_user']:$openid;
		if (empty($openid))
			return false;
		// 获取access_token
		$access_token = account_weixin_token ( $wc_acceount );
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
		$content = ihttp_get ( $url );
		if (empty ( $content ))
			return false;
		$wc_userinfo = @json_decode ( $content ['content'], true );
		if (empty ( $wc_userinfo ) || ! is_array ( $wc_userinfo )) {
			return false;
		}
		// 存在异常信息 如 48001 api未授权
		if (isset ( $wc_userinfo ['errcode'] )) {
			return false;
		}
		// var_dump($wc_userinfo);
		// 获取用户微信头像及昵称
		if (empty ( $udata ['uname'] ) || strtolower ( $udata ['uname'] ) == 'null')
			$udata ['uname'] = $wc_userinfo ['nickname'];
		$udata ['avatar'] = $wc_userinfo ['headimgurl'];
	}
	/**
	 * 发送客服消息 触发规则48小时内发送有效
	 * @param string $to
	 * @param string|array $msg
	 * @param string $type 'text','image','voice','video','music','news'
	 * @param string $from_custom 指定发送消息的客服
	 * @return array errcode,errmsg
	 */
	public static function sendWxMsg($to,$msg,$type='text',$from_custom=''){
		global $_W;
		if(empty($to) || empty($msg) || empty($type)){
			return ComFunc::jsonErrMsg(-1,'推送openid为空或者推送内容为空');
		}
		if(!in_array($type,array('text','image','voice','video','music','news')))
			return ComFunc::jsonErrMsg(-1,'推送消息类型不支持');
		$wxmsg=array('touser'=>$to,'msgtype'=>$type);
		if($type=='text'){
			$wxmsg['text']=array('content'=>$msg);
		}
		elseif($type=='image'){
			$wxmsg['image']=array('media_id'=>$msg);
		}
		elseif($type=='voice'){
			$wxmsg['voice']=array('media_id'=>$msg);
		}
		elseif($type=='video' && is_array($msg) && !empty($msg['media_id']) && !empty($msg['thumb_media_id']) && !empty($msg['title']) && !empty($msg['description'])){
			$wxmsg['video']=array('media_id'=>$msg['media_id'],'thumb_media_id'=>$msg['thumb_media_id'],'title'=>$msg['title'],'description'=>$msg['description']);
		}
		elseif($type=='music' && is_array($msg) && !empty($msg['musicurl']) && !empty($msg['hqmusicurl']) && !empty($msg['thumb_media_id']) && !empty($msg['title']) && !empty($msg['description'])){
			$wxmsg['music']=array('musicurl'=>$msg['musicurl'],'hqmusicurl'=>$msg['hqmusicurl'],'thumb_media_id'=>$msg['thumb_media_id'],'title'=>$msg['title'],'description'=>$msg['description']);			
		}
		elseif($type=='news' && is_array($msg) && count($msg)>0 && count($msg)<11){
			$newlist=array();
			foreach ($msg as $m){
				if(!empty($m['title']) && !empty($m['description']) && isset($m['url']) && isset($m['picurl']))
					$newlist[]=array('title'=>$m['title'],'description'=>$m['description'],'url'=>$m['url'],'picurl'=>$m['picurl']);
			}
			if(!empty($newlist))
				$wxmsg['news']['articles']=$newlist;
		}
		if(count($wxmsg)==2)
			return ComFunc::jsonErrMsg(-1,'推送消息结构错误');
		if(!empty($from_custom))
			$wxmsg['customservice']=array('kf_account'=>$from_custom);
		$token=account_weixin_token($_W['account']);
		$rtn=ihttp_post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$token,json_encode($wxmsg,JSON_UNESCAPED_UNICODE));
		$wxrtn=json_decode($rtn['content'],true);
		if(is_array($wxrtn) && $wxrtn['errcode']==0){
			return ComFunc::jsonErrMsg(0);
		}
		else{
			return ComFunc::jsonErrMsg($wxrtn['errcode'],$wxrtn['errmsg']);
		}
	}
	/**
	 * 推送文本消息到客户openid
	 * @param string $to openid
	 * @param string $content 内容
	 * @return array('errcode','errmsg') errcode错误码 errmsg 错误信息
	 */
	public static function sendTextMsg($to,$content){
		return self::sendWxMsg($to, $content);
	}
	public static function sendImageMsg($to,$media_id){
		return self::sendWxMsg($to,$media_id,'image');
	}
	/**
	 * 对array按字母排序并拼接为一个字符串类似 key1=v1&key2=v2...
	 * @param array $arr 要排序拼接的字符串
	 * @param bool $urlencode 是否进行url转码
	 * @return string
	 */
	public static function formatParaMap($arr,$urlencode){
		$buff='';
		ksort($arr);
		foreach($arr as $k => $v){
			if($urlencode){
				$v=rawurlencode($v);
			}
			$buff.=strtolower($k).'='.$v.'&';
		}
		$reqPar='';
		if(strlen($buff)>0){
			$reqPar=substr($buff,0,strlen($buff)-1);
		}
		return $reqPar;
	}
	/**
	 * 微信公众平台jsapi签名算法
	 * @return multitype:string number mixed
	 */
	public static function wxJsSignConf(){
		global $_W,$_GPC;
		$js_ticket=$_GPC['__jsapi_ticket'];
		if(empty($js_ticket)){
			$access_token= account_weixin_token($_W['account']);
			$resp=ihttp_get("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi");
			$resp=json_decode($resp['content'],true);
			$js_ticket=$resp['ticket'];
			//将jsticket存入cookie，避免重复到微信端获取ticket
			isetcookie('__jsapi_ticket',$js_ticket,7000);
		}
		$signa=array(
				'timestamp'=>time(),
				'noncestr'=>ComFunc::createRndStr(24,'number',true),
				'jsapi_ticket'=>$js_ticket,
				'url'=>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
		);
		$sign=self::formatParaMap($signa,false);
		if(!empty($sign)){
			$sign=sha1($sign);
			$signa['sign']=$sign;
		}
		return $signa;
	}	
	private static function mkTplMsgJson($openid,$template_id,$url,$topcolor,$data=array()){
		if(empty($data)){
			return null;
		}
		$arr=array();
		foreach ($data as $k=>$v){
			if(is_array($v))
				$arr[$k]=$v;
			elseif(is_string($v)){
				$tmp=explode('|',$v);
				if(count($tmp)==2){
					$arr[$k]=array('value'=>$tmp[0],'color'=>$tmp[1]);
				}
				continue;
			}
			continue;
		}
		if(empty($arr))
			return null;
		return json_encode(array('touser'=>$openid,'template_id'=>$template_id,'url'=>$url,'topcolor'=>$topcolor,'data'=>$arr),JSON_UNESCAPED_UNICODE);
	}
	/**
	 * 发送模板消息
	 * @param unknown_type $template_id
	 * @param unknown_type $url
	 * @param unknown_type $topcolor
	 * @param unknown_type $data
	 * @param unknown_type $open
	 * @return \ACE\Module\Common\ReturnMsg|mixed
	 */
	public static function sendTplMsg($template_id,$url,$topcolor='#FF0000',$data=array(),$open=''){
		global $_W;
		if(empty($template_id) || empty($url) || empty($data)){
			return new ReturnMsg(-1,'参数信息不完整');
		}
		$wc_acceount=$_W['account'];
		if(empty($_W['fans']['from_user']) && empty($open))
			return new ReturnMsg(-1,'无法获取当前用户openid');
		$access_token=account_weixin_token($wc_acceount);
		if(empty($access_token))
			return new ReturnMsg(-1,'无法获取当前access_token');
		$openid=!empty($open)?$open:$_W['fans']['from_user'];
		$jsondata=self::mkTplMsgJson($openid, $template_id, $url, $topcolor,$data);
		if(empty($jsondata))
			return new ReturnMsg(-1,'000');
		$rtn=ihttp_post("https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$access_token",$jsondata);
		return json_decode($rtn['content'],true);
	}
}
class EcryptAES{
	const CIPHER = MCRYPT_RIJNDAEL_128;
	const MODE = MCRYPT_MODE_CBC;
	const BLOCK_SIZE=32;
	/**
	 * 加密前补位函数
	 * @param unknown_type $str
	 * @return string
	 */
	private static function strpad($str){
		$amount_to_pad=self::BLOCK_SIZE - (strlen($str) % self::BLOCK_SIZE);
		if($amount_to_pad == 0)
			$amount_to_pad=self::BLOCK_SIZE;
		
		$pad_chr=chr($amount_to_pad);
		$tmp="";
		for($i=0;$i<$amount_to_pad;$i++){
			$tmp.=$pad_chr;
		}
		return $str.$tmp;
	}
	/**
	 * 解密后删除补位字符
	 * @param unknown_type $str
	 * @return string
	 */
	private static function rmstr($str){
		$pad=ord(substr($str,-1));
		if($pad<1 || $pad>32){
			$pad=0;
		}
		return substr($str,0,(strlen($str)-$pad));
	}
	
	public static function encrypt($text,$key){
		try{
			$key=base64_decode($key.'=');
			$rand=ComFunc::createRndStr(16,'number',true);
			/*echo $rand.'<br/>';
			$eeeee=pack('N',100000000000);
			var_dump($eeeee);
			var_dump(unpack('N',$eeeee));*/
			$text=$rand.pack('N',strlen($text)).$text;
			$size=mcrypt_get_block_size(self::CIPHER,self::MODE);
			$module=mcrypt_module_open(self::CIPHER,'',self::MODE,'');
			$iv=substr($key,0,16);
			$text=self::strpad($text);
			mcrypt_generic_init($module,$key, $iv);
			$encrypted=mcrypt_generic($module, $text);
			mcrypt_generic_deinit($module);
			mcrypt_module_close($module);
		}
		catch(\Exception $e){
			print($e);
		}
		return base64_encode($encrypted);
	}
	
	public static function decrypt($encrypted,$key){
		try{
			$key=base64_decode($key.'=');
			$ciphertext_dec=base64_decode($encrypted);
			$module=mcrypt_module_open(self::CIPHER,'',self::MODE,'');
			$iv=substr($key,0,16);
			mcrypt_generic_init($module, $key, $iv);
			
			$decrypted=mdecrypt_generic($module,$ciphertext_dec);
			mcrypt_generic_deinit($module);
			mcrypt_module_close($module);
		}
		catch(\Exception $e){
			print $e;
		}
		try{
			$result=self::rmstr($decrypted);
			if(strlen($result)<16)
				return "";
			$result=substr($result,16,strlen($result));
			$len_list=unpack('N',substr($result,0,4));
			$result=substr($result,4,$len_list[1]);
		}
		catch (\Exception $e){
			print $e;
		}
		return $result;
	}
}
/**
 * 常用公共方法集合类
 * @author yam 2015年01月29日
 * 使用方法：
 * use ACE\Module\Common\ComFunc;
 * require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';
 * ComFunc::方法名(参数); 或者 ACE\Module\Common\ComFunc::方法名(参数);
 */
class ComFunc {
	/**
	 * 构造打印json数据字符串
	 * @param unknown_type $appkey
	 * @param unknown_type $storeid
	 * @param unknown_type $data
	 * @return string
	 */
	public static function createPrintData($appkey,$storeid,$data){
		return '{"action":"admin","appkey":"123","option":"toprint","clientappkey":"'.$appkey.'","storeid":'.$storeid.',"data":'.json_encode($data,JSON_UNESCAPED_UNICODE).'}';
	}
	/**
	 * 发送字符串数据到打印机
	 * @param unknown_type $str
	 */
	public static function sendStrToPrint($str){
		$fp = fsockopen("tcp://127.0.0.1",13500, $errno, $errstr);
		if (!$fp){
			echo "$errstr ($errno)<br/>\n";
			/* echo $fp;*/
		} else {
			fwrite($fp,$str);
			fclose($fp);
		}
	}
	/**
	 * 发送打印数据到客户端 yam 2015年04月24日
	 * @param string $appkey
	 * @param int $storeid
	 * @param array $data
	 */
	public static function sendClientPrint($appkey,$storeid,$data){
		self::sendStrToPrint('{"action":"admin","appkey":"123","option":"toprint","clientappkey":"'.$appkey.'","storeid":'.$storeid.',"data":'.json_encode($data,JSON_UNESCAPED_UNICODE).'}');
	}

	/**
	 * 发送打印数据到客户端 zbs 2015年08月31日
	 * @param string $appkey
	 * @param int $storeid
	 * @param array $data
	 * @param int $weid
	 * @param int $businessid 
	 */
	public static function sendClientPrintToDB($appkey,$storeid,$data,$weid,$businessid){
		$dataDB = array();
		$dataDB['weid'] = $weid;
		$dataDB['business_id'] = $businessid;
		$dataDB['data'] = '{"action":"print","data":'.json_encode($data,JSON_UNESCAPED_UNICODE).'}';
		$dataDB['status'] = 1;
		//var_dump($dataDB);
		pdo_insert('ace_restaurant_print_data',$dataDB);
	}
	/**
	 * 尝试从W以及auth连接后的cookie中获取用户openid
	 * @param bool $only_openid 是否值获取openid 否则会返回数组 包含openid和是否是粉丝
	 * @param string $try_scope 用户授权作用域 默认为snsapi_userinfo将获取昵称头像等基本信息 snsapi_base将不弹出提示直接获取
	 * @return string|array 用户openid|array('openid','isfans')
	 * @author yam 2015年06月05日
	 */
	public static function tryToGetOpenid($only_openid=true,$try_scope='snsapi_userinfo'){
		global $_W;
		//首先尝试从w中直接获取
		$rtn=array('openid'=>$_W['fans']['from_user'],'isfans'=>true);
		if(empty($rtn['openid'])){
			//如果cookie中亦为空，尝试创建auth链接
			if(empty($_W['tmp_openid'])){
				ComFunc::createAuthUrl('',true,$try_scope);
			}
			else{
				//从cookie中获取
				$rtn['openid']=$_W['tmp_openid'];
				$rtn['isfans']=false;
			}
		}
		if($only_openid)
			return $rtn['openid'];
		return $rtn;
	}
	/**
	 * 创建微信网页授权OAuth连接，并提供自动跳转
	 * @param string $url 需要跳转到的页面，为空时取当前页面
	 * @param bool $redirct 是否直接跳转，默认为false
	 * @return string 当不直接跳转时返回授权url网址
	 * @author yam 2015年06月05日
	 */
	public static function createAuthUrl($url='',$redirct=false,$scope='snsapi_base'){
		global $_W;
		$rurl='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$_W['account']['key'];
		$rurl.='&redirect_uri='.rawurlencode(!empty($url)?$url:self::getCurrentUrl());
		$rurl.='&response_type=code&scope='.$scope.'&state=STATE#wechat_redirect';
		if($redirct)
			self::redrict($rurl);
		return $rurl;
	}
	/**
	 * 对比两数组是否相同，包括键的匹配，值的类型匹配 add yam 2015年04月17日
	 * @param array $array1
	 * @param array $array2
	 * @return boolean
	 */
	public static function compareTwoArray($array1,$array2){
		if(is_array($array1) && is_array($array2)){  //两个都为数组
			if(count($array1)==count($array2)){  //数组长度一样
				foreach ($array1 as $k=>$v){
					if(!array_key_exists($k,$array2)){  //array2 不存在 array1的主键
						return false;
					}
					if(is_array($v)){ //array1的值为数组
						if(!is_array($array2[$k])){  //array2的对应值不为数组
							return false;
						}
						if(self::compareTwoArray($v, $array2[$k])===false){  //两对应数组对比存异
							return false;
						}
					}
					if($v!==$array2[$k])
						return false;
				}
				return true;  //全部检查完毕通过
			}
			return false;
		}
		return false;
	}
	/**
	 * 字符串解密函数 add yam 2015年04月17日
	 * @param string $str 需要解密的字符串
	 * @param string $key 密钥
	 * @return Ambigous <string, unknown, mixed>
	 */
	public static function strDecryption($str,$key){
		return self::baseStrEncryption($str, $key,'D');
	}
	/**
	 * 字符串加密函数 add yam 2015年04月17日
	 * @param string $str 需要加密的字符串
	 * @param string $key 密钥
	 * @return Ambigous <string, unknown, mixed>
	 */
	public static function strEncryption($str,$key){
		return self::baseStrEncryption($str, $key);
	}
	private static function baseStrEncryption($string,$key,$op='E'){
		if(empty($key)){
			return $string;
		}
		$key=md5($key);
		$key_length=strlen($key);
		$string=$op=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
		$string_length=strlen($string);
		$rndkey=$box=array();
		$result='';
		for($i=0;$i<=255;$i++){
			$rndkey[$i]=ord($key[$i%$key_length]);
			$box[$i]=$i;
		}
		for($j=$i=0;$i<256;$i++){
			$j=($j+$box[$i]+$rndkey[$i])%256;
			$tmp=$box[$i];
			$box[$i]=$box[$j];
			$box[$j]=$tmp;
		}
		for($a=$j=$i=0;$i<$string_length;$i++){
			$a=($a+1)%256;
			$j=($j+$box[$a])%256;
			$tmp=$box[$a];
			$box[$a]=$box[$j];
			$box[$j]=$tmp;
			$result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
		}
		if($op=='D')
		{
			if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8))
				return substr($result,8);
			else
				return'';
		}
		else
			return str_replace('=','',base64_encode($result));
	}
	public static function uploadFile($url,$filename,$path,$type){
		$data = array(
				'pic'=>'@'.realpath($path).";type=".$type.";filename=".$filename
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// curl_getinfo($ch);
		$return_data = curl_exec($ch);
		curl_close($ch);
		return $return_data;
	}
	
	private static function http_get_data($url) {
		$ch = curl_init ();
		curl_setopt ( $ch,CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt ($ch,CURLOPT_URL, $url );
		ob_start ();
		curl_exec ($ch);
		$return_content = ob_get_contents ();
		ob_end_clean ();
		$return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
		return $return_content;
	}
	/**
	 * 从url下载文件并保存
	 * @param unknown_type $url
	 * @param unknown_type $filename
	 */
	public static function saveFromUrl($url,$filename){
		$return_content = self::http_get_data($url);
		$fp= @fopen($filename,"a"); //将文件绑定到流
		fwrite($fp,$return_content); //写入文件
		fclose($fp);
	}	
	/**
	 * 缓存数组private方法 yam 2015年02月10日
	 * @param unknown_type $var
	 */
	private static function cacheArray($var){
		$content="";
		if(is_array($var)){
			$content.="array(\r\n";
			$i=0;
			foreach ($var as $k => $v){
				if($i>0)
					$content.=",\r\n";
				$k=str_ireplace('"','\"', $k);
				if(is_array($v)){
					$v=self::cacheArray($v);
					$content.="\"$k\"=>$v";
				}	else {
					$v=str_ireplace('"','\"', $v);
					$content.="\"$k\"=>\"$v\"";
				}
				$i++;
			}
			$content.="\r\n)";
		}
		return $content;
	}
	public static function incCache($file,$module){
		global $_W;
		$current_path=dirname(__FILE__).'/';
		if(empty($_W['weid']))
			die('weid为空不能查询缓存');
		if(empty($file))
			die('缓存文件名不能为空');
		if(empty($module))
			die('当前模块名为空');
		if(!file_exists($current_path.$module))
			die('模块目录不存在');
		$file_path=$current_path.$module.'/cache/'.$file.'_'.$_W['weid'].'.cache.php';
		if(!file_exists($file_path))
			return null;
		return include IA_ROOT.'/source/modules/'.$module.'/cache/'.$file.'_'.$_W['weid'].'.cache.php';
	}
	/**
	 * 判断是否需要缓存 yam 2015年02月10日
	 * @param string $file 缓存文件名
	 * @param string $module 当前模块名
	 * @param int $cachetime 缓存时间 默认24小时
	 * @return boolean 正常情况下返回boolen
	 */
	public static function needCache($file,$module,$cachetime=24){
		global $_W;
		$current_path=dirname(__FILE__).'/';
		if(empty($_W['weid']))
			die('weid为空不能查询缓存');
		if(empty($file))
			die('缓存文件名不能为空');
		if(empty($module))
			die('当前模块名为空');
		if(!file_exists($current_path.$module))
			die('模块目录不存在');
		self::mkDir($current_path.$module.'/cache');
		$file_path=$current_path.$module.'/cache/'.$file.'_'.$_W['weid'].'.cache.php';
		if(file_exists($file_path)){
			//缓存时间默认为24小时，超过24小时自动创建新的缓存文件
			$file_create_time=filemtime($file_path);
			if($file_create_time+$cachetime*3600<time()){
				unlink($file_path);
				return true;
			}
			return false;
		}
		else{
			return true;
		}
	}
	/**
	 * 写入缓存文件 yam 2015年02月10日
	 * @param string $file 缓存文件名
	 * @param string $module 当前模块名
	 * @param mix $var 需要缓存的变量 数组/字符串/数字/对象
	 * @return boolean
	 */
	public static function cache($file,$module,$var){
		global $_W;
		$current_path=dirname(__FILE__).'/';
		$content="<?php\r\n";
		if(empty($_W['weid']))
			die('weid为空不能创建缓存');
		if(empty($file))
			die('缓存文件名不能为空');
		if(empty($module))
			die('当前模块名为空');
		if(empty($content))
			die('缓存内容为空');
		if(!file_exists($current_path.$module))
			die('模块目录不存在');
		self::mkDir($current_path.$module.'/cache');
		$file_path=$current_path.$module.'/cache/'.$file.'_'.$_W['weid'].'.cache.php';
		$content.="//File:$file.cache.php\r\n";
		$content.="//Module:$module\r\n";
		$content.="//Cache time:".date('Y/m/d H:i:s',time())."\r\n";
		if(is_array($var)){
			$content.="return ".self::cacheArray($var).";";
		}
		else if(is_string($var)){
			$content.="return \"".str_ireplace('"','\"',$var)."\";";
		}
		else if(is_numeric($var)){
			$content.="return $var;";
		}
		else if(is_object($var)){
			$content.="return \"".str_ireplace('"','\"',json_encode($var,JSON_UNESCAPED_UNICODE))."\";";
		}
		else{
			$content.=$var;
		}
		$content.="\r\n?>";
		$f=fopen($file_path, 'w');
		fwrite($f,$content);
		fclose($f);
		return true;
	}
	/**
	 * 微信公众平台jsapi签名算法
	 * @return multitype:string number mixed
	 */
	public static function wxJsSignConf(){
		return WxFunc::wxJsSignConf();
	}
	/**
	 * 字符串数组中字符串去空格 trim
	 * @param array $arr 字符串数组
	 * @param bool $removespace 是否移除所有空格 默认为false 只去掉两边空格 为true是去掉所有空格
	 * @return array 返回处理后的数组，亦可不用返回值，该方法为引用调用
	 */
	public static function arrayTrim($arr,$removespace=false){
		if(!is_array($arr) || empty($arr)) return $arr;
		foreach ($arr as &$v){
			if($removespace)
				$v=str_ireplace(' ','',$v);
			else
				$v=trim($v);
		}
		return $arr;
	}
	/**
	 * 获取当前url地址
	 * @param bool $onlyhost 是否只返回主机名 http://vezhi.net
	 * @param bool $hostanddir 当onlyhost为true hostanddir也为true 则在主机名后加目录 http://vezhi.net/123
	 * @param bool $showport 是否显示端口号
	 * @return string
	 */
	public static function getCurrentUrl($onlyhost=false,$hostanddir=false,$showport=false){
		$host=$_SERVER['HTTP_HOST'];
		$port=$_SERVER['SERVER_PORT'];
		$script=$_SERVER['PHP_SELF'];
		$query=$_SERVER['QUERY_STRING'];
		if($onlyhost){
			$host='http://'.$host.($showport?':'.$port:'');
			return $hostanddir?dirname($host.$_SERVER["REQUEST_URI"]):$host;
		}
		return "http://{$host}".($showport?':'.$port:'')."{$script}?{$query}";
	}
	private static $debug_admin_tpl=<<<EOD
<!DOCTYPE html><html><head><title>日志查看</title></head><body><?php
\$fileext='#FILEEXT#';\$filebefor='#FILEBEFOR#';\$qr=\$_SERVER['QUERY_STRING'];\$tm=time();
if(strpos(\$qr,'&')) \$qr=substr(\$qr,0,strpos(\$qr,'&'));
if(!empty(\$qr)){
	\$qr=preg_replace('/[\.\\\/\%\?\[\]\"\'\*\&]/','',\$qr);	\$oqr=\$qr;	\$qr=str_replace('del-','',\$qr);
	if(\$qr=='list'){
		echo '<h1>日志文件列表</h1>';\$d = dir(dirname(__FILE__));
		while(false!==(\$entry=\$d->read())){
			if(\$entry=='.') continue;
			if(\$entry=='..') continue;
			if(preg_match('/\.php$/',\$entry)) continue;
			\$filename=substr(\$entry,strlen(\$filebefor),strlen(\$fileext)*-1);echo "<a href='?\$filename&t=\$tm'>\$filename</a><br/>\\n";
		}\$d->close();exit;}
	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',\$qr)){
		echo '<h1>日志文件不存在.</h1>';exit;	}
	\$filepath=dirname(__FILE__).'/'.\$filebefor.\$qr.\$fileext;
	if(file_exists(\$filepath)){
		if(preg_match('/^del\-.+$/',\$oqr)){
			unlink(\$filepath);
			echo "删除日志文件\$oqr<script>location.replace('?list');</script>";exit;		}
		echo "正在查看的日志为：\$qr&nbsp;&nbsp;<button onclick='if(confirm(\"确定要删除该日志吗？\")){location.replace(\"?del-\$qr&t=\$tm\");}'>删除</button>&nbsp;<button onclick='location.replace(\"?list&t=\$tm\");'>列表&gt;&gt;</button><br/><hr/>";
		\$content=file_get_contents(\$filepath);
		echo "\n<pre>\n".\$content.'</pre>';}
	else{
		echo '<h1>日志文件不存在</h1>';}
}
else{
	echo '<h1>请指定要查看的日志文件</h1>';}
?><body></html>
EOD;
	/**
	* 开启调试模式 yam 2015年01月28日
	* @param integet $errorlevel 错误级别 和 error_reporting的参数一致
	* @param bool $showerr 是否直接在页面输出异常  默认为false不输出
	* @param bool $logtofile 是否把错误输出到日志中 默认为true 输出
	*/
	public static function startDebug($errorlevel=E_ALL,$showerr=false,$logtofile=true){
		error_reporting($errorlevel);
		if($showerr){
			ini_set('display_errors','1');
		}
		else{
			ini_set('display_errors',0);
		}
		if($logtofile){
			ini_set('log_errors',1);
			$fileext='.debug.db';
			$filebefor='debug-';
			self::mkDir(IA_ROOT.'/debug');
			if(!file_exists(IA_ROOT.'/debug/index.php')){
				$filecontent=str_replace('#FILEEXT#',$fileext,self::$debug_admin_tpl);
				$filecontent=str_replace('#FILEBEFOR#',$filebefor,$filecontent);
				file_put_contents(IA_ROOT.'/debug/index.php',$filecontent);
			}
			ini_set('error_log',IA_ROOT.'/debug/'.$filebefor.date('Y-m-d',time()).$fileext);
		}
	}

	/**
	 * added 记录方法调用日志 yam 2015年01月22日
	 * @param string $method 方法名
	 * @param string|array $param 参数 可为字符串也可为参数数组
	 * @param string $result 返回结果
	 * @param string $class 类名
	 * @param string $type 调用类型 :: 或者 ->
	 */
	public static function loggingMethod($method,$param,$result,$class='',$type='->'){
		$inf='';
		if(!empty($class)){
			$inf.=$class.$type;
		}
		if(!empty($method)){
			$inf.=$method."(";
			if(!empty($param)){
				if(is_string($param)){
					$inf.=$param;
				}
				else if(is_array($param)){
					$inf.=implode(',', $param);
				}
			}
			$inf.=")\n";
		}
		if(!empty($result)){
			$inf.="Result:\n".$result;
		}
		self::logging('Method-Info',$inf);
	}
	/**
	 * 记录catch到的异常信息
	 * @param string $level 异常级别或自定义字符串
	 * @param Exception $e 异常对象
	 */
	public static function loggingException($level='exception',$e){
		if($e instanceof \Exception){
			$inf.="Exception Message:\n".$e->getMessage()."------------\nException Trace:\n".$e->getTraceAsString();
			self::logging($level,$inf);
		}
	}
	/**
	 * 日志记录
	 * @param string $level
	 * @param string|array $message
	 */
	public static function logging($level = 'info', $message = '') {
		$fileext='.txt';
		$filebefor='log-';
		self::mkDir(IA_ROOT.'/logs');
		if(!file_exists(IA_ROOT.'/logs/index.php')){
			$filecontent=str_replace('#FILEEXT#',$fileext,self::$debug_admin_tpl);
			$filecontent=str_replace('#FILEBEFOR#',$filebefor,$filecontent);
			file_put_contents(IA_ROOT.'/logs/index.php',$filecontent);
		}
		$content = date('Y-m-d H:i:s') . " {$level} :\n------------\n";
		if(is_string($message)) {
			$content .= "String:\n{$message}\n";
		}
		if(is_array($message)) {
			$content .= "Array:\n";
			foreach($message as $key => $value) {
				$content .= sprintf("%s : %s ;\n", $key, $value);
			}
		}
		if($message == 'get') {
			$content .= "GET:\n";
			foreach($_GET as $key => $value) {
				$content .= sprintf("%s : %s ;\n", $key, $value);
			}
		}
		if($message == 'post') {
			$content .= "POST:\n";
			foreach($_POST as $key => $value) {
				$content .= sprintf("%s : %s ;\n", $key, $value);
			}
		}
		$content .= "\n";
		$filename='log-'.date('Y-m-d',time());
		$filename =IA_ROOT.'/logs/'.$filename;
		file_put_contents($filename.$fileext,$content,FILE_APPEND);
		/*$fp = fopen($filename.$fileext, 'a+');
		fwrite($fp, $content);
		fclose($fp);*/
	}
	/**
	 * 通用返回json信息函数
	 * @param string|integer $errcode 错误码
	 * @param string $errmsg 返回信息
	 * @param array $oth 其他自定义信息
	 * @return string json字符串 {"errcode":0,"errmsg":''}
	 */
	public static function jsonErrMsg($errcode,$errmsg='',$oth=array()){
		return json_encode(array_merge(array('errcode'=>$errcode,'errmsg'=>$errmsg),$oth),JSON_UNESCAPED_UNICODE);
	}
	/**
	 * 获取文件目录路径
	 * @param string $filepath 文件路径
	 * @param string $separator 目录分隔符 默认为/
	 * @return string
	 */
	public static function getFilePath($filepath,$separator='/'){
		$filepath=strrev($filepath);
		return strrev(substr($filepath,0,strpos($filepath,$separator)));
	}
	/**
	 * 获取文件后缀名
	 * @param string $filepath 文件路径
	 * @return string
	 */
	public static function getFileExt($filepath){
		$filepath=strrev($filepath);
		return strrev(substr($filepath,0,strpos($filepath,'.')));
	}
	/**
	 * 指定宽度和高度生成缩略图
	 * @param string $filepath 图片路径
	 * @param integer $width 指定高度
	 * @param integer $height 指定宽度
	 * @param boolen $deloldfile 是否删除源文件 默认为true
	 */
	public static function thumbImage($filepath,$width,$height,$deloldfile=true){
		if(!file_exists($filepath))
			die('指定文件不存在');
		list($ws,$hs,$type,$attr) = getimagesize($filepath);
		if($ws==0 || $hs==0){
			die('文件不是正确图片文件');
		}
		return self::thumbImageSelf($filepath, $width, $height,$deloldfile);
	}
	/**
	 * 按最大自定义尺寸生成缩略图
	 * @param string $filepath 图片路径
	 * @param integer $max 图片最大尺寸，最大尺寸表示宽度和高度其中之一的最大值，另一值按比例计算
	 * @param boolen $deloldfile 是否删除原图片 默认为true
	 */
	public static function thumbImageMax($filepath,$max=500,$deloldfile=true){
		if(!file_exists($filepath))
			die('指定文件不存在');
		list($width,$height,$type,$attr) = getimagesize($filepath);
		if($width==0 || $height==0){
			die('文件不是正确图片文件');
		}
		//指定缩放出来的最大的宽度（也有可能是高度）
		//根据最大值，算出另一个边的长度，得到缩放后的图片宽度和高度
		$size_src=array($width,$height);
		if($width > $height){
			$width=$max;
			$height=$height*($max/$size_src[0]);
		}else{
			$height=$max;
			$width=$width*($max/$size_src[1]);
		}
		return self::thumbImageSelf($filepath, $width, $height,$deloldfile);
	}
	/**
	 * 生成缩略图私有方法内部调用
	 * @param unknown_type $filepath
	 * @param unknown_type $width
	 * @param unknown_type $height
	 * @param unknown_type $deloldfile
	 * @return \ACE\Module\Common\ReturnMsg
	 */
	private static function thumbImageSelf($filepath,$width,$height,$deloldfile=true){
		$imgOld=imagecreatefromjpeg($filepath);
		$imgObj=imagecreatetruecolor($width,$height);
		if(function_exists('imagecopyresampled'))
			imagecopyresampled($imgObj,$imgOld,0,0,0,0,$width,$height,imagesx($imgOld),imagesy($imgOld));
		else
			imagecopyresized($imgObj,$imgOld,0,0,0,0,$width,$height,imagesx($imgOld),imagesy($imgOld));
		imagedestroy($imgOld);
		if($deloldfile)
			unlink($filepath);
		else{
			$fileext=self::getFileExt($filepath);
			$filepath=str_replace('.'.$fileext,'_tb_'.self::createRndStr(5,'letter').'_'.$width.'x'.$height.'.'.$fileext,$filepath);
		}
		imagejpeg($imgObj,$filepath,100);
		chmod($filepath,0777);
		imagedestroy($imgObj);
		return new ReturnMsg(0,'',array('filepath'=>$filepath));
	}
	/**
	 * 创建目录
	 * @param string $dir 目录路径
	 * @return boolean
	 */
	public static function mkDir($dir)//创建目录
	{
		if (!file_exists($dir)){
			self::mkDir(dirname($dir));
			$c=mkdir($dir, 0777);
			if($c==false)
				return false;
		}
		return true;
	}
	/**
	 * 推送文本消息到客户openid
	 * @param string $to openid
	 * @param string $content 内容
	 * @return array('errcode','errmsg') errcode错误码 errmsg 错误信息
	 */
	public static function sendWcMsg($to,$content){
		return WxFunc::sendTextMsg($to, $content);
	}
	/**
	 * 跳转到上页 首先判断cookie 接着判断referer 然后判断传入参数$url 最后判断Comfunc中的default_referer
	 * @author yam 2015年01月26日
	 * @param string $url 指定跳转url
	 */
	public static $default_referer='';
	public static function redrictReferer($url=''){
		if(!empty($_COOKIE['___lasturl']))
			self::redrict($_COOKIE['___lasturl']);
		elseif(isset($_SERVER['HTTP_REFERER']))
		self::redrict($_SERVER['HTTP_REFERER']);
		elseif(!empty($url))
		self::redrict($url);
		elseif(!empty(self::$default_referer))
		self::redrict(self::$default_referer);
	}
	/**
	 * 防止用户重复提交
	 */
	public static function checkToken(){
		global $_GPC;
		if(isset($_GPC['token'])){  //检测是否提交数据过来
			if($_GPC['token']!=$_SESSION['token']){
				$_SESSION['SUCCESS_INFO']="数据不能重复提交！";
				unset($_SESSION['token']);
				self::redrictReferer();
			}
			else {  //对比与session相同，进入CRUD
				$_SESSION['token']=self::createRndStr(rand(8,13),'letter');
				return true;
			}
		}
		//没有提交数据过来
		$_SESSION['token']=self::createRndStr(rand(8,13),'letter');
		return false;
	}
	/**
	 * Session 启用方法，在使用session的页面中需先调用此方法
	 * 调用方法 $this->startSession();
	 * @param bool $gotobind 到没有查询到微信用户是是否跳转到绑定页面 默认跳转
	 * @author yam 2014年4月16日
	 */
	public static function startSession(){
		global $_W,$_GPC;
		@session_start();
	}
	/**
	 * 新检查用户是否登陆 yam 2015年01月30日
	 * @author yam
	 */
	public static function checkLogin(&$user=null){
		global $_GPC,$_W;
		self::startSession();
		$_SESSION['staticversion']='?ver=2015-01-30';
		if(!empty($_SESSION['fromUser']) && !empty($_SESSION['weid'])){
			if($_SESSION['weid']==$_W['weid']){ //weid 正确
				$loginuser=pdo_fetch('select * from '.tablename('ace_members').' where `id`=:sfu and `u_id`=:weid',array(':sfu'=>$_SESSION['fromUser'],':weid'=>$_W['weid']));
				if(!empty($loginuser)){  //fromuser 正确
					//判断是电脑登陆还是微信登陆
					if(!self::userauth()){ //微信访问
						checkauth();  //检查是否有openid  $_W['fans']['from_user']
						if(empty($loginuser['from_user']) || $_W['fans']['from_user']!=$loginuser['from_user']){  //微信用户openid为空 或者 openid和数据库不符 跳转绑定
							//跳转到绑定页
							setcookie('___lasturl',self::getCurrentUrl(), time()+3600, '/');
							setcookie('___urlret',true, time()+3600, '/');
							self::redrict('mobile.php?act=module&name=usersvip&do=Binding&weid='.$_W['weid']);
						}
					}
					$user=$loginuser;
					return; //登陆成功
				}
				unset($loginuser);
			}
		}
		unset($_SESSION['weid']);unset($_SESSION['fromUser']);
		if(self::userauth()){  //直接访问
			session_destroy();//清空session
			setcookie('___lasturl',self::getCurrentUrl(), time()+3600, '/');
			setcookie('___urlret',true, time()+3600, '/');
			//没有登录 跳转到登陆界面
			self::redrict('mobile.php?act=module&name=usersvip&do=Login&weid='.$_W['weid']);			
		}
		else{  //微信访问
			checkauth();  //检查是否有openid  $_W['fans']['from_user']
			$loginuser=pdo_fetch('select * from '.tablename('ace_members').' where `from_user`=:sfu and `u_id`=:weid',array(':sfu'=>$_W['fans']['from_user'],':weid'=>$_W['weid']));
			if(empty($loginuser)){ //跳转到绑定页
				setcookie('___lasturl',self::getCurrentUrl(), time()+3600, '/');
				setcookie('___urlret',true, time()+3600, '/');
				self::redrict('mobile.php?act=module&name=usersvip&do=Binding&weid='.$_W['weid']);
			}
			else{
				$_SESSION['fromUser']=$loginuser['id'];
				$_SESSION['weid']=$_W['weid'];
				$_SESSION['islogin']="1";
			}
			unset($loginuser);
		}
	}
	/**
	 * 检测是否为合法参数
	 * @param string $params 请求
	 * @param array $allow     合法请求关键字列表
	 * @return boolean
	 */
	private static function validatePost($params,$allow=array()){
	}
	/**
	 * 直接访问用户返回true 微信用户返回false
	 * @return boolean
	 */
	public static function userauth() {
		if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')>0){
			return false;
		} else {
			return true;
		}
	}
	/**
	 * 产生订单编号 yam 2014年4月18日
	 * @return string 返回订单编号
	 */
	public static function createOrderNo($onlydatetime=false) {
		$now = date ( 'YmdHms', time () );
		if($onlydatetime)
			return $now;
		$time = explode ( " ", microtime () );
		$micro = $time [0] * 1000000;
		return $now . str_pad ( $micro, '6', '0', STR_PAD_LEFT );
	}
	/**
	 * 创建随机字符串，可为纯字母或数字，亦可为字母数字混合
	 * @param int $length 字符串长度
	 * @param string $type 类型 number为纯数字，letter为纯字母
	 * @param bool $letter 当type为number时如果letter为true则为字母数字混合字符串
	 * @return string
	 */
	public static function createRndStr($length = 8,$type='number',$letter=false) {
		$chars='';
		if($type=='number'){
			$chars .= "0123456789";
			if($letter){
				$type='letter';
			}
		}
		if($type=='letter')
			$chars.='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$str = "";
		for($i = 0; $i < $length; $i ++) {
			$str .= substr ( $chars, mt_rand ( 0, strlen ( $chars ) - 1 ), 1 );
		}
		return $str;
	}
	/**
	 * 获取客户真实ip地址
	 * @return Ambigous <string, unknown>
	 */
	public static function getClientIp() {
		if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"),
				"unknown"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
			$ip = getenv("REMOTE_ADDR");
		else if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']
				&& strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
			$ip = $_SERVER['REMOTE_ADDR'];
		else
			$ip = "unknown";
		return ($ip);
	}
	/**
	 * 页面跳转方法
	 * @param 	string 跳转到指定url
	 * @author yam 2014年4月16日
	 */
	public static function redrict($url,$rmcode=true) {
		//add 添加直接跳转取消auth残留code参数 yam 2015年01月30日
		if($rmcode)
			$url=preg_replace('/&code=.[^\&\#]+/i','',$url);
		header ( "Location: " . $url );
		exit ();
	}
	/**
	 * 获取微信用户昵称及头像
	 * @param array 用户信息 array('uname','avatar')
	 * @author yam 2014-04-28
	 */
	public static function wechatUser(&$udata,$openid='') {
		return WxFunc::getWxUser($udata,$openid);
	}
	/**
	 * 获取毫秒时间戳
	 */
	public static function getMS(){
		$time = explode ( " ", microtime () );
		$time = $time [1] . ($time [0] * 1000);
		$time2 = explode ( ".", $time );
		$time = $time2 [0];
		return $time;
	}
}
/**
 * 日志类，方便调用
 * @author anyo
 * 使用方法：
 use ACE\Module\Common\F;
 use ACE\Module\Common\ComFunc;
 require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';
 L::方法名(参数); 或者 ACE\Module\Common\L::方法名(参数);
 //全局开启调试默认代码
 class A{
 public function __construct(){
 L::debug();
 }
 }
 */
class L{
	/**
	 * 记录日志
	 * @param string $level 记录级别 用来信息区分
	 * @param string|array $message 记录内容 为字符串或者数组
	 */
	public static function log($level='info',$message){
		ComFunc::logging($level,$message);
	}
	/**
	 * 记录调用方法日志
	 * @param string $method 方法名
	 * @param string|array $param 参数字符串 字符串或者数组
	 * @param string $result 返回值
	 * @param string $class 类名
	 * @param string $type 调用方法->或者::
	 */
	public static function method($method,$param,$result,$class='',$type='->'){
		ComFunc::loggingMethod($method, $param, $result,$class,$type);
	}
	/**
	 * 记录异常信息
	 * @param string $level
	 * @param Exception $e
	 */
	public static function exception($level='exception',$e){
		ComFunc::loggingException($level,$e);
	}
	/**
	 * 开启调试模式
	 * @param integer $errorlevel 和error_reporting参数一致 默认为E_ERROR
	 * @param bool $showerr 是否直接把错误显示到页面上 默认不显示
	 * @param bool $logtofile 是否输出错误到调试文件里 默认输出
	 */
	public static function debug($errorlevel=E_ERROR,$showerr=false,$logtofile=true){
		ComFunc::startDebug($errorlevel,$showerr,$logtofile);
	}
}
/**
 * 公共数据验证类
 * @author anyo
 * 使用方法：
 * use ACE\Module\Common\Validate;
 * require_once IA_ROOT.'/source/modules/versioncontrol.inc.php';
 * Validate::方法名(参数); 或者 ACE\Module\Common\Validate::方法名(参数);
 */
class Validate{
	private static $error_list=null;
	private static $show_s=false;
	private static function addError($m){
		if(self::$error_list==null)
			self::$error_list=array();
		self::$error_list[]=$m;
	}
	/**
	 * 是否显示源数据
	 * @param bool $s 不调用为false 调用过后默认为true
	 */
	public static function showSor($s=true){
		self::$show_s=(bool)$s;
	}
	/**
	 * 获取数据验证结果
	 * @param string $type 返回结果类型  默认 html 每行一条错误信息用<br/>换行  array类型 返回数组['错误1','错误2','错误3',''] array类型会多一个为空的错误数据
	 * @return string 错误信息
	 */
	public static function result($type='html'){
		if(self::$error_list==null || count(self::$error_list)==0){
			return "";
		}
		$res="";
		if($type==='html'){
			foreach (self::$error_list as $e){
				$res.=$e.'<br/>';
			}
		}
		else if($type==='array'){
			$res='[';
			foreach (self::$error_list as $e){
				$res.='"'.$e.'",';
			}
			$res.='""]';
		}
		self::$error_list=null;
		return $res;
	}
	/**
	 * 自定义验证
	 * @param string $r 正则表达式
	 * @param string $s 需要验证的数据字符串
	 * @param string $m 验证失败后提示信息
	 * @return boolean 是否通过验证
	 */
	public static function validate($r,$s,$m=''){
		$s=trim($s);
		if(!preg_match($r,$s)){
			if(self::$show_s){
				$m='['.$s.']'.$m;
			}
			self::addError($m);
			return false;
		}
		return true;
	}
	//校验数据不为NULL
	public static function isNNull($s,$m='数据为NULL'){
		if($s!=null){
			return true;
		}
		self::addError($m);
		return false;
	}
	//校验数据不为空
	public static function isEpty($s,$m="数据为空"){
		if(!empty($s)){
			return true;
		}
		self::addError($m);
		return false;
	}
	//校验数据没有赋值
	public static function isSt($s,$m='没有赋值给数据'){
		if(isset($s)){
			return true;
		}
		self::addError($m);
		return false;
	}
	//校验数据是否相等
	public static function isEq($s,$r,$m='对比不相等'){
		if($s==$r){
			return true;
		}
		self::addError($m);
		return false;
	}
	//校验数据是否不相等
	public static function isNeq($s,$r,$m='对比相等'){
		if($s!=$r){
			return true;
		}
		self::addError($m);
		return false;
	}
	//值在定义的数组中
	public static function isInArr($s,$arr=array(),$m='不在数组中'){
		if(is_array($arr) && in_array($s, $arr))
			return true;
		self::addError($m);
		return false;
	}
	public static function isMobile($s,$m='手机号码格式不正确。'){
		return self::validate("/^((\(\d{2,3}\))|(\d{3}\-))?1\d{10}$/",$s,$m);
	}
	public static function isTel($s,$m='电话号码格式不正确。'){
		return self::validate("/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/",$s,$m);
	}
	public static function isUrl($s,$m='链接地址格式不正确。'){
		return self::validate("/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/i", $s,$m);
	}
	public static function isEmail($s,$m='电子邮件格式不正确。'){
		return self::validate('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $s,$m);
	}
	public static function isInt($s,$m='数字不是整数。'){
		return self::validate("/^[-\+]?\d+$/", $s,$m);
	}
	public static function isDouble($s,$m='数字不是小数。'){
		return self::validate('/^[-\+]?\d+(\.\d+)?$/', $s,$m);
	}
	public static function isUnsafe($s,$m='不是安全字符。'){
		return self::validate('/^(([A-Z]*|[a-z]*|\d*|[-_\~!@#\$%\^&\*\.\(\)\[\]\{\}<>\?\\\/\'\"]*)|.{0,5})$|\s/', $s,$m);
	}
	public static function isEnglish($s,$m='包含非英文字符。'){
		return self::validate('/^[A-Za-z]+$/', $s,$m);
	}
	public static function isRequire($s,$m='不能为空。'){
		return self::validate('/^.+$/',$s,$m);
	}
	public static function isCarCode($s,$m='车牌号不正确'){
		mb_internal_encoding("UTF-8");
		$s=mb_substr($s,1);
		return self::validate('/^[A-Za-z0-9]{5,6}$/',$s,$m);
	}
}

/***
 * 钩子HOOK类
* @author yam
* 2014年06月23日
*/
class Hook
{
	private static $actions = array();
	/**
	 * 监听钩子
	 * @param string $hook 钩子名称
	 * @param function $function 执行函数
	 * @return boolean 是否添加成功
	*/
	public static function L($hook,$function){
		$hook=mb_strtolower($hook,CHARSET);
		if(!self::EA($hook))
			self::$actions[$hook] = array();
		if (is_callable($function)){
			self::$actions[$hook][] = $function;
			return true;
		}
		return false ;
	}
	/**
	 * 执行钩子
	 * @param string $hook 钩子名称
	 * @param array $params 钩子函数参数array
	 * @return boolean 是否执行成功
	 */
	public static function E($hook,$params=null){
		$hook=mb_strtolower($hook,CHARSET);
		if(isset(self::$actions[$hook])){
			foreach(self::$actions[$hook] as $function)	{
				if (is_array($params))
					call_user_func_array($function,$params);
				else
					call_user_func($function);
			}
			return true;
		}
		return false;
	}
	/**
	 * 返回钩子对应函数
	 * @param string $hook 钩子名称
	 * @return Ambigous <boolean, multitype:> false没有钩子 array 钩子函数array
	 */
	public static function F($hook){
		$hook=mb_strtolower($hook,CHARSET);
		return (isset(self::$actions[$hook]))? self::$actions[$hook]:FALSE;
	}
	/**
	 * 是否存在钩子
	 * @param string $hook 钩子名称
	 * @return boolean
	 */
	public static function EA($hook){
		$hook=mb_strtolower($hook,CHARSET);
		return (isset(self::$actions[$hook]))?true:false;
	}
}