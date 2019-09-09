<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 银行 Model
 *
 * @since: 2017年8月18日 下午2:26:03
 * @author: lyx
 * @version: V1.0.0
 */
class BankTypeModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('title', 'require', '银行名称不能为空', self::MODEL_BOTH),
            array('title', 'checklen', '银行名称必须在2~20个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('title', 'checkUnique','银行名称已经存在！',self::MUST_VALIDATE,'callback',self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
            array('sort',Constants::NO,self::MODEL_INSERT),  // 新增时候sort字段设置为0
            array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );

    /**
     * 银行列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *
     * @since: 2017年8月18日 下午2:27:34
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number)
    {
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='sort asc,id asc', $page_number);
        
        return $data;
    }
	
	/**
	 * 验证银行名称唯一性
	 *
	 * @return   bool    验证通过或失败
	 *
	 * @since: 2017年8月18日 下午2:27:56
	 * @author: lyx
	 */
	protected function checkUnique(){
	    $item = $this->getByTitle(I('title'));
	    if($item){
	        return $item['id'] == I('id') ? true : false;
	    }else{
	        return true;
	    }
	}
}