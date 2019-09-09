<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

/**
 * 统计报表管理控制器
 *
 * @since: 2017年1月14日 下午1:47:46
 * @author: lyx
 * @version: V1.0.0
 */
class ReportController extends AdminBaseController {
    
    /**
     * 业绩报表页面（默认是当月的报表）
     *
     * @since: 2017年1月14日 下午1:49:43
     * @author: lyx
     */
    public function market() {
        $market_type = I('market_type');
        $start_date = I('start_date');
        $end_date = I('end_date');

        //业绩类型
        if ((isset($_GET["market_type"]) || isset($_POST["market_type"])) && $market_type != '-1') {
            $where['market_type'] = $market_type;
        }
        
        //默认获取当月第一天至当前
        if(!$start_date) {
            $start_date = date("Y-m",time()) . "-01";
        }
        if(!$end_date) {
            $end_date = date("Y-m-d",time());
        }
    
        //时间
        if ($start_date && $end_date) {
            $where['add_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['add_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['add_time'] = array('ELT', $end_date . ' 23:59:59');
        }
    
        //查询数据
        $market_data = D('MarketRecord')->getReport($where);
        
        //返回页面的数据
        $this->assign('data', json_encode($market_data['report']));
        $this->assign('statistics', $market_data['statistics']);
        $this->assign('market_type', $market_type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
    
    /**
     * 会员报表页面（默认是当月的报表）
     *
     * @since: 2017年1月14日 下午1:49:43
     * @author: lyx
     */
    public function user() {
        $level = I('level');
        $start_date = I('start_date');
        $end_date = I('end_date');
        
        //会员级别
        if ((isset($_GET["level"]) || isset($_POST["level"])) && $level != '-1') {
            $where['user_level_id'] = $level;
        }
    
        //默认获取当月第一天至当前
        if(!$start_date) {
            $start_date = date("Y-m",time()) . "-01";
        }
        if(!$end_date) {
            $end_date = date("Y-m-d",time());
        }
    
        //时间
        if ($start_date && $end_date) {
            $where['add_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['add_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['add_time'] = array('ELT', $end_date . ' 23:59:59');
        }
    
        //查询数据
        $user_data = D('User')->getReport($where);
        
        //获取所有会员级别信息
        $levels = M("UserLevel")
                    ->field('id,title')
                    ->order('id asc')
                    ->select();
        $this->assign('levels',$levels);
        
        //返回页面的数据
        $this->assign('data', json_encode($user_data['report']));
        $this->assign('statistics', $user_data['statistics']);
        $this->assign('level', $level);
        $this->assign('levels', $levels);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->display();
    }
    
    /**
     * 奖金报表页面（默认是当月的报表）
     *
     * @since: 2017年1月14日 下午4:15:38
     * @author: lyx
     */
    public function reward() {
        $type = I('type');
        $start_date = I('start_date');
        $end_date = I('end_date');
    
        //奖项类型
        if ((isset($_GET["type"]) || isset($_POST["type"])) && $type != '-1') {
            $where['type'] = $type;
        }
        
        //默认获取当月第一天至当前
        if(!$start_date) {
            $start_date = date("Y-m",time()) . "-01";
        }
        if(!$end_date) {
            $end_date = date("Y-m-d",time());
        }
    
        //时间
        if ($start_date && $end_date) {
            $where['add_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['add_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['add_time'] = array('ELT', $end_date . ' 23:59:59');
        }
    
        //查询数据
        $market_data = D('RewardRecord')->getReport($where);
    
        //封装页面需要的系统配置信息
        $config = array(
                'is_open_touch_award'   => $this->sys_config['AWARD_OPEN_TOUCH'],
                'is_open_service_award'   => $this->sys_config['AWARD_OPEN_SERVICE'],
                'is_open_recommend_award'   => $this->sys_config['AWARD_OPEN_RECOMMEND'],
                'is_open_leader_award'   => $this->sys_config['AWARD_OPEN_LEADER'],
                'is_open_point_award'   => $this->sys_config['AWARD_OPEN_POINT'],
                'is_open_floor_award'   => $this->sys_config['AWARD_OPEN_FLOOR'],
                'is_open_layer_touch'   => $this->sys_config['AWARD_OPEN_LAYER_TOUCH'],
                'is_open_service_center'    => $this->sys_config['SYSTEM_OPEN_SERVICE_CENTER'],
                'currency_symbol'   => $this->sys_config['SYSTEM_CURRENCY_SYMBOL']
        );
        $this->assign('config',$config);
        
        //返回页面的数据
        $this->assign('data', json_encode($market_data['report']));
        $this->assign('statistics', $market_data['statistics']);
        $this->assign('type', $type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->display();
    }
    
    /**
     * 收支图表页面
     *
     * @since: 2017年1月14日 下午4:57:26
     * @author: lyx
     */
    public function inOut() {
        /* $type = I('type');
        switch ($type) {
            case 'year':
                $where['add_time'] = array(
                        array('EGT', date("Y",time()) . '-01-01 00:00:00'),
                        array('ELT', date("Y-m-d",time()) . ' 23:59:59')
                ) ;
                break;
            case 'month':
                $where['add_time'] = array(
                        array('EGT', date("Y-m",time()) . '-01 00:00:00'),
                        array('ELT', date("Y-m-d",time()) . ' 23:59:59')
                ) ;
                break;
            case 'day':
                $where['add_time'] = array(
                        array('EGT', date("Y-m-d",time()) . ' 00:00:00'),
                        array('ELT', date("Y-m-d",time()) . ' 23:59:59')
                ) ;
                break;
            default:
                $where = 1;
        } */
        
        $start_date = I('start_date');
        $end_date = I('end_date');
        
        //时间
        if ($start_date && $end_date) {
            $where['add_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['add_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['add_time'] = array('ELT', $end_date . ' 23:59:59');
        }

        //总收支
        $total['in'] = M('MarketRecord')->where($where)
                                        ->field('IFNULL(SUM(amount),0) as total')
                                        ->find();
        $total['out'] = M('RewardRecord')->where($where)
                                        ->field('IFNULL(SUM(amount),0) as sum_amount,IFNULL(SUM(total),0) as sum_total')
                                        ->find();
        
        if ($total['out']['sum_total']>0 && $total['in']['total']>0) {
            $percent = round( $total['out']['sum_total']/$total['in']['total'] * 100 , 2) . "％";
        } else {
            $percent = "0％";
        }
        
        //返回页面的数据
        $this->assign('total', $total);
        $this->assign('percent', $percent);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
}