<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

/**
 * 短信管理控制器
 *
 * @since: 2017年1月13日 下午1:43:24
 * @author: lyx
 * @version: V1.0.0
 */
class MessageController extends AdminBaseController {

    /**
     * 短信发送列表页
     *
     * @since: 2017年1月13日 下午1:44:32
     * @author: lyx
     */
    public function index() {
        $type = I('type');
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
        //账号类型
        if ((isset($_GET["type"]) || isset($_POST["type"])) && $type != '-1') {
            $where['type'] = $type;
        }
        //状态
        if ((isset($_GET["status"]) || isset($_POST["status"])) && $status != '-1') {
            $where['status'] = $status;
        }
        //时间
        if ($start_date && $end_date) {
            $where['send_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['send_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['send_time'] = array('ELT', $end_date . ' 23:59:59');
        }
        //关键词
        if ($keyword) {
            $map['user_no'] = array('like', '%' . $keyword . '%') ;
            $map['phone'] = array('like', '%' . $keyword . '%') ;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
    
        //查询数据
        $banks = D('Message')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $banks['data']);
        $this->assign('page', $banks['page']);
        $this->assign('type', $type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('status', $status);
        $this->display();
    }
}