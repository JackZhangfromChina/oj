<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 支付订单 Model
 *
 * @since: 2016年12月13日 下午3:15:21
 * @author: lyx
 * @version: V1.0.0
 */
class PayModel extends BaseModel{

    /**
     * 支付记录
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *                   statistics 统计对象
     *
     * @since: 2017年1月13日 下午4:29:32
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);

        //统计数据
        $where['status'] = Constants::PAY_STATUS_SUCCESS;
        $data['statistics'] = $this->where($where)
                                    ->field('SUM(amount) as sum_total')
                                    ->find();

        return $data;
    }

    public function orderCancel($order_no, &$msg)
    {
        if(empty($order_no)){
            $msg = '订单不存在';
            return false;
        }
        // 系统内查询
        $order = $this->getByOrderNo($order_no);
        if(empty($order)){
            $msg = '订单不存在';
            return false;
        }else if($order['status'] == Constants::PAY_STATUS_SUCCESS){
            $msg = '订单已支付完成！';
            return false;
        }else if($order['status'] == Constants::PAY_STATUS_CLOSE){
            $msg = '订单已取消';
            return false;
        }

        $payData = array(
            'status'=>2
        );
        $this->where(array('order_no'=>$order['order_no']))->save($payData);

        $msg = '订单已取消';
        return true;
    }
}