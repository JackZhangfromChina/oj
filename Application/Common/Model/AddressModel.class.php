<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 收货地址 Model
 *
 * @since: 2016年12月13日 下午2:55:12
 * @author: lyx
 * @version: V1.0.0
 */
class AddressModel extends BaseModel{
    
    /**
	 * 地址管理
	 *
	 * @since: 2016年12月22日 上午9:50:10
	 * @author: Wang Peng
	 */
	public function selectList($datainfo,$flag,$page){

        //验证时间
        if(!empty($datainfo['date_start']) && !empty($datainfo['date_end'])){
             $info['add_time']=array(
                    array('EGT', $datainfo['date_start'] . ' 00:00:00'),
                    array('ELT', $datainfo['date_end'] . ' 23:59:59')
             );
        }elseif (!empty($datainfo['date_start'])) {
             $info['add_time'] = array('EGT', $datainfo['date_start'] . ' 00:00:00');
        }elseif (!empty($datainfo['date_end'])) {
             $info['add_time'] = array('ELT', $datainfo['date_end'] . ' 23:59:59');
        }
        //验证商品名称
        if(!empty($datainfo['user_no']))
        {
            $info['user_no'] = array('like','%' . $datainfo['user_no'] . '%');
        }
         
         if(empty($flag)){
             $info['user_no'] = session('user.user_no');
         }

        $data = $this->getPage($this,$info,'',$order='id desc',$page,$field='');
        return $data;
    }
    
    /**
     * 订单页地址信息
     *
     * @since: 2016年12月22日 上午10:51:22
     * @author: Wang Peng
     */
    public function addrsList($data){

        $data = $this->where(array('user_no'=>$data['user_no']))->order('is_default desc,id desc')->select();
        return $data;
    }

    //定义自动验证
    protected $_validate = array(
        array('receiver', 'require', '收货人不能为空', self::MODEL_BOTH),
        array('phone', 'require', '手机号不能为空', self::MODEL_BOTH),
        array('province', 'require', '省份不能为空', self::MODEL_BOTH),
        array('city', 'require', '市级不能为空', self::MODEL_BOTH),
        array('zone', 'require', '区级不能为空', self::MODEL_BOTH),
        array('address', 'require', '详细地址不能为空', self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
        array('add_time','curr_time',self::MODEL_INSERT,'callback'),
        array('update_time','curr_time',self::MODEL_BOTH,'callback'),
    );
    
}