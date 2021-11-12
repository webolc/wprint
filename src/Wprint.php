<?php
namespace webolc\wprint;

/**
 * 小票打印接口
 * YYX
 */
interface Wprint{
	/**
	 * 打印订单
	 * @param 打印内容 $content
	 * @param 打印机编号 $number
	 * @param 打印次数 $times
	 */
	function printSend($content,$number='',$times=1);
	/**
	 * 添加小票打印机
	 * @param 打印机信息  $data
	 */
	function printAdd($data);
	/**
	 * 编辑小票打印机
	 * @param 打印机编号 $number
	 * @param 打印机名称 $name
	 * @param 打印机流量卡号码 $phonenum
	 */
	function printEdit($number,$name,$phonenum='');
	/**
	 * 删除小票打印机
	 * @param 打印机编号 $number
	 */
	function printDel($number);
	/**
	 * 小票打印机状态
	 * @param 打印机编号 $number
	 */
	function printStatus($number);
	/**
	 * 小票打印机打印订单数
	 * @param 打印机编号 $number
	 * @param 时间 $date
	 */
	function printOrder($number,$date='');
	/**
	 * 清空待打印订单
	 * @param 打印机编号 $number
	 */
	function printClear($number);
}