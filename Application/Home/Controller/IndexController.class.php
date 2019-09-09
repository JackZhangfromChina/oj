<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Conf\Constants;

/**
 * 前台首页控制器
 *
 * @since: 2016年12月9日 下午4:31:19
 * @author: lyx
 * @version: V1.0.0
 */
class IndexController extends HomeBaseController {
    
    public function index(){
        echo 123;exit;
        //显示title
        $this->assign('title', '会员中心' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
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
        $userno = $this->user['user_no'];
        $count = D('Mail')->where(array('receiver_no'=>$userno,'is_read'=>0))->count('id');
        $this->assign('unreadcount',$count);

        $this->display();
    }
    
    public function home(){
        $level =  D('UserLevel')->field('title')->where(array('id'=>$this->user['user_level_id']))->find();
        
        //团队会员数
        $user_count = M('ParentNexus')->where(array('parent_no'=>$this->user['user_no']))->count();
        //推荐会员数
        $recommend_count = M('RecommendNexus')->where(array('recommend_no'=>$this->user['user_no']))->count();
        //下属会员数
        $center_count = M('User')->where(array('service_center_no'=>$this->user['user_no']))->count('id');
        
        //总奖金
        $reward_total = M('RewardRecord')
                            ->field('IFNULL(SUM(total),0) as sum_total')
                            ->where(array('user_no'=>$this->user['user_no']))
                            ->find();
        $where['status'] = Constants::OPERATE_STATUS_CONFIRM;
        $where['user_no'] = $this->user['user_no'];
        //总汇款
        $remit_confirm = M('Remit')
                            ->field('IFNULL(SUM(amount),0) as sum_total')
                            ->where($where)
                            ->find();
        //总提现
        $withdraw_confirm = M('Withdraw')
                            ->field('IFNULL(SUM(amount),0) as sum_total')
                            ->where($where)
                            ->find();
        
        // 多条公告
        $news = M('News')->field('id,title')->where(array('status' => Constants::NORMAL))->order('id desc') ->limit(8)->select();
        
        //返回页面的数据
        $this->assign('level', $level['title']);
        $this->assign('user_count', $user_count);
        $this->assign('recommend_count', $recommend_count);
        $this->assign('center_count', $center_count);
        $this->assign('reward_total', $reward_total['sum_total']);
        $this->assign('remit_confirm', $remit_confirm['sum_total']);
        $this->assign('withdraw_confirm', $withdraw_confirm['sum_total']);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->assign('is_open_service_center', $this->sys_config['SYSTEM_OPEN_SERVICE_CENTER']);
        $this->assign('news', $news);
        $this->display();
    }
    
    /**
    * 系统关闭的页面提示
    *
    * @since: 2017年1月9日 上午10:01:25
    * @author: lyx
    */
    public function close(){
    
        $this->assign('title', '系统暂停服务');
        $this->assign('close_content', $this->sys_config['WEB_CLOSE_PROMPT_TEXT']);
        $this->display();
    }
    
    /**
     * 无权访问的页面提示
     *
     * @since: 2017年1月9日 上午10:01:25
     * @author: lyx
     */
    public function unAuth(){
    
        $this->assign('title', '无权操作');
        $this->assign('tips', I('tips'));
        $this->display();
    }
}