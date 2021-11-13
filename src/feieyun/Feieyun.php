<?php
namespace webolc\wprint\feieyun;

use webolc\wprint\feieyun\HttpClient;
use webolc\wprint\Wprint;

class Feieyun implements Wprint{
	
	protected $config;
	protected $client;
	public function __construct($config=[]){
		$conf = [
			'user' => '',
			'key' => '',
			'host' => 'api.feieyun.cn',
			'port' => 80,
			'path' => '/Api/Open/',
			'sn' => ''//打印机编号
		];
		$this->config = array_merge($conf,$config);
		$this->client = new HttpClient($this->config['host'],$this->config['port']);
	}
	/**
	 * 打印订单
	 */
	public function printSend($content,$number='',$times=1){
		$number = $number?$number:$this->config['sn'];
		$data = [
			'apiname'=>'Open_printMsg',
			'sn'=>$number,//打印机编号
			'content'=>$content??$this->test_tpl(),//打印内容
			'times'=>$times//打印次数
		];
		return $this->post($data);
	}
	/**
	 * 添加小票打印机
	 * 打印机编号SN(必填) # 打印机识别码KEY(必填) # 备注名称(选填) 
	 * # 流量卡号码(选填)，多台打印机请换行（\n）添加新打印机信息，
	*/
	public function printAdd($data){
		$content = $data['number'].' # '.$data['key'].' # '.$data['name'];
		if (isset($data['phonenum'])){
			$content .= ' # '.$data['phonenum'];
		}
		$data = [
			'apiname'=>'Open_printerAddlist',
			'printerContent'=>$content,//内容
		];
		return $this->post($data);
	}
	/**
	 * 编辑小票打印机
	*/
	public function printEdit($number,$name,$phonenum=''){
		$data = [
			'apiname'=>'Open_printerEdit',
		    'sn'=>$number,
		    'name'=>$name
		];
		if ($phonenum){$data['phonenum']=$phonenum;}
		return $this->post($data);
	}
	/**
	 * 删除小票打印机
	*/
	public function printDel($number){
		$data = [
			'apiname'=>'Open_printerDelList',
		    'snlist'=>$number
		];
		return $this->post($data);
	}
	/**
	 * 小票打印机状态
	*/
	public function printStatus($number){
		$data = [
			'apiname'=>'Open_queryPrinterStatus',
		    'sn'=>$number
		];
		return $this->post($data);
	}
	/**
	 * 小票打印机打印订单数
	*/
	function printOrder($number,$date=''){
		$date = $date?$date:time();
		$date = date('Y-m-d',$date);
		$data = [
			'apiname'=>'Open_queryOrderInfoByDate',
		    'sn'=>$number,
		    'date'=>$date
		];
		return $this->post($data);
	}
	/**
	 * 清空待打印订单
	*/
	function printClear($number){
		$data = [
			'apiname'=>'Open_delPrinterSqs',
		    'sn'=>$number
		];
		return $this->post($data);
	}
	/**
	 * 设置或配置参数
	 */
	public function data($key,$val=false){
		if ($val!==false){
			$this->config[$key] = $val;
			return true;
		}else{
			if (isset($this->config[$key])){
				return $this->config[$key];
			}
		}
		return false;
	}
	/**
	 * 发送请求
	 */
	protected function post($data){
		$time = time();
		$data['user'] = $this->config['user'];
		$data['stime'] = $time;
		$data['sig'] = sha1($this->config['user'].$this->config['key'].$time);;
		$res = $this->client->post($this->config['path'], $data);
		if ($res){
			$res = json_decode($this->client->getContent(),true);
			if (isset($res['ret']) && $res['ret']==0){
				return [
				    'code'    => 1,
				    'status'  => 'success',
				    'msg' => 'success', 
				    'data'    => $res['data']
				];
			}
			return [
			    'code'    => 0,
			    'status'  => 'error',
			    'msg'     => $res['msg'],
			    'data'    => $res['data']
			];
		}
		return [
		    'code'    => 0,
		    'status'  => 'error',
		    'msg'     => '',
		    'data'    => []
		];
	}
	/**
	 * 模板
	 	//单标签:
		//<BR> ：换行符
		//<CUT> ：切刀指令(主动切纸,仅限切刀打印机使用才有效果)
		//<LOGO> ：打印LOGO指令(前提是预先在机器内置LOGO图片)
		//<PLUGIN> ：钱箱或者外置音响指令
		//<CB></CB>：居中放大
		//<B></B>：放大一倍
		//<C></C>：居中
		//<L></L>：字体变高一倍
		//<W></W>：字体变宽一倍
		//<QR></QR>：二维码（单个订单，最多只能打印一个二维码）
		//<RIGHT></RIGHT>：右对齐
		//<BOLD></BOLD>：字体加粗
		//"<BR>"为换行,"<CUT>"为切刀指令(主动切纸,仅限切刀打印机使用才有效果)
		//"<LOGO>"为打印LOGO指令(前提是预先在机器内置LOGO图片),"<PLUGIN>"为钱箱或者外置音响指令
		//成对标签：
		//"<CB></CB>"为居中放大一倍,"<B></B>"为放大一倍,"<C></C>"为居中,<L></L>字体变高一倍
		//<W></W>字体变宽一倍,"<QR></QR>"为二维码,"<BOLD></BOLD>"为字体加粗,"<RIGHT></RIGHT>"为右对齐
	 */
	protected function tpl(){
		
	}
	/**
	 * 测试模板
	 */
	protected function test_tpl(){
		$content = '<CB>测试打印</CB><BR>';
		$content .= '名称　　　　　 单价  数量 金额<BR>';
		$content .= '--------------------------------<BR>';
		$content .= '测试产品　1.00　1　1.0<BR>';
		$content .= '一号产品　10.00　10　100.00<BR>';
		$content .= '二号产品　530.00　2　1060.00<BR>';
		$content .= '--------------------------------<BR>';
		$content .= '备注：我没有备注任何信息，请不要留意<BR>';
		$content .= '合计：1161.00元<BR>';
		$content .= '商家电话：13888888888888<BR>';
		$content .= '消费时间：'.date('Y-m-d H:i:s').'<BR>';
		//把二维码字符串用标签套上即可自动生成二维码
		$content .= '<QR>http://www.webolc.com</QR>';
		return $content;
	}
}