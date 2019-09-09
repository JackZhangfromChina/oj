<?php
namespace Api\Controller;
use Common\Controller\BaseController;
use Common\Conf\Constants;

/**
 * 定时任务控制器
 *
 * @since: 2017年3月11日 下午4:25:27
 * @author: lyx
 * @version: V1.0.0
 */
class TimerController extends BaseController {

	/**
	 * 定时结算（日结）
	 *
	 * @since: 2017年3月11日 下午4:26:19
	 * @author: lyx
	 */
	public function settlementTask() {
	    //不限制相应时间
	    set_time_limit(0);
	    
	    //获取未结算的业绩
		$market_records = D('MarketRecord')->where(array('status'=>Constants::NO))->select();
		foreach ($market_records as $market_record) {
		    //调用奖金结算服务
			A('Common/Service')->settlement($market_record);
		}
		$result = array(
				'status'  => true,
				'message' => '奖金结算成功!'
		);
		$this->ajaxReturn($result);
	}
	
	/**
	 * 定时返本
	 *
	 * @since: 2017年3月11日 下午4:26:19
	 * @author: lyx
	 */
	public function rebateTask() {
	    //不限制相应时间
	    set_time_limit(0);
	    
	    //调用静态返利服务
	    A('Common/Service')->rebate();
	     
	    $result = array(
	            'status'  => true,
	            'message' => '奖金结算成功!'
	    );
	    $this->ajaxReturn($result);
	}
}