<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 管理员 Model
 *
 * @since: 2016年12月12日 下午1:50:09
 * @author: lyx
 * @version: V1.0.0
 */
class AdminModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('realname', 'require', '管理员真实姓名必须填写', self::MUST_VALIDATE),
            array('realname', 'checklen', '真实姓名必须在2~20个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('nickname', 'checklen', '昵称必须在2~20个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('phone', 'checklen', '手机号必须在4~20个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(4,20)),
            array('email', 'checklen', 'email必须在6~50个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(6,50)),
    );
    
    //自动完成
    protected $_auto = array (
            array('status',Constants::NORMAL,self::MODEL_INSERT),  // 新增时候status字段设置为1
            array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
    /**
     * 管理员列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *
     * @since: 2017年1月4日 下午3:25:00
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id asc', $page_number);
        
        foreach ($data['data'] as $key => $admin) {
            if ($admin['is_super'] == Constants::NO) {
                //获取分组信息信息
                $access = M('AuthGroupAccess') 
                        ->field('title')
                        ->alias('aga')
                        ->join('__AUTH_GROUP__ ag ON aga.group_id=ag.id','LEFT')
                        ->where(array('uid'=>$admin['id']))
                        ->select();
                $title_arr = array_column($access, 'title');
                
                $data['data'][$key]['title'] = implode('、', $title_arr);
            } else {
                $data['data'][$key]['title'] = "超级管理员";
            }
        }
        return $data;
    }
}