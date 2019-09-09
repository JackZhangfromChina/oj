<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 后台首页控制器
 *
 * @since: 2016年12月9日 下午4:31:19
 * @author: lyx
 * @version: V1.0.0
 */
class IndexController extends AdminBaseController {
    
    /**
    * 后台登陆后的首页展示
    *
    * @since: 2016年12月20日 上午11:19:08
    * @author: lyx
    */
    public function index() {
        
        //显示title
        $this->assign('title', '后台管理中心' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
        //显示seo关键字
        $this->assign('keywords', $this->sys_config['WEB_KEYWORD']);
        //显示seo描述
        $this->assign('description', $this->sys_config['WEB_DESCRIPTION']);
        //显示网站logo
        if (trim($this->sys_config['WEB_LOGO'])) {
            $this->assign('logo', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_LOGO']);
        }
        //显示网站默认头像
        if (trim($this->sys_config['WEB_DEFAULT_AVATAR'])) {
            $this->assign('head_portrait', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_DEFAULT_AVATAR']);
        }
        //显示未读邮件数目
        $count = D('Mail')->where(array('receiver_no'=>'','is_read'=>0))->count('id');
        $this->assign('unreadcount',$count);
        $this->display();
    }
    
    /**
    * 后台欢迎页面
    *
    * @since: 2016年12月20日 上午11:19:36
    * @author: lyx
    */
    public function home() {
        
        //总会员数
        $user_count = M('User')->count('id');
        
        //级别对应的会员数量
        $user_total = M('User')->alias('u')
                ->field('ul.title,count(ul.id) AS count')
                ->join($this->db_prefix.'user_level AS ul ON ul.id = u.user_level_id')
                ->group('ul.id')->select();
        
        //今日注册总会员数
        
        $where['add_time'] = array(
                array('EGT', date("Y-m-d",time()) . ' 00:00:00')
        ) ;
        $day_user_count = M('User')->where($where)->count('id');
        
        //总收入
        $market_total = M('MarketRecord')
                                ->field('IFNULL(SUM(amount),0) as sum_total')
                                ->find();
        
        //今日收入
        $day_market_total = M('MarketRecord')->where($where)
                                ->field('IFNULL(SUM(amount),0) as sum_total')
                                ->where($where)
                                ->find();
        
        //总支出
        $reward_total = M('RewardRecord')
                                ->field('IFNULL(SUM(total),0) as sum_total')
                                ->find();
        
        //今日支出
        $day_reward_total = M('RewardRecord')->where($where)
                                ->field('IFNULL(SUM(total),0) as sum_total')
                                ->where($where)
                                ->find();
        
        $con_where['status'] = Constants::OPERATE_STATUS_CONFIRM;
        $init_where['status'] = Constants::OPERATE_STATUS_INITIAL;
        
        //总汇款
        $remit_confirm = M('Remit')
                                ->field('IFNULL(SUM(amount),0) as sum_total')
                                ->where($con_where)
                                ->find();
        //未处理汇款
        $remit_initial = M('Remit')
                                ->field('IFNULL(SUM(amount),0) as sum_total')
                                ->where($init_where)
                                ->find();
        //总提现
        $withdraw_confirm = M('Withdraw')
                                ->field('IFNULL(SUM(amount),0) as sum_total')
                                ->where($con_where)
                                ->find();
        //未处理提现
        $withdraw_initial = M('Withdraw')
                                ->field('IFNULL(SUM(amount),0) as sum_total')
                                ->where($init_where)
                                ->find();
            
        //默认获取当月第一天至当前
        $market_where['add_time'] = array(
                array('EGT', date("Y-m",time()) . '-01 00:00:00'),
                array('ELT', date("Y-m-d H:i:s",time()))
        ) ;
        $market_data = D('MarketRecord')->getReport($market_where);
        
        // 多条公告
        $news = M('News')->field('id,title')->where(array('status' => Constants::NORMAL))->order('id desc') ->limit(8)->select();
        
        //返回页面的数据
        $this->assign('user_count', $user_count);
        $this->assign('user_total', $user_total);
        $this->assign('day_user_count', $day_user_count);
        
        $this->assign('market_total', $market_total['sum_total']);
        $this->assign('day_market_total', $day_market_total['sum_total']);
        
        $this->assign('reward_total', $reward_total['sum_total']);
        $this->assign('day_reward_total', $day_reward_total['sum_total']);
        
        $this->assign('remit_confirm', $remit_confirm['sum_total']);
        $this->assign('remit_initial', $remit_initial['sum_total']);
        $this->assign('withdraw_confirm', $withdraw_confirm['sum_total']);
        $this->assign('withdraw_initial', $withdraw_initial['sum_total']);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->assign('news', $news);
        $this->assign('market_data', json_encode($market_data['report']));
        $this->display();
    }
    
    /**
     * 无权访问的页面提示
     *
     * @since: 2017年1月9日 上午10:41:45
     * @author: lyx
     */
    public function unAuth(){
    
        $this->assign('title', '无权操作');
        $this->assign('tips', I('tips'));
        $this->display();
    }
}