<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 商品分类 Model
 *
 * @since: 2016年12月13日 下午3:06:05
 * @author: lyx
 * @version: V1.0.0
 */
class GoodsCategoryModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('title', 'require', '商品分类名称不能为空', self::MODEL_BOTH),
            array('title', 'checklen', '商品分类名称必须在2~20个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('title', 'checkUnique','商品分类名称已经存在！',self::MUST_VALIDATE,'callback',self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
            array('sort',Constants::NO,self::MODEL_INSERT),  // 新增时候sort字段设置为0
            array('status',Constants::NORMAL,self::MODEL_INSERT),  // 新增时候status字段设置为1
            array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );

    /**
     * 商品分类列表
     * @param  [type] $where       [description]
     * @param  [type] $parameter   [description]
     * @param  [type] $page_number [description]
     * @return [type]              [description]
     */
    public function getList($where,$parameter,$page_number)
    {
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='sort asc,id asc', $page_number);
        
        return $data;
    }
	
	/**
	 * 验证分类名称唯一性
	 *
	 * @return   bool    验证通过或失败
	 *
	 * @since: 2017年1月23日 上午11:00:01
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