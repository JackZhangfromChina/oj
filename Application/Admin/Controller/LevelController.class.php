<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 会员级别控制器
 *
 * @since: 2016年12月21日 下午4:22:58
 * @author: lyx
 * @version: V1.0.0
 */
class LevelController extends AdminBaseController {

    /**
     * 初始化
     *
     * @since: 2017年5月10日 上午11:03:35
     * @author: lyx
     */
    public function _initialize() {
        parent::_initialize();
    
        $this->assign('system_state', $this->sys_config['WEB_SYSTEM_STATE']);
    }
    
    /**
     * 会员级别页面
     *
     * @since: 2016年12月21日 下午4:23:09
     * @author: lyx
     */
    public function index() {
        //获取所有会员级别信息
        $levels = M("UserLevel")
                    ->field('id,title,investment,status,update_time')
                    ->order('id asc')
                    ->select();
        $this->assign('levels',$levels);
        $this->display();
    }
    
    /**
     * 会员级别设置页面（包括保存设置）
     *
     * @since: 2016年12月21日 下午4:25:09
     * @author: lyx
     */
    public function set() {

        if (IS_POST) { //数据提交
            //系统在开启状态下，不允许进行敏感操作，以免引起数据异常
            if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
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
            unset($_POST['password']);
            
            //解析封装推荐奖设置信息
            if ($_POST['recommend_award_from'] && $_POST['recommend_award_to'] && $_POST['recommend_award_value']) {
                $recommend_award_arr = array();
                foreach ($_POST['recommend_award_from'] as $key => $from) {
                    $to = $_POST['recommend_award_to'][$key];
                    $value = $_POST['recommend_award_value'][$key];
                    if ($from && $to) {
                        $arr_key = intval($from) .'-'. intval($to);
                        $recommend_award_arr[$arr_key] = floatval($value);
                    }
                }
                unset($_POST['recommend_award_from']);
                unset($_POST['recommend_award_to']);
                unset($_POST['recommend_award_value']);
                $_POST['recommend_award'] = json_encode($recommend_award_arr);
            }
            
            //解析封装领导奖设置信息
            if ($_POST['leader_award_from'] && $_POST['leader_award_to'] && $_POST['leader_award_value']) {
                $leader_award_arr = array();
                foreach ($_POST['leader_award_from'] as $key => $from) {
                    $to = $_POST['leader_award_to'][$key];
                    $value = $_POST['leader_award_value'][$key];
                    if ($from && $to) {
                        $arr_key = intval($from) .'-'. intval($to);
                        $leader_award_arr[$arr_key] = floatval($value);
                    }
                }
                unset($_POST['leader_award_from']);
                unset($_POST['leader_award_to']);
                unset($_POST['leader_award_value']);
                $_POST['leader_award'] = json_encode($leader_award_arr);
            }
            
            //解析封装见点奖设置信息
            if ($_POST['point_award_from'] && $_POST['point_award_to'] && $_POST['point_award_value']) {
                $point_award_arr = array();
                foreach ($_POST['point_award_from'] as $key => $from) {
                    $to = $_POST['point_award_to'][$key];
                    $value = $_POST['point_award_value'][$key];
                    if ($from && $to) {
                        $arr_key = intval($from) .'-'. intval($to);
                        $point_award_arr[$arr_key] = floatval($value);
                    }
                }
                unset($_POST['point_award_from']);
                unset($_POST['point_award_to']);
                unset($_POST['point_award_value']);
                $_POST['point_award'] = json_encode($point_award_arr);
            }
            
            //解析封装层奖设置信息
            if ($_POST['floor_award_from'] && $_POST['floor_award_to'] && $_POST['floor_award_value']) {
                $floor_award_arr = array();
                foreach ($_POST['floor_award_from'] as $key => $from) {
                    $to = $_POST['floor_award_to'][$key];
                    $value = $_POST['floor_award_value'][$key];
                    if ($from && $to) {
                        $arr_key = intval($from) .'-'. intval($to);
                        $floor_award_arr[$arr_key] = floatval($value);
                    }
                }
                unset($_POST['floor_award_from']);
                unset($_POST['floor_award_to']);
                unset($_POST['floor_award_value']);
                $_POST['floor_award'] = json_encode($floor_award_arr);
            }
            
            //解析封装层碰奖设置信息
            if ($_POST['layer_touch_award_from'] && $_POST['layer_touch_award_to'] && $_POST['layer_touch_award_value']) {
                $layer_touch_award_arr = array();
                foreach ($_POST['layer_touch_award_from'] as $key => $from) {
                    $to = $_POST['layer_touch_award_to'][$key];
                    $value = $_POST['layer_touch_award_value'][$key];
                    if ($from && $to) {
                        $arr_key = intval($from) .'-'. intval($to);
                        $layer_touch_award_arr[$arr_key] = floatval($value);
                    }
                }
                unset($_POST['layer_touch_award_from']);
                unset($_POST['layer_touch_award_to']);
                unset($_POST['layer_touch_award_value']);
                $_POST['layer_touch_award'] = json_encode($layer_touch_award_arr);
            }
            
            //数据保存
            $_POST['update_time'] = curr_time();
            $re = M("UserLevel")->save($_POST);
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '设置成功！'
                );
                
                //操作日志
                $this->addLog('修改了' . I('post.title') . '的参数设置。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '设置失败！'
                );
            }
            $this->ajaxReturn($result);
            
        }
        
        //显示当前级别的信息
        $level = M("UserLevel")->find(I('get.id'));
        $this->assign('level',$level);
        //解析推荐奖设置
        if ($this->sys_config['AWARD_OPEN_RECOMMEND'] == Constants::YES) {
            $this->assign('recommend_award',$this->_parseAwardJson($level['recommend_award']));
        }
        //解析领导奖设置
        if ($this->sys_config['AWARD_OPEN_LEADER'] == Constants::YES) {
            $this->assign('leader_award',$this->_parseAwardJson($level['leader_award']));
        }
        //解析见点奖设置
        if ($this->sys_config['AWARD_OPEN_POINT'] == Constants::YES) {
            $this->assign('point_award',$this->_parseAwardJson($level['point_award']));
        }
        //解析层奖设置
        if ($this->sys_config['AWARD_OPEN_FLOOR'] == Constants::YES) {
            $this->assign('floor_award',$this->_parseAwardJson($level['floor_award']));
        }
        //解析层碰奖设置
        if ($this->sys_config['AWARD_OPEN_LAYER_TOUCH'] == Constants::YES) {
            $this->assign('layer_touch_award',$this->_parseAwardJson($level['layer_touch_award']));
        }
        //封装页面需要的系统配置信息
        $config = array(
                'is_open_touch_award'   => $this->sys_config['AWARD_OPEN_TOUCH'],
                'is_open_layer_touch_award'   => $this->sys_config['AWARD_OPEN_LAYER_TOUCH'],
                'is_open_service_award'   => $this->sys_config['AWARD_OPEN_SERVICE'],
                'is_open_recommend_award'   => $this->sys_config['AWARD_OPEN_RECOMMEND'],
                'is_open_leader_award'   => $this->sys_config['AWARD_OPEN_LEADER'],
                'is_open_point_award'   => $this->sys_config['AWARD_OPEN_POINT'],
                'is_open_floor_award'   => $this->sys_config['AWARD_OPEN_FLOOR'],
                'award_set_number'   => $this->sys_config['AWARD_SET_NUMBER'],
                'is_open_service_center'   => $this->sys_config['SYSTEM_OPEN_SERVICE_CENTER'],
                'is_open_mall'   => $this->sys_config['SYSTEM_OPEN_MALL'],
                'is_open_return'   => $this->sys_config['SYSTEM_OPEN_RETURN'],
        );
        $this->assign('config',$config);
        
        //如果允许修改会员级别信息，显示设置页面
        //如果不允许修改会员级别信息，显示展示页面
        if ($this->sys_config['SYSTEM_CAN_SET_USER_LEVEL'] == Constants::YES) {
            $this->display();
        } else {
            $this->display('show');
        }
    }
    
    /**
     * 保存会员级别状态
     *
     * @since: 2016年12月21日 下午4:43:28
     * @author: lyx
     */
    public function setStatus() {
    
        if (IS_POST) { //数据提交
            //系统在开启状态下，不允许进行敏感操作，以免引起数据异常
            if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
                $result = array(
                        'status'  => false,
                        'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
                );
                $this->ajaxReturn($result);
            }
            
            $op_name = I('post.status') ? '开启' : '关闭';
            $level = M("UserLevel")->field('title')->find(I('post.id'));
            
            //保存设置
            $where = array(
                    'id'  => I('post.id')
            );
            $data = array(
                    'status'        => I('post.status') ? Constants::YES : Constants::NO,
                    'update_time'   => curr_time()
            );
            $re = M("UserLevel")->where($where)->save($data);
            
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '设置成功！'
                );
                
                //操作日志
                $this->addLog($op_name . $level['title'] . '级别。', I('post.id'));
                
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '设置失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }
    
    /**
    * 解析奖项设置的json串
    *
    * @param    string  $award_json 需要解析的json串
    * @return   array   解析后的数组
    *               from    开始
    *               to      结束
    *               value   对应的值
    *
    * @since: 2016年12月22日 下午12:04:14
    * @author: lyx
    */
    private function _parseAwardJson($award_json){
        $award_arr = json_decode($award_json,true);
        $new_awards = array();
        foreach ($award_arr as $key => $award) {
            $key_arr=explode("-",$key);
            if ((count($key_arr)==1 || count($key_arr)==2 ) && $key_arr[0]!=0) {
                $new_awards[] = array(
                        'from'  => intval($key_arr[0]),
                        'to'    => intval($key_arr[1]) ? intval($key_arr[1]) : intval($key_arr[0]),
                        'value' => floatval($award)
                );
            }
        }
        return $new_awards;
    }
}