<?php
namespace Admin\Controller;
use Common\Controller\PublicBaseController;
use Common\Conf\Constants;

/**
 * 验证管理控制器
 *
 * @since: 2016年12月27日 下午2:55:56
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
    * @since: 2016年12月27日 下午3:22:23
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
                $isAvailable = I('phone') ? D('User')->checkExist('phone', I('phone'), I('id')) : false;
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
     * 根据会员编号验证会员信息是否存在，并且返回会员真实姓名
     *
     * @param    string  user_no     会员编号
     * @return   json串
     *
     * @since: 2016年12月28日 下午2:19:05
     * @author: lyx
     */
    public function existUserAndReturn() {
        $result = array(
                'status'  => false,
                'realname' => ""
        );

        //获取会员信息
        $user = M('User')->getByUserNo(I('user_no'));
        if (I('user_no') && $user) {
            $result = array(
                'status'  => true,
                'realname' => $user['realname']
            );
            if (isset($_POST['account_type'])) {

                switch ($_POST['account_type']) {
                    case Constants::ACCOUNT_TYPE_EB:
                        $field = 'eb_account';
                        break;
                    case Constants::ACCOUNT_TYPE_TB:
                        $field = 'tb_account';
                        break;
                    case Constants::ACCOUNT_TYPE_CB:
                        $field = 'cb_account';
                        break;
                    case Constants::ACCOUNT_TYPE_MB:
                        $field = 'mb_account';
                        break;
                    case Constants::ACCOUNT_TYPE_RB:
                        $field = 'rb_account';
                        break;
                }

                $result['balance'] = $user[$field];
            }
        }

        $this->ajaxReturn($result);
    }

    /**
     * 验证管理员是否存在
     *
     * @param    int        id          管理员id
     * @param    string     username    管理员用户名
     * @return   json串
     *
     * @since: 2017年1月4日 下午5:35:09
     * @author: lyx
     */
    public function checkUsername() {
        $where = array(
                'username'   => I('post.username')
        );
        $admin = M('Admin')->where($where)->find();

        if (!$admin) {
            $isExist = true;
        } else {
            $isExist = $admin['id']==I('post.id') ? true : false;
        }
        $result = array(
                'status'    => $isExist
        );

        $this->ajaxReturn($result);
    }

    /**
     * 验证角色是否存在
     *
     * @param    int        id          角色id
     * @param    string     title       角色名称
     * @return   json串
     *
     * @since: 2017年1月5日 下午5:36:30
     * @author: lyx
     */
    public function checkGroupTitle() {
        $where = array(
                'title'   => I('post.title')
        );
        $group = M('AuthGroup')->where($where)->find();

        if (!$group) {
            $isExist = true;
        } else {
            $isExist = $group['id']==I('post.id') ? true : false;
        }
        $result = array(
                'status'    => $isExist
        );

        $this->ajaxReturn($result);
    }

    /**
     * 验证规则是否存在
     *
     * @param    int        id          规则id
     * @param    string     name       规则action
     * @return   json串
     *
     * @since: 2017年1月7日 上午10:40:35
     * @author: lyx
     */
    public function checkRule() {
        $where = array(
                'name'   => I('post.name')
        );
        $rule = M('AuthRule')->where($where)->find();

        if (!$rule) {
            $isExist = true;
        } else {
            $isExist = $rule['id']==I('post.id') ? true : false;
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
     * 验证库存损耗是否大于当前库存
     *
     * @param    int changenum    变更数量
     * @param    int changetype    变更类型
     * @return   json串
     *
     * @since: 2017年1月24日 下午3:14:11
     * @author: Wang Peng
     */
    public function checkInventorynum(){

        $changenum = trim(I('changenum'));
        $changetype = I('changetype');

        $where = array(
                'id' => I('changeid'),
        );

        $inventory_number = M('Goods')->where($where)->getField('inventory_number');

        if($changetype == 1){
            if($inventory_number - $changenum < 0){
                $result = array(
                    'valid'    => false
                );

                $this->ajaxReturn($result);
            }
        }
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
}