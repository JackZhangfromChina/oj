<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 新闻 Model
 *
 * @since: 2016年12月13日 下午3:13:33
 * @author: lyx
 * @version: V1.0.0
 */
class NewsModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('title', 'require', '标题不能为空', self::MODEL_BOTH),
            array('title', 'checklen', '标题必须在2~50个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,50)),
            array('content', 'require', '内容不能为空', self::MODEL_BOTH),
            array('news_category_id', 'require', '分类不能为空', self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
            array('status',Constants::NORMAL,self::MODEL_INSERT),  // 新增时候status字段设置为1
            array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
    /**
	 * 新闻 Model
	 *
	 * @since: 2016年12月16日 下午2:54:10
	 * @author: Wang Peng
	 */
    public function selectList($datainfo,$page){
        //验证商品分类
       	if ($datainfo['news_category_id'] > Constants::NO) {
			$info['news_category_id'] = $datainfo['news_category_id'];
		}
        //验证时间
		if (!empty($datainfo['date_start']) && !empty($datainfo['date_end'])) {
             $info['add_time']=array(
                    array('EGT', $datainfo['date_start'] . ' 00:00:00'),
                    array('ELT', $datainfo['date_end'] . ' 23:59:59')
             );
		} elseif (!empty($datainfo['date_start'])) {
			 $info['add_time'] = array('EGT', $datainfo['date_start'] . ' 00:00:00');
		} elseif (!empty($datainfo['date_end'])) {
			 $info['add_time'] = array('ELT', $datainfo['date_end'] . ' 23:59:59');
		}
        //验证商品名称
		if (!empty($datainfo['title'])) {
			$info['title'] = array('like','%' . $datainfo['title'] . '%');
		}
		
        //分页
        $data = $this->getPage($this,$info,$datainfo,$order='id desc',$page,$field='');
        return $data;
    }
}