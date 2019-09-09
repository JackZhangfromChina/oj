<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 站内信模块管理控制器
 *
 * @since: 2017年1月7日 下午4:15:22
 * @author: lyx
 * @version: V1.0.0
 */
class MailController extends AdminBaseController {

    /**
    * 站内信列表
    *
    * @since: 2017年1月7日 下午5:35:40
    * @author: lyx
    */
    public function index(){
        
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
        
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
            $map['receiver_no'] = array('like', '%' . $keyword . '%') ;
            $map['sender_no'] = array('like', '%' . $keyword . '%') ;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
        
        //查询数据
        $mails = D('Mail')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
        
        //返回页面的数据
        $this->assign('list', $mails['data']);
        $this->assign('page', $mails['page']);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }
    
    /**
     * 发件箱
     *
     * @since: 2017年1月7日 下午5:36:18
     * @author: lyx
     */
    public function outbox(){
    
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
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
            $where['receiver_no'] = array('like', '%' . $keyword . '%') ;
        }
        $where['sender_no'] = '';
        $where['is_sender_delete'] = Constants::NO;
    
        //查询数据
        $mails = D('Mail')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $mails['data']);
        $this->assign('page', $mails['page']);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }
    
    /**
     * 收件箱
     *
     * @since: 2017年1月7日 下午5:36:40
     * @author: lyx
     */
    public function inbox(){
    
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
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
            $where['sender_no'] = array('like', '%' . $keyword . '%') ;
        }
        $where['receiver_no'] = '';
        $where['is_receiver_delete'] = Constants::NO;
    
        //查询数据
        $mails = D('Mail')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $mails['data']);
        $this->assign('page', $mails['page']);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }
    
    /**
     * 未读邮件列表
     *
     * @since: 2017年1月7日 下午5:37:23
     * @author: lyx
     */
    public function unread(){
    
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
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
        $where['is_read'] = Constants::NO;
        //关键词
        if ($keyword) {
            $where['sender_no'] = array('like', '%' . $keyword . '%') ;
        }
        $where['receiver_no'] = '';
    
        //查询数据
        $mails = D('Mail')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $mails['data']);
        $this->assign('page', $mails['page']);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }
    
    /**
    * 写站内信页面（包括发件操作）
    *
    * @since: 2017年1月7日 下午5:40:26
    * @author: lyx
    * 
    * @since: 2017年1月22日 下午5:26:09
    * @author: lyx
    */
	public function add(){
        
        if (IS_POST) { //数据提交
            $Mail = D('Mail'); // 实例化对象
            if (!$Mail->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Mail->getError()
                );
            } else {
    
                $Mail->sender_no = '';
                $Mail->content = $_POST['content'];
                //验证短信息发送
                $add_res = $Mail->add();
				if($add_res !== false){
					$result = array(
					        'status'  => true,
					        'message' => '邮件发送成功'
					);
					
					//操作日志
					$this->addLog('给会员' . I('post.receiver_no') . '的发送标题为' . I('post.title') . '的站内信。', $add_res);
				}else{
				    $result = array(
				            'status'  => false,
				            'message' => '邮件发送失败'
				    );
				} 
            }
            $this->ajaxReturn($result);
        }

        $this->display();
	}

    /**
     * 查看内容
     *
     * @since: 2016年12月18日 上午10:10:10
     * @author: Wang Peng
     */
    public function detail(){
        
        $mail = D('Mail')->find(I('id'));
        
        //查看未读短信
        if ($mail['receiver_no'] == '' && $mail['is_read'] == Constants::NO) {
            
            //更新阅读状态
            $data = array(
                    'id'        => I('id'),
                    'is_read'   => Constants::YES,
                    'read_time' => curr_time()
            );
            M('Mail')->save($data);
            
            //未读短信数量更新
            $where = array(
                    'receiver_no'  => '',
                    'is_read'      => Constants::NO
            );
            $unread_count = D('Mail')->where($where)->count('id');
            $this->assign('unread_count',$unread_count);
         }
         
         $this->assign('mail',$mail);
         $this->display();
    }

   /**
    * 删除发件箱信息
    *
    * @since: 2016年12月27日 上午9:30:51
    * @author: Wang Peng
    * 
    * @since: 2017年1月22日 下午3:10:52
    * @updater: lyx
    */
    public function senderDel(){
        if (IS_POST) { //数据提交
            $mail = M('Mail')->find(I('post.id'));
             
            //删除
            $data = array(
                    'id'                 => I('post.id'),
                    'is_sender_delete' => Constants::YES
            );
            $res = M('Mail')->save($data);
             
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                //操作日志
                $this->addLog('删除标题为' . $mail['title'] . '的邮件。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '删除失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }
    
    /**
    * 删除收件箱信息
    *
    * @since: 2016年12月27日 上午9:30:51
    * @author: Wang Peng
    * 
    * @since: 2017年1月22日 下午3:10:29
    * @updater: lyx
    */
    public function receiverDel(){
        if (IS_POST) { //数据提交
            $mail = M('Mail')->find(I('post.id'));
       
            //删除
            $data = array(
                    'id'                 => I('post.id'),
                    'is_receiver_delete' => Constants::YES
            );
            $res = M('Mail')->save($data);
           
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                //操作日志
                $this->addLog('删除标题为' . $mail['title'] . '的邮件。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '删除失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }
}