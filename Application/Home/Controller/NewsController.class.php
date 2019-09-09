<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Conf\Constants;

/**
 * 公告模块管理控制器
 *
 * @since: 2017年1月23日 上午9:29:36
 * @author: lyx
 * @version: V1.0.0
 */
class NewsController extends HomeBaseController {

    /**
     * 系统公告列表
     *
     * @since: 2016年12月16日 下午2:26:10
     * @author: Wang Peng
     * 
     * @since: 2017年1月23日 上午9:30:02
     * @author: lyx
     */
    public function index(){
    
        $info = D('News')->selectList(I(),$this->sys_config['SYSTEM_PAGE_NUMBER']);
        //根据分类id查询分类标题
        foreach ($info['data'] as $key => $value) {
            $info['data'][$key]['categoty'] = D('NewsCategory')->field('title')->find($value['news_category_id']);
        }
    
        $this->assign('datainfo',$info['data']);
        $this->assign('page',$info['page']);
    
        $category = M('NewsCategory')->select();
        $this->assign('categoryinfo',$category);
    
        $this->assign('title',I('title'));
        $this->assign('news_category_id',I('news_category_id'));
        $this->assign('date_start',I('date_start'));
        $this->assign('date_end',I('date_end'));
    
        $this->display();
    }
    
    /**
     * 展示发布公告
     *
     * @since: 2016年12月14日 上午9:24:51
     * @author: Wang Peng
     */
    public function detail(){
        
        $news = D('News')->find(I('id'));
        $category = D('NewsCategory')->field('title')->find($news['news_category_id']);
        
        $this->assign('news',$news);
        $this->assign('category',$category);
        $this->display();
    }
}