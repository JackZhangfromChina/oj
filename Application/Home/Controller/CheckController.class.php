<?php
namespace Home\Controller;
use Common\Controller\PublicBaseController;
use Common\Conf\Constants;

/**
 * 验证管理控制器
 *
 * @since: 2016年12月28日 上午9:50:02
 * @author: lyx
 * @version: V1.0.0
 */
class CheckController extends PublicBaseController {

    /**
    * 根据类型验证会员信息是否存在
    *
    * @param    string  type        需要验证的类型（user_no、email..）
    * @param    string  user_no     会员编号
    * @return   json串
    *
    * @since: 2016年12月28日 上午9:50:18
    * @author: lyx
    */
    public function existUserInfo() {
        
        $isAvailable = true;
        switch ($_POST['type']) {
            case 'email':
//                 $isAvailable = I('user_no') ? D('User')->checkExist(I('email')) : false;
                break;
            case 'user_no':
                $isAvailable = I('user_no') ? D('User')->checkExist('user_no', I('user_no')) : false;
                break;
            case 'phone':
                $isAvailable = I('phone') ? D('User')->checkExist('phone', I('phone')) : false;
                break;
            default:
                $isAvailable = I('user_no') ? D('User')->checkExist('user_no', I('user_no')) : false;
                break;
        }
        
        $result = array(
                'valid' => $isAvailable,
            );
        
        $this->ajaxReturn($result);
    }
    
    /**
     * 根据会员编号验证当前会员是否可见
     *
     * @param    string  user_no     会员编号
     * @return   json串
     *
     * @since: 2016年12月28日 下午2:18:54
     * @author: lyx
     */
    public function existUserVisible() {
        $result = array(
                'status'    => false,
                'realname'  => ""
        );
        $user=M('User')->find($_SESSION['user']['id']);
    
        if (I('user_no') && $user) {
            
            //判断是否是同一人
            if (I('user_no') != $user['user_no']) {
                //获取用真实姓名
                $another = M('User')->getByUserNo(I('user_no'));
                if ($another) {
                    //判断是否允许跨区域操作，两个会员是否在同一区域内
                    if ($this->sys_config['USER_CROSS_REGION'] == Constants::YES || strpos($another['path'], $user['path']) !== false || strpos($user['path'], $another['path']) !== false) {
                        $result = array(
                                'status'    => true,
                                'realname'  => $another['realname']
                        );
                    } else {
                        //表示会员存在，但是两个会员不在同一区域并且系统不允许跨区域操作
                        $result = array(
                                'status'    => false,
                                'realname'  => $another['realname']
                        );
                    }
                }
            } else {
                $result = array(
                        'status'    => false,
                        'realname'  => $user['realname']
                );
            }
        }
    
        $this->ajaxReturn($result);
    }
    
    /**
     * 验证银行卡信息是否存在
     *
     * @param    int        id          银行卡设置id
     * @param    string     bank_no     银行卡号
     * @return   json串
     *
     * @since: 2016年12月28日 上午9:50:18
     * @author: lyx
     */
    public function checkBankNo() {
        $isExist = I('user_no') ? D('User')->checkExist('user_no', I('user_no')) : false;
        $where = array(
                'user_no'   => I('post.user_no'),
                'bank_no'   => I('post.bank_no')
        );
        $bank = M('Bank')->where($where)->select();
        
        if (!$bank) {
            $isExist = true;
        } elseif(count($bank) == 1){
            $isExist = $bank[0]['id']==I('post.id') ? true : false;
        } else {
            $isExist = false;
        }
        $result = array(
                'status'    => $isExist
        );
    
        $this->ajaxReturn($result);
    }
    
    /**
     * 验证会员扩展信息是否存在
     *
     * @param    string     user_no     会员编号
     * @return   json串
     *
     * @since: 2017年1月18日 下午4:04:49
     * @author: lyx
     */
    public function checkUserExtend() {
        $where=I('post.');
        $user = M('UserExtend')->where($where)->select();
    
        if (!$user) {
            $valid = true;
        } elseif(count($user) == 1){
            $valid = $user[0]['user_no']==I('get.user_no') ? true : false;
        } else {
            $valid = false;
        }
        $result = array(
                'valid'    => $valid
        );
    
        $this->ajaxReturn($result);
    }
    
    /**
     * 验证报单中心是否存在
     *
     * @param    string     user_no     报单中心（会员编号）
     * @return   json串
     *
     * @since: 2017年1月19日 下午4:33:19
     * @author: lyx
     */
    public function checkServiceCenter()
    {
        $res =  D('ServiceCenter')->getServiceCenter(I('post.service_center_no'));
        $result = array(
                'valid'  => $res
        );
        $this->ajaxReturn($result);
    }
    
    /**
     * 检测安置人位置状态
     *
     * @param    string  parent_no   安置人编号
     * @param    string  curr_user   当前会员编号
     * @return   array
     *               status     是否可用
     *               location   空余位置
     *               message    失败提示
     *
     * @since: 2017年1月19日 下午5:42:12
     * @author: lyx
     */
    public function checkParentLocation() {
        
        $result = array(
                'status'    => false,
                'message'   => '安置人不存在'
        );
        
        $user = M('User')->field('path')->where(array("user_no"=>I('post.curr_user')))->find();
        $where = array(
                "user_no"    => I('post.parent_no')
        );
        $parent = M('User')->field('path,left_no,right_no')->where($where)->find();
        
        //判断是否允许跨区域操作，两个会员是否在同一区域内
        if ($user && $parent && ($this->sys_config['USER_CROSS_REGION'] == Constants::YES || strpos($parent['path'], $user['path']) !== false)) {
            
            if ($this->sys_config['SYSTEM_AUTO_SLIDE']) {
                $result = array(
                        'status'    => true,
                        'location'  => 'all'
                );
            } else {
                if (!$parent['left_no'] && !$parent['right_no']) {
                    $result = array(
                            'status'    => true,
                            'location'  => 'all'
                    );
                } elseif (!$parent['left_no']) {
                    $result = array(
                            'status'    => true,
                            'location'   => 'left'
                    );
                } elseif (!$parent['right_no']) {
                    $result = array(
                            'status'    => true,
                            'location'   => 'right'
                    );
                } else {
                    $result = array(
                            'status'    => false,
                            'message'   => '安置区域已满'
                    );
                }
            }
            
        }
        $this->ajaxReturn($result);
    }
}