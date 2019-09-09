<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 参数配置控制器
 *
 * @since: 2016年12月20日 上午11:33:20
 * @author: lyx
 * @version: V1.0.0
 */
class ConfigController extends AdminBaseController {

    /**
     * 初始化
     *
     * @since: 2017年5月10日 上午10:27:54
     * @author: lyx
     */
    public function _initialize() {
        parent::_initialize();

        $this->assign('system_state', $this->sys_config['WEB_SYSTEM_STATE']);
    }

    /**
     * 参数配置欢迎页
     *
     * @since: 2017年1月7日 下午3:00:44
     * @author: lyx
     */
    public function index() {

        $this->display();
    }

    /**
    * 系统设置页面（包括设置保存）
    *
    * @since: 2016年12月20日 上午11:33:33
    * @author: lyx
    */
    public function system() {
        if (IS_POST) { //数据提交
            //保存配置信息
            $this->_save('系统设置');
        }

        $configs  = D('Config')->getByGroupName('系统设置');
        $this->assign('configs',$configs);
        $this->assign('is_open_mall', $this->sys_config['SYSTEM_OPEN_MALL']);
        $this->display();
    }

    /**
     * 站点设置页面
     *
     * @since: 2016年12月20日 上午11:42:30
     * @author: lyx
     */
    public function website() {
        if (IS_POST) { //数据提交
            //保存配置信息
            $this->_save('站点设置');
        }

        $configs  = D('Config')->getByGroupName('站点设置');
        $this->assign('configs',$configs);
        $this->display();
    }

    /**
     * 奖项设置页面
     *
     * @since: 2016年12月20日 上午11:37:04
     * @author: lyx
     */
    public function award() {
        if (IS_POST) { //数据提交
            //保存配置信息
            $this->_save('奖项设置');
        }

        $configs  = D('Config')->getByGroupName('奖项设置');
        $fees = json_decode($configs['AWARD_FEE']['value'],true);
        $this->assign('configs',$configs);
        $this->assign('fees',$fees);
        $this->assign('is_open_service_center',$this->sys_config['SYSTEM_OPEN_SERVICE_CENTER']);
        $this->display();
    }

    /**
     * 会员设置页面
     *
     * @since: 2016年12月20日 上午11:37:27
     * @author: lyx
     */
    public function user() {
        if (IS_POST) { //数据提交
            //保存配置信息
            $this->_save('会员设置');
        }

        $configs  = D('Config')->getByGroupName('会员设置');
        $enroll_items = json_decode($configs['USER_ENROLL_ITEM']['value'],true);
        $this->assign('configs',$configs);
        $this->assign('enroll_items',$enroll_items);
        $this->display();
    }

    /**
     * 短信设置页面
     *
     * @since: 2017年1月6日 上午11:17:28
     * @author: lyx
     */
    public function message() {
        if (IS_POST) { //数据提交
            //保存配置信息
            $this->_save('短信设置');
        }

        $configs  = D('Config')->getByGroupName('短信设置');
        //获取短信发送设置信息
        $json_item = array("MESSAGE_FORGET", "MESSAGE_ACTIVATE", "MESSAGE_WITHDRAW",
                            "MESSAGE_DEDUCT", "MESSAGE_RECHARGE", "MESSAGE_ROLL_IN",
                            "MESSAGE_ROLL_OUT", "MESSAGE_REMIT", "MESSAGE_BUY", "MESSAGE_ORDER", "MESSAGE_PAY");
        foreach ($json_item as $value) {
            $sets[$value] = json_decode($configs[$value]['value'],true);
        }
        $this->assign('configs',$configs);
        $this->assign('sets',$sets);
        $this->assign('is_open_mall', $this->sys_config['SYSTEM_OPEN_MALL']);
        $this->display();
    }

    /**
     * 支付设置页面
     *
     * @since: 2017年1月6日 下午5:51:37
     * @author: lyx
     */
    public function pay() {
        if (IS_POST) { //数据提交
            //保存配置信息
            $this->_save('支付设置');
        }

        $configs  = D('Config')->getByGroupName('支付设置');
        $this->assign('configs',$configs);
        $this->display();
    }

    /**
     * 邮件设置页面
     *
     * @since: 2017年2月15日 下午1:35:49
     * @author: lyx
     */
    public function mail() {
        if (IS_POST) { //数据提交
            //保存配置信息
            $this->_save('邮件设置');
        }

        $configs  = D('Config')->getByGroupName('邮件设置');
        $this->assign('configs',$configs);
        $this->display();
    }

    /**
     * 设置保存
     *
     * @since: 2016年12月20日 下午5:59:09
     * @author: lyx
     */
    private function _save($group_name) {
        if (IS_POST) { //数据提交
            $i = 0;
            $post_key = array_keys($_POST);

            //系统在开启状态下，不允许进行敏感操作，以免引起数据异常（开启关闭系统或是站点设置除外）
            if (!in_array('WEB_SYSTEM_STATE', $post_key) && $this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
                $result = array(
                        'status'  => false,
                        'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
                );
                $this->ajaxReturn($result);
            }

            //再次确认密码
            if (!password_verify($_POST['password'], $this->admin['password'])) {
                $result = array(
                        'status'  => false,
                        'message' => '口令不正确！'
                );
                $this->ajaxReturn($result);
            }

            foreach ($post_key as $key) {
                $where = array(
                        'code'  => $key
                );
                if (is_array($_POST[$key])) {
                    $data = array(
                            'value'         => json_encode($_POST[$key]),
                            'update_time'   => curr_time()
                    );
                } else {
                    $data = array(
                            'value'         => $_POST[$key],
                            'update_time'   => curr_time()
                    );
                }

                //保存设置
                $re = M("Config")->where($where)->save($data);
                if ($re !== false) {
                    $i++;
                }
            }

            if ($i == count($post_key)) {
                $result = array(
                        'status'  => true,
                        'message' => '设置成功！'
                );
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '部分设置保存失败！'
                );
            }

            //操作日志
            $this->addLog('对' . $group_name . '进行了参数值的修改。');

            $this->ajaxReturn($result);
        }
    }

    /**
     * 短信模板列表
     */
    public function smsTemplate()
    {
        $cates = D('MessageTemplate')->getList(Constants::YES, array(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        $this->assign('list', $cates['data']);
        $this->assign('page', $cates['page']);
        $this->display();
    }
    /**
     * 添加短信模板
     */
    public function addSmsTemplate()
    {
        if (IS_POST) {
            $obj = D('MessageTemplate');
            if (!$obj->create()) {
                $result = [
                    'status' => false,
                    'message' => $obj->getError(),
                ];
            } else {
                $flag = $obj->add();
                if ($flag !== false) {
                    $result = [
                        'status' => true,
                        'message' => '添加成功！',
                    ];
                    //操作日志
                    $this->addLog('添加编号为' . I('post.no') . '的短信模板。', $flag);
                } else {
                    $result = [
                        'status' => false,
                        'message' => '添加失败！',
                    ];
                }
            }
            $this->ajaxReturn($result);
        }
    }
    /**
     * 编辑短信模板
     */
    public function editSmsTemplate()
    {
        if (IS_POST) {
            $obj = D('MessageTemplate');
            if (!$obj->create()) {
                $result = [
                    'status' => false,
                    'message' => $obj->getError(),
                ];
            } else {
                $data = M('MessageTemplate')->find(I('post.id'));
                $flag = $obj->save();
                if ($flag !== false) {
                    $result = [
                        'status' => true,
                        'message' => '修改成功！',
                    ];
                    //操作日志
                    $this->addLog('修改编号为' . $data['no'] . '的短信模板。', I('post.no'));
                } else {
                    $result = [
                        'status' => false,
                        'message' => '修改失败！',
                    ];
                }
            }
            $this->ajaxReturn($result);
        }
    }
    /**
     * 删除短信模板
     */
    public function deleteSmsTemplate()
    {
        if (IS_POST) {
            $id = I('post.id');
            // 检查模板是否存在
            $data = M('MessageTemplate')->find($id);
            if (!$data) {
                $result = [
                    'status' => false,
                    'message' => '参数错误！',
                ];
                $this->ajaxReturn($result);
            }
            //查询模板是否在使用
            $config_sys_id = M('ConfigGroup')->getFieldByTitle('短信设置','id');
            $count = D('Config')->where([
                    'config_group_id' => $config_sys_id,
                    'value' => ['like', '%"'.$data['no'].'"%'],
                ])->count('id');
            if ($count > 0) {
                $result = [
                    'status' => false,
                    'message' => '该模板正在使用，不能删除！',
                ];
                $this->ajaxReturn($result);
            }
            //删除
            $flag = M("MessageTemplate")->delete($id);
            if ($flag !== false) {
                $result = [
                    'status' => true,
                    'message' => '删除成功！',
                ];
                //操作日志
                $this->addLog('删除编号为' . $data['no'] . '的短信模板。', $id);
            } else {
                $result = [
                    'status' => false,
                    'message' => '删除失败！',
                ];
            }
            $this->ajaxReturn($result);
        }
    }
}