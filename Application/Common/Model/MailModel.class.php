<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 站内信 Model
 *
 * @since: 2016年12月13日 下午3:10:48
 * @author: lyx
 * @version: V1.0.0
 */
class MailModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('title', 'require', '邮件标题不能为空', self::MODEL_BOTH),
            array('title', 'checklen', '邮件标题必须在2~50个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,50)),
            array('content', 'require', '邮件内容不能为空', self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
            
            array('parent_id', Constants::NO,self::MODEL_INSERT),  // 新增时候parent_id字段设置为0
            array('is_read',Constants::NO,self::MODEL_INSERT),  // 新增时候is_read字段设置为0
            array('is_sender_delete',Constants::NO,self::MODEL_INSERT),  // 新增时候is_sender_delete字段设置为0
            array('is_receiver_delete',Constants::NO,self::MODEL_INSERT),  // 新增时候is_receiver_delete字段设置为0
            array('send_time','curr_time',self::MODEL_INSERT,'callback'), // 对send_time字段在新增时写入当前时间
    );
    
    /**
     * 站内信列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *
     * @since: 2017年1月7日 下午3:43:30
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
    
        return $data;
    }
}