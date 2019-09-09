<?php
namespace Common\Controller;
use Common\Controller\BaseController;
use Common\Conf\Constants;

/**
 * 业务服务控制器
 *
 * @since: 2017年3月1日 上午11:06:47
 * @author: lyx
 * @version: V1.0.0
 */
class ServiceController extends BaseController {
	
    /**
     * 静态返本算法
     *
     * @since: 2017年3月13日 下午2:06:13
     * @author: lyx
     */
    public function rebate() {
        
        //开启静态返本=>静态返还处理
        if ($this->sys_config['SYSTEM_OPEN_RETURN']) {
            //获取级别设置
            $old_levels =  M('UserLevel')->select();
            foreach($old_levels as $level){
                $levels[$level[id]] = $level;
            }
            
            //获取待返本的业绩
            $market_records = D('MarketRecord')->where(array('return_is_over' => Constants::NO))->select();
            
            //循环进行返本
            foreach ($market_records as $market_record) {
                //返本
                $this->_rebate($market_record, $levels);
            }
        }
    }
    
    /**
    * 奖金算法
    *
    * @param   object  market_record   业绩对象
    * @return  bool    true/false
    *
    * @since: 2017年3月3日 上午10:15:15
    * @author: lyx
    */
	public function settlement($market_record) {
	    //业绩来源会员信息
	    $source_user = M('User')->where(array('user_no'=>$market_record['user_no']))->find();
	    //业绩来源的安置关系
	    $parents = M('User')->alias('u')
            	    ->field('u.id,u.user_no,u.user_level_id,u.left_market,u.right_market,pn.floor')
            	    ->join(C('DB_PREFIX').'parent_nexus AS pn ON u.user_no = pn.parent_no')
            	    ->where(array('pn.user_no'=>$market_record['user_no']))
            	    ->order('pn.floor asc')
            	    ->select();
	    //获取级别设置
	    $old_levels =  M('UserLevel')->select();
	    foreach($old_levels as $level){
	        $levels[$level[id]] = $level;
	    }
	    //获取手续费设置
	    $award_fee = json_decode($this->sys_config['AWARD_FEE'],true);
	    
	    //开启对碰奖=>计算对碰奖
	    if ($this->sys_config['AWARD_OPEN_TOUCH']) {
	        //产生对碰奖时，如果开启了领导奖并计算领导奖
	        $this->_touchAward($source_user, $parents, $market_record, $award_fee, $levels);
	    }
	    //开启见点奖=>计算见点奖(升级没有见点奖)
	    if ($this->sys_config['AWARD_OPEN_POINT'] && $market_record['market_type'] == Constants::MARKET_TYPE_ENROLL) {
	        $this->_pointAward($parents, $market_record, $award_fee, $levels);
	    }
	    //开启满层奖=>计算满层奖（升级没有满层奖）
	    if ($this->sys_config['AWARD_OPEN_FLOOR'] && $market_record['market_type'] == Constants::MARKET_TYPE_ENROLL) {
	        $this->_floorAward($parents, $market_record, $award_fee, $levels);
	    }
	    //开启层碰奖=>计算层碰奖（升级没有层碰奖）
	    if ($this->sys_config['AWARD_OPEN_LAYER_TOUCH'] && $market_record['market_type'] == Constants::MARKET_TYPE_ENROLL) {
	        $this->_layerTouchAward($source_user, $parents, $market_record, $award_fee, $levels);
        }
        //开启推荐奖=>计算推荐奖
        if ($this->sys_config['AWARD_OPEN_RECOMMEND']) {
            $this->_recommendAward($market_record, $award_fee, $levels);
        }
        //开启报单中心并且开启报单奖=>计算报单奖
        if ($this->sys_config['SYSTEM_OPEN_SERVICE_CENTER'] && $this->sys_config['AWARD_OPEN_SERVICE']) {
            $this->_serviceAward($source_user, $market_record, $award_fee, $levels);
        }
	    
	    //保存业绩的结算信息
	    $market_record['status'] = Constants::YES;
	    $market_record['settle_time'] = curr_time();
	    $records = D('MarketRecord')->save($market_record); //计入事务队列
	}
	
	/**
	 * 对碰奖
	 *
	 * @param  object  source_user     业绩来源会员对象
	 * @param  array   parents         业绩来源的安置关系
	 * @param  object  market_record   业绩对象
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月9日 下午3:01:54
	 * @author: lyx
	 */
	private function _touchAward($source_user, $parents, $market_record, $award_fee, $levels) {
	    foreach ($parents as $parent) {
	        //获取相对此父类节点的位置（左区/右区）
	        $location = substr($source_user['path'],strlen($source_user['path'])-$parent['floor'],1);
	        //对碰业绩初始为0
	        $touch_market = 0;
	        //当前用户相对父级为左区
            if ($location == Constants::LOCATION_LEFT) {
                //右区有业绩，产生对碰
                if ($parent['right_market'] > 0) {
                    //对碰的业绩存储
                    if ($market_record['amount'] >= $parent['right_market']) {
                        $touch_market = $parent['right_market'];
                        $user_data = array(
                                'id'            => $parent['id'],
                                'left_market'   => array('exp', 'left_market + ' . ($market_record['amount']-$touch_market)),
                                'right_market'  => 0,
                                'touch_market'  => array('exp', 'touch_market + ' . $touch_market)
                        );
                    } else {
                        $touch_market = $market_record['amount'];
                        $user_data = array(
                                'id'            => $parent['id'],
                                'left_market'   => 0,
                                'right_market'  => array('exp', 'right_market - ' . $touch_market),
                                'touch_market'  => array('exp', 'touch_market + ' . $touch_market)
                        );
                    }
                    $u_res = M('User')->save($user_data); //计入事务队列
                } else {
                    //左区业绩增加
                    M('User')->where(array('user_no' => $parent['user_no']))->setInc('left_market' ,$market_record['amount']);//计入事务队列
                }
            } elseif ($location == Constants::LOCATION_RIGHT)  { //当前用户相对父级为左区
                //左区有业绩，产生对碰
                if ($parent['left_market'] > 0) {
                    //对碰的业绩存储
                    if ($market_record['amount'] >= $parent['left_market']) {
                        $touch_market = $parent['left_market'];
                        $user_data = array(
                                'id'            => $parent['id'],
                                'left_market'   => 0,
                                'right_market'  => array('exp', 'right_market + ' . ($market_record['amount']-$touch_market)),
                                'touch_market'  => array('exp', 'touch_market + ' . $touch_market)
                        );
                    } else {
                        $touch_market = $market_record['amount'];
                        $user_data = array(
                                'id'            => $parent['id'],
                                'left_market'   => array('exp', 'left_market - ' . $touch_market),
                                'right_market'  => 0,
                                'touch_market'  => array('exp', 'touch_market + ' . $touch_market)
                        );
                    }
                    $u_res = M('User')->save($user_data); //计入事务队列
                } else {
                    //右区业绩增加
                    M('User')->where(array('user_no' => $parent['user_no']))->setInc('right_market' ,$market_record['amount']);//计入事务队列
                }
            }
            
            //有对碰业绩，计算对碰奖
            if ($touch_market) {
                //计算日对碰总计(日封顶累计以业绩产生的时间为依据)
                $day_where = array(
                        'user_no'               => $parent['user_no'],
                        'type'                  => Constants::REWARD_TYPE_TOUCH,
                        'to_days(occur_time)'   => array('exp', "=to_days('". $market_record['add_time'] ."')")
                );
                $day_sum = M('RewardRecord')->where($day_where)->sum('amount');
                //获取日封顶值
                $touch_max = $levels[$parent['user_level_id']]['touch_max'];
                //判断对碰是否到达日封顶
                if ($touch_max == 0 || $touch_max > $day_sum) {
                    //获取会员的对碰奖项设置
                    $award_prize = $levels[$parent['user_level_id']]['touch_award'];
                    //计算奖金
                    $award = round($touch_market * $award_prize / 100, 2);
                    //税点
                    $tax_point = in_array("touch", $award_fee) ? $levels[$parent['user_level_id']]['tax'] : 0;
                    
                    if ($touch_max == 0 || ($touch_max-$day_sum) > $award) {
                        //备注
                        $remark = '会员' . $market_record['user_no'] . '被激活,获得对碰奖';
                    } else {
                        //备注
                        $remark = '会员' . $market_record['user_no'] . '被激活,获得对碰奖(已达到日封顶)';
                        //即将到达封顶，计算奖金
                        $award = $touch_max-$day_sum;
                    }
                    //增加奖金纪录
                    $reward = $this->_addReward($market_record, $parent['user_no'], Constants::REWARD_TYPE_TOUCH, $award, $tax_point, $remark);
                    
                    //产生对碰奖，判断是否开启领导奖=>计算领导奖
                    if ($this->sys_config['AWARD_OPEN_LEADER'] && $award>0) {
                        //产生对碰奖时，如果开启了领导奖并计算领导奖
                        $this->_leaderAward($market_record, $parent, $award, $award_fee, $levels);
                    }
                }
            }
	    }
	}
	
	/**
	 * 领导奖
	 *
	 * @param  object  market_record   业绩对象
	 * @param  object  parent          产生对碰奖的会员对象（领导奖的来源）
	 * @param  int     amount          对碰奖金额
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月10日 上午10:33:37
	 * @author: lyx
	 */
	private function _leaderAward($market_record, $parent, $amount, $award_fee, $levels) {
	    //对碰奖来源的推荐关系
	    $recommends =  M('User')->alias('u')
                	    ->field('u.user_no,u.user_level_id,rn.rec_floor')
                	    ->join(C('DB_PREFIX').'recommend_nexus AS rn ON u.user_no = rn.recommend_no')
                	    ->where(array('rn.user_no'=>$parent['user_no']))
                	    ->order('rn.rec_floor asc')
                	    ->select();
	    foreach ($recommends as $recommend) {
	        //获取推荐人的领导奖设置
	        $award_flag = false;
	        $award_arr = json_decode($levels[$recommend['user_level_id']]['leader_award'],true);
	        //解析奖项设置，获取当前代级对应设置
	        foreach ($award_arr as $key => $award_item) {
	            $key_item=explode("-",$key);
	            if ($recommend['rec_floor'] >= $key_item[0]&&$recommend['rec_floor'] <= $key_item[1]) {
	                $award_prize = $award_item;
	                $award_flag = true;
	            }
	        }
	         
	        //代级符合条件
	        if ($award_flag) {
	            //计算奖金
	            $award = round($amount * $award_prize / 100, 2);
	
	            //税点
	            $tax_point = in_array("leader", $award_fee) ? $levels[$recommend['user_level_id']]['tax'] : 0;
	            //备注
	            $remark = '会员' . $market_record['user_no'] . '被激活,会员' . $parent['user_no'] . '产生对碰,获得领导奖';
	
	            //增加奖金纪录
	            $reward = $this->_addReward($market_record, $recommend['user_no'], Constants::REWARD_TYPE_LEADER, $award, $tax_point, $remark);
	        }
	    }
	}
	
	/**
	 * 层碰奖
	 *
	 * @param  array   parents         业绩来源的安置关系
	 * @param  object  market_record   业绩对象
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月9日 下午3:01:54
	 * @author: lyx
	 */
	private function _layerTouchAward($source_user, $parents, $market_record, $award_fee, $levels) {
        foreach ($parents as $parent) {
            //获取相对此父类节点的位置（左区/右区）
            $location = substr($source_user['path'],strlen($source_user['path'])-$parent['floor'],1);
	        
	        //获取层碰对象
	        $where = array(
	                'user_no'     => $parent['user_no'],
	                'floor'         => $parent['floor'],
	        );
	        $layer_touch =  M('LayerTouch')->where($where)->find();
	        //没有当前会员对应的层碰对象
	        if (!$layer_touch) {
	            if ($location == Constants::LOCATION_LEFT) {
	                $layer_data = array(
	                        'user_no'      => $parent['user_no'],
	                        'floor'        => $parent['floor'],
	                        'left_no'      => $source_user['user_no'],
	                        'left_market'  => $market_record['amount'],
	                        'update_time'  => curr_time()
	                );
	                //插入层碰信息
	                $record = M('LayerTouch')->add($layer_data); //计入事务队列
	            } elseif ($location == Constants::LOCATION_RIGHT)  {
	                $layer_data = array(
	                        'user_no'      => $parent['user_no'],
	                        'floor'        => $parent['floor'],
	                        'right_no'      => $source_user['user_no'],
	                        'right_market'  => $market_record['amount'],
	                        'update_time'  => curr_time()
	                );
	                //插入层碰信息
	                $record = M('LayerTouch')->add($layer_data); //计入事务队列
	            }
	        } else {
	            //当前用户是左区，并且当前层右区存在层碰节点，产生层碰奖
	            if ($location == Constants::LOCATION_LEFT && !$layer_touch['left_no'] && $layer_touch['right_no']) {
	                //计算层碰的碰撞金额
	                if ($market_record['amount'] <= $layer_touch['right_market']) {
	                    $amount = $market_record['amount'];
	                } else {
	                    $amount = $layer_touch['right_market'];
	                }
	                
	                //计算层碰奖
	                $this->_layerTouchAwardSave($parent, $amount, $market_record, $award_fee, $levels);
	                
	                //保存层碰信息
	                $layer_data = array(
	                        'id'           => $layer_touch['id'],
	                        'left_no'      => $source_user['user_no'],
	                        'left_market'  => $market_record['amount'],
	                        'update_time'  => curr_time()
	                );
	                $record = M('LayerTouch')->save($layer_data); //计入事务队列
	            } elseif($location == Constants::LOCATION_RIGHT && !$layer_touch['right_no'] && $layer_touch['left_no']) { //当前用户是右区，并且当前层左区存在层碰节点，产生层碰奖
	                //计算层碰的碰撞金额
	                if ($layer_touch['left_market'] <= $market_record['amount']) {
	                    $amount = $layer_touch['left_market'];
	                } else {
	                    $amount = $market_record['amount'];
	                }

	                //计算层碰奖
	                $this->_layerTouchAwardSave($parent, $amount, $market_record, $award_fee, $levels);
	                 
	                //保存层碰信息
	                $layer_data = array(
	                        'id'           => $layer_touch['id'],
	                        'right_no'     => $source_user['user_no'],
	                        'right_market' => $market_record['amount'],
	                        'update_time'  => curr_time()
	                );
	                $record = M('LayerTouch')->save($layer_data); //计入事务队列
	            }
	        }
	    }
	}
	
	/**
	 * 层碰奖（附属）
	 *
	 * @param  array   parent          业绩来源的父级会员对象
	 * @param  int     amount          产生碰撞的金额
	 * @param  object  market_record   业绩对象
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月9日 下午4:30:51
	 * @author: lyx
	 */
	private function _layerTouchAwardSave($parent, $amount, $market_record, $award_fee, $levels) {
	    //获取安置人的层碰奖设置
	    $award_flag = false;
	    $award_arr = json_decode($levels[$parent['user_level_id']]['layer_touch_award'],true);
	    //解析奖项设置，获取当前层级对应设置
	    foreach ($award_arr as $key => $award_item) {
	        $key_item=explode("-",$key);
	        if ($parent['floor'] >= $key_item[0]&&$parent['floor'] <= $key_item[1]) {
	            $award_prize = $award_item;
	            $award_flag = true;
	        }
	    }
	    
	    //层级符合条件
	    if ($award_flag) {
	        //计算奖金
	        $award = round($amount * $award_prize / 100, 2);
	        
	        //税点
	        $tax_point = in_array("layer_touch", $award_fee) ? $levels[$parent['user_level_id']]['tax'] : 0;
	        //备注
	        $remark = '会员' . $market_record['user_no'] . '被激活,获得层碰奖';
	         
	        //增加奖金纪录
	        $reward = $this->_addReward($market_record, $parent['user_no'], Constants::REWARD_TYPE_TOUCHLAYER, $award, $tax_point, $remark);
	    }
	}
	
	
	/**
	 * 满层奖
	 *
	 * @param  array   parents         业绩来源的安置关系
	 * @param  object  market_record   业绩对象
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月7日 下午3:58:10
	 * @author: lyx
	 */
	private function _floorAward($parents, $market_record, $award_fee, $levels) {
	    foreach ($parents as $parent) {
	        $where = array(
	                'pn.parent_no'     => $parent['user_no'],
	                'pn.floor'         => $parent['floor'],
	                'mr.market_type'   => Constants::MARKET_TYPE_ENROLL
	        );
	        $sub_markets = M('MarketRecord')->alias('mr')
                    ->field('mr.user_no,mr.amount')
        	        ->join(C('DB_PREFIX').'parent_nexus AS pn ON mr.user_no = pn.user_no')
        	        ->where($where)
        	        ->select();
	        $sub_market_count = count($sub_markets);
	        //判断是否有满层奖
	        if ($sub_market_count == pow(2, $parent['floor']) && $sub_markets[$sub_market_count-1]['user_no'] == $market_record['user_no']) {
	            //计算满层时，此层的总业绩
	            $sum_market = array_sum(array_column($sub_markets, 'amount'));
	            
	            //获取安置人的满层奖设置
	            $award_flag = false;
    	        $award_arr = json_decode($levels[$parent['user_level_id']]['point_award'],true);
    	        //解析奖项设置，获取当前层级对应设置
    	        foreach ($award_arr as $key => $award_item) {
    	            $key_item=explode("-",$key);
    	            if ($parent['floor'] >= $key_item[0]&&$parent['floor'] <= $key_item[1]) {
    	                $award_prize = $award_item;
    	                $award_flag = true;
    	            }
    	        }
    	
    	        //层级符合条件
    	        if ($award_flag) {
    	            //计算奖金
    	            $award = round($sum_market * $award_prize / 100, 2);
    	             
    	            //税点
    	            $tax_point = in_array("point", $award_fee) ? $levels[$parent['user_level_id']]['tax'] : 0;
    	            //备注
    	            $remark = '会员' . $market_record['user_no'] . '被激活,获得满层奖';
    	             
    	            //增加奖金纪录
    	            $reward = $this->_addReward($market_record, $parent['user_no'], Constants::REWARD_TYPE_LAYER, $award, $tax_point, $remark);
    	        }
	        }
	    }
	}
	
	/**
	 * 见点奖
	 *
	 * @param  array   parents         业绩来源的安置关系
	 * @param  object  market_record   业绩对象
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月7日 下午3:32:21
	 * @author: lyx
	 */
	private function _pointAward($parents, $market_record, $award_fee, $levels) {
	    foreach ($parents as $parent) {
	        //获取安置人的见点奖设置
	        $award_flag = false;
	        $award_arr = json_decode($levels[$parent['user_level_id']]['point_award'],true);
	        //解析奖项设置，获取当前层级对应设置
	        foreach ($award_arr as $key => $award_item) {
	            $key_item=explode("-",$key);
	            if ($parent['floor'] >= $key_item[0]&&$parent['floor'] <= $key_item[1]) {
	                $award_prize = $award_item;
	                $award_flag = true;
	            }
	        }
	        //层级符合条件
	        if ($award_flag) {
    	        //计算奖金
    	        $award = round($market_record['amount'] * $award_prize / 100, 2);
    	         
    	        //税点
    	        $tax_point = in_array("point", $award_fee) ? $levels[$parent['user_level_id']]['tax'] : 0;
    	        //备注
    	        $remark = '会员' . $market_record['user_no'] . '被激活,获得见点奖';
    	         
    	        //增加奖金纪录
    	        $reward = $this->_addReward($market_record, $parent['user_no'], Constants::REWARD_TYPE_POINT, $award, $tax_point, $remark);
	        }
	    }
	}
	
	/**
	 * 推荐奖
	 *
	 * @param  object  market_record   业绩对象
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月7日 下午13:15:15
	 * @author: lyx
	 */
	private function _recommendAward($market_record, $award_fee, $levels) {
	    //业绩来源的推荐关系
	    $recommends =  M('User')->alias('u')
        	    ->field('u.user_no,u.user_level_id,rn.rec_floor')
        	    ->join(C('DB_PREFIX').'recommend_nexus AS rn ON u.user_no = rn.recommend_no')
        	    ->where(array('rn.user_no'=>$market_record['user_no']))
        	    ->order('rn.rec_floor asc')
        	    ->select();
	    foreach ($recommends as $recommend) {
	        //获取推荐人的推荐奖设置
	        $award_flag = false;
	        $award_arr = json_decode($levels[$recommend['user_level_id']]['recommend_award'],true);
	        //解析奖项设置，获取当前代级对应设置
	        foreach ($award_arr as $key => $award_item) {
	            $key_item=explode("-",$key);
	            if ($recommend['rec_floor'] >= $key_item[0]&&$recommend['rec_floor'] <= $key_item[1]) {
	                $award_prize = $award_item;
	                $award_flag = true;
	            }
	        }
	        
	        //代级符合条件
	        if ($award_flag) {
	            //计算奖金
	            $award = round($market_record['amount'] * $award_prize / 100, 2);
	             
	            //税点
	            $tax_point = in_array("recommend", $award_fee) ? $levels[$recommend['user_level_id']]['tax'] : 0;
	            //备注
	            $remark = '会员' . $market_record['user_no'] . '被激活,获得推荐奖';
	             
	            //增加奖金纪录
	            $reward = $this->_addReward($market_record, $recommend['user_no'], Constants::REWARD_TYPE_RECOMMEND, $award, $tax_point, $remark);
	        }
	    }
	}
	
	/**
	 * 报单奖
	 * @param  object  source_user     业绩来源会员对象
	 * @param  object  market_record   业绩对象
	 * @param  object  award_fee       扣税对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月3日 上午10:15:15
	 * @author: lyx
	 */
	private function _serviceAward($source_user, $market_record, $award_fee, $levels) {
	    //获取报单中心内容
        $where = array(
                'u.user_no'  => $source_user['service_center_no'],
                'status'   => Constants::OPERATE_STATUS_CONFIRM
        );
        $service_center = M('User')->alias('u')
                ->field('u.user_level_id')
                ->join(C('DB_PREFIX').'service_center AS sc ON u.user_no = sc.user_no')
                ->where($where)->find();
            
        if ($service_center) {
            //获取报单中心的报单奖项设置
            $award_prize = $levels[$service_center['user_level_id']]['service_award'];
            //计算奖金
            $award = round($market_record['amount'] * $award_prize / 100, 2);
            
            //税点
            $tax_point = in_array("service", $award_fee) ? $levels[$service_center['user_level_id']]['tax'] : 0;
            //备注
            $remark = '会员' . $market_record['user_no'] . '被激活,获得报单奖';
            //增加奖金纪录
            $this->_addReward($market_record, $source_user['service_center_no'], Constants::REWARD_TYPE_DECLARATION, $award, $tax_point, $remark);
        } 
	}
	
	/**
	 * 添加奖金纪录
	 *
	 * @param  object  market_record   业绩对象
	 * @param  string  user_no         获取奖金的用户编号
	 * @param  int     type            奖金类型
	 * @param  float   amount          奖金金额
	 * @param  float   tax             扣税百分点
	 * @param  string  remark          备注
	 * 
	 * @return  int   新奖金纪录的id 
	 *
	 * @since: 2017年3月3日 下午4:20:32
	 * @author: lyx
	 */
	private function _addReward($market_record, $user_no, $type=Constants::REWARD_TYPE_TOUCH, $amount,$tax_point,$remark){
	    if ($amount > 0) {
	        //计算税
	        $tax = round($amount * $tax_point / 100, 2);
	        //计算奖金入账额
	        $total = $amount - $tax;
	        $data = array(
	                'market_record_id' => $market_record['id'],
	                'user_no'          => $user_no,
	                'remark'           => $remark,
	                'type'             => $type,
	                'amount'           => $amount,
	                'tax'              => $tax,
	                'total'            => $total,
	                'occur_time'       => $market_record['add_time'],
	                'add_time'         => curr_time()
	        );
	        //插入奖金纪录
	        $record = M('RewardRecord')->add($data); //计入事务队列
	         
	        //变更账户余额
	        $user_account = M('User')->where(array('user_no'  => $user_no))->setInc('mb_account', $total); //计入事务队列
	    }
	}
	
	/**
	 * 静态返本
	 *
	 * @param  object  market_record   业绩对象
	 * @param  array   levels          等级对象数组
	 *
	 * @since: 2017年3月15日 下午2:58:52
	 * @author: lyx
	 */
	private function _rebate($market_record, $levels) {
        //计算返本总计
	    $return_amount = M('ReturnRecord')->where(array('market_record_id'=> $market_record['id']))->sum('amount');
	    //返本封顶设置
	    $return_max = $levels[$market_record['user_level_id']]['return_max'];
	    //上次返回时间
	    $last_return_time = $market_record['return_time'];
	    //返回周期
	    $return_cycle = $levels[$market_record['user_level_id']]['return_cycle'];
	    //本次应返还的金额
	    $amount = round($levels[$market_record['user_level_id']]['investment'] * $levels[$market_record['user_level_id']]['multiple'] * $levels[$market_record['user_level_id']]['return_ratio'] / 100, 2);
	    
	    //未达到返本次数并且未达到返本封顶
	    if ($levels[$market_record['user_level_id']]['return_number'] > $market_record['return_number'] && $return_max > $return_amount){
	        //达到返回周期
	        if (date("Y-m-d",time())>=date('Y-m-d',strtotime("$last_return_time+$return_cycle day"))) {
	            //返本记录
	            $data = array(
	                    'id'               => $market_record['id'],
	                    'return_number'    => array('exp', 'return_number + 1'),
	                    'return_time'      => curr_time()
	            );
	            if ($amount >= ($return_max-$return_amount)) {
	                $amount = $return_max-$return_amount;
	                //返本结束
	                $data['return_is_over'] = Constants::YES;
	            }
	            //变更返本信息
	            $record = M('MarketRecord')->save($data); //计入事务队列
	            
	            //返本记录
	            $return_record = array(
	                    'market_record_id' => $market_record['id'],
	                    'user_no'          => $market_record['user_no'],
	                    'user_level_id'    => $market_record['user_level_id'],
	                    'market_type'      => $market_record['market_type'],
	                    'amount'           => $amount,
	                    'add_time'         => curr_time()
	            );
                $return_record_res = M('ReturnRecord')->add($return_record); //计入事务队列
	            //变更账户余额
	            $user_account = M('User')->where(array('user_no' => $market_record['user_no']))->setInc('rb_account', $amount); //计入事务队列
	        }
	    } else {
	        //返本结束
	        $data = array(
	                'id'               => $market_record['id'],
	                'return_is_over'   => Constants::YES
	        );
	        $record = M('MarketRecord')->save($data); //计入事务队列
	    }
	}
}