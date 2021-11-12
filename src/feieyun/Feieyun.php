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
			'content'=>$this->tpl($content),//打印内容
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
				return success('',$res['data']);
			}
			return error($res['msg'],$res['data']);
		}
		return error();
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
	protected function tpl($data=[],$type='default'){
		if ($data){
			if (isset($data['user_id']) && $data['user_id']){
				$content = $this->default_tpl($data);
			}else{
				$content = $this->index_tpl($data);
			}
			
		}else{
			$content = $this->test_tpl();
		}
		return $content;
	}
	/**
	 * Index模板
	 */
	protected function index_tpl($data){
		$title = isset($data['shop_name'])?$data['shop_name'].'小票':'祥杰收银打印';
		$content = '<CB>'.$title.'</CB><BR>';
		if (isset($data['order_id'])){
			$content .= '单号：'.$data['order_id'].'<BR>';
		}
		if (isset($data['mark'])){
			if ($data['mark']){
				$content .= '备注：'.$data['mark'].'<BR>';
			}
		}
		$content .= '<BR>';
		//订单详情
		if (isset($data['order_detail'])){
			$content .= '名称　　　　　 单价  数量 金额<BR>';
			$content .= '--------------------------------<BR>';
			foreach ($data['order_detail'] as $val){
				$content .= $val['name'].'　'.$val['price'].'　'.$val['number'].'　'.sprintf("%.2f",$val['price']*$val['number']).'<BR>';
			}
			$content .= '--------------------------------<BR>';
		}
		if (isset($data['order_money'])){
			$content .= '合计：'.$data['order_money'].'元<BR><BR>';
		}
		//抵扣信息
		if (isset($data['order_deduct'])){
			$content .= '抵扣信息　　　　　 　　抵扣金额<BR>';
			$content .= '--------------------------------<BR>';
			foreach ($data['order_deduct'] as $val){
				$content .= $val['deduct_name'].'　　'.$val['money'].'<BR>';
			}
			$content .= '--------------------------------<BR>';
		}
		//支付方式
		if (isset($data['order_paytype'])){
			$content .= '支付方式　　　　　 　　支付金额<BR>';
			$content .= '--------------------------------<BR>';
			foreach ($data['order_paytype'] as $val){
				$content .= $val['show_name'].'　　'.$val['money'].'<BR>';
			}
			$content .= '--------------------------------<BR>';
		}
		if (isset($data['true_money'])){
			$content .= '实付：'.$data['true_money'].'元<BR>';
		}
		if (isset($data['phone'])){
			$content .= '商家电话：'.$data['phone'].'<BR>';
		}
		if (isset($data['worker_time'])){
			$content .= '营业时间：'.$data['worker_time'].'<BR>';
		}
		$content .= '消费时间：'.date('Y-m-d H:i:s',$data['pay_time']).'<BR>';
		//把二维码字符串用标签套上即可自动生成二维码
		if (isset($data['url'])){
			$content .= '<QR>'.$data['url'].'</QR>';
		}
		return $content;
	}
	/**
	 * 默认模板
	 */
	protected function default_tpl($data){
		$title = isset($data['shop_name'])?$data['shop_name'].'小票':'祥杰收银打印';
		$content = '<CB>'.$title.'</CB><BR>';
		if (isset($data['order_id'])){
			$content .= '单号：'.$data['order_id'].'<BR>';
		}
		$content .= '时间：'.date('Y-m-d H:i:s',$data['pay_time']).'<BR>';
		$type = ['快速消费','商品消费','计次充值','计次消费','积分兑换','挂单还款','会员充值','套餐充值','','','人工调整'];
		$content .= '类型：'.$type[$data['type']].'<BR><BR>';
		
		//明细
		if ($data['order_detail']){
			foreach ($data['order_detail'] as $list){
				if ($data['type'] == 3){
					$content .= "{$list['name']}:{$list['price']}*{$list['number']}=".sprintf("%.2f",$list['price']*$list['number'])."元，剩余{$list['surplus_num']}<BR>";
				}else{
					$content .= "{$list['name']}:{$list['price']}*{$list['number']}=".sprintf("%.2f",$list['price']*$list['number'])."元<BR>";
				}
			}
		}
		$content .= '--------------------------------<BR>';
		$content .= '合计：'.$data['order_money'].'元<BR>';
		
		//金额
		if ($data['true_money']){
			$content .= '实付：'.$data['true_money'].'元';
			//抵扣信息：
			$order_deduct = "";
			if ($data['order_deduct']){
				foreach ($data['order_deduct'] as $list){
					$order_deduct .= "{$list['deduct_name']}{$list['money']}元；";
				}
			}
			//支付方式：
			$order_paytype = "";
			if ($data['order_paytype']){
				foreach ($data['order_paytype'] as $list){
					$order_paytype .= "{$list['show_name']}{$list['money']}元；";
				}
			}
			if ($order_deduct || $order_paytype){
				$content .= '（'.$order_deduct.$order_paytype.'）';
			}
			$content .= '<BR><BR>';
		}
		
		//余额
		$content .= '余额积分    　　　　　 　　    <BR>';
		$content .= '--------------------------------<BR>';
		if (isset($data['shop_user']['balance']) && $data['shop_user']['balance']){
			$content .= '本次剩余金额：'.$data['shop_user']['balance'].' 元<BR>';
		}
		if (isset($data['score']['last']) && $data['score']['last']){
			$content .= "本次剩余积分：{$data['score']['last']}<BR>";
		}
		if (isset($data['score']['change']) || $data['score']['change']){
			$content .= "本次消费积分{$data['score']['change']}<BR>";
		}
		$content .= '<BR>';
		
		if (isset($data['phone'])){
			$content .= '商家电话：'.$data['phone'].'<BR>';
		}
		if (isset($data['worker_time'])){
			$content .= '营业时间：'.$data['worker_time'].'<BR>';
		}
		$content .= '<BR>';
		$content .= '客户信息    　　　　　 　　    <BR>';
		$content .= '--------------------------------<BR>';
		$content .= '称呼：'.$data['shop_user']['true_name'].'<BR>';
		$content .= '电话：'.$data['shop_user']['phone'].'<BR><BR>';
		
		//把二维码字符串用标签套上即可自动生成二维码
		if (isset($data['url'])){
			$content .= '<QR>'.$data['url'].'</QR>';
		}
		
		return $content;
	}
	/**
	 * 测试模板
	 */
	protected function test_tpl(){
		$content = '<CB>祥杰收银测试打印</CB><BR>';
		$content .= '名称　　　　　 单价  数量 金额<BR>';
		$content .= '--------------------------------<BR>';
		$content .= '测试产品　1.00　1　1.0<BR>';
		$content .= '老苗医一号产品　10.00　10　100.00<BR>';
		$content .= '祥杰二号产品　530.00　2　1060.00<BR>';
		$content .= '--------------------------------<BR>';
		$content .= '备注：我没有备注任何信息，请不要留意<BR>';
		$content .= '合计：1161.00元<BR>';
		$content .= '商家电话：13888888888888<BR>';
		$content .= '消费时间：'.date('Y-m-d H:i:s').'<BR>';
		//把二维码字符串用标签套上即可自动生成二维码
		$content .= '<QR>http://www.lmyyst.com</QR>';
		return $content;
	}
}