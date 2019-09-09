<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 配置 Model
 *
 * @since: 2016年12月13日 下午3:02:19
 * @author: lyx
 * @version: V1.0.0
 */
class ConfigModel extends BaseModel{

    /**
    * 根据配置分组名称获取配置信息
    *
    * @param    string  $group_name     配置分组名称
    * @return   array   配置信息
    *
    * @since: 2016年12月20日 上午11:51:28
    * @author: lyx
    */
    public function getByGroupName($group_name){
        $where = array(
                'cg.title'  => $group_name,
                'is_can_update'  => Constants::YES
        );
        $configs =  M('Config')->alias('c')
                        ->field('c.code,c.title,c.value')
                        ->join(C('DB_PREFIX').'config_group AS cg ON c.config_group_id = cg.id')
                        ->where($where)
                        ->order('c.id asc')
                        ->select();
        foreach ($configs as $config) {
            $new_configs[$config['code']] = $config;
        }
        
        return $new_configs;
    }
}