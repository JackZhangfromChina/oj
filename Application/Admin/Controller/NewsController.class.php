<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 公告模块管理控制器
 *
 * @since: 2016年12月14日 上午9:24:51
 * @author: lyx
 * @version: V1.0.0
 */
class NewsController extends AdminBaseController {

    /**
     * 公告列表
     *
     * @since: 2016年12月16日 下午2:26:10
     * @author: Wang Peng
     */
    public function index(){
    
        $news = D('News');
        $newscategory = D('NewsCategory');
    
        $info = $news->selectList(I(),$this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        foreach ($info['data'] as $key => $value) {
            $info['data'][$key]['categoty'] = $newscategory->field('title')->find($value['news_category_id']);
        }
    
        $this->assign('datainfo',$info['data']);
        $this->assign('page',$info['page']);
    
        $categorys = M('NewsCategory')->select();
        $this->assign('categorys',$categorys);
    
        $this->assign('title',I('title'));
        $this->assign('news_category_id',I('news_category_id'));
        $this->assign('date_start',I('date_start'));
        $this->assign('date_end',I('date_end'));
    
        $this->display();
    }
	
	/**
	 * 添加公告页面（包括添加操作）
	 *
	 * @since: 2017年1月22日 上午10:18:17
	 * @author: lyx
	 */
	public function add() {
	
	    if (IS_POST) { //数据提交
	        $News = D("News"); // 实例化对象
	        
	        //获取公告分类信息
	        $category  = M('NewsCategory')->field('id')->find(I('post.news_category_id'));
	        //验证分类是否存在
	        if (!$category) {
	            $result = array(
	                    'status'  => false,
	                    'message' => '公告分类不存在，操作失败！'
	            );
	            $this->ajaxReturn($result);
	        }
	        
	        if (!$News->create()) {
	            $result = array(
	                    'status'  => false,
	                    'message' => $News->getError()
	            );
	        } else {
	            $News->content = $_POST['content'];
	            $res = $News->add(); // 写入数据到数据库
	        
	            if ($res !== false) {
	                $result = array(
	                        'status'  => true,
	                        'message' => '添加公告成功'
	                );
	                //操作日志
	                $this->addLog('添加公告。公告标题为：' . I('post.title'), $res);
	            } else {
	                $result = array(
	                        'status'  => false,
	                        'message' => '添加公告失败！'
	                );
	            }
	        }
	        $this->ajaxReturn($result);
	    }
	
        //获取分类公告信息
		$where = array(
		        'status'  => Constants::NORMAL
		);
		$categorys = M('NewsCategory')
            		->where($where)
            		->order('id asc')
            		->select();
		
	    if($categorys){
	        $this->assign('categorys',$categorys);
	        $this->display();
	    } else {
	        $this->redirect('Index/unAuth', array('tips'=>'系统中没有可选的公告分类，请先添加公告分类后再发布公告！'));
	    }
	}
	
	/**
	 * 修改公告页面（包括修改操作）
	 *
	 * @since: 2017年1月22日 下午1:24:44
	 * @author: lyx
	 */
	public function edit() {
	
	    if (IS_POST) { //数据提交
	        $News = D("News"); // 实例化对象
	         
	        //获取公告分类信息
	        $category  = M('NewsCategory')->field('id')->find(I('post.news_category_id'));
	        
	        //验证分类是否存在
	        if (!$category) {
	            $result = array(
	                    'status'  => false,
	                    'message' => '公告分类不存在，操作失败！'
	            );
	            $this->ajaxReturn($result);
	        }
	        
	        $old_news = M('News')->find(I('post.id'));
	        if (!$News->create()) {
	            $result = array(
	                    'status'  => false,
	                    'message' => $News->getError()
	            );
	        } else {
	            $News->content = $_POST['content'];
	            $res = $News->save(); // 写入数据到数据库
	             
	            if ($res !== false) {
	                $result = array(
	                        'status'  => true,
	                        'message' => '修改公告成功'
	                );
	                //操作日志
	                $this->addLog('修改标题为' . $old_news['title'] . '的公告。修改后的标题为' . I('post.title') . '。', I('post.id'));
	            } else {
	                $result = array(
	                        'status'  => false,
	                        'message' => '修改公告失败！'
	                );
	            }
	        }
	        $this->ajaxReturn($result);
	    }
	
	    //获取分类公告信息
	    $where = array(
	            'status'  => Constants::NORMAL
	    );
	    $categorys = M('NewsCategory')
        	    ->where($where)
        	    ->order('id asc')
        	    ->select();
	    $news = M("News")->find(I('get.id'));
	    
	    $this->assign('news',$news);
	    $this->assign('categorys',$categorys);
	    $this->display();
	}
	
   /**
    * 公告分类管理
    *
    * @since: 2016年12月15日 上午9:50:51
    * @author: Wang Peng
    * 
    * @since: 2017年1月21日 下午4:59:25
    * @updater: lyx
    */
	public function categoryList(){
	    //获取分类公告信息
	    $where['status'] = Constants::NORMAL;
		$info = D('NewsCategory')->selectList($where, $where, $this->sys_config['SYSTEM_PAGE_NUMBER']);

		$this->assign('list', $info['data']);
		$this->assign('page', $info['page']);
        $this->display();
	}

    /**
    * 添加公告分类
    *
    * @since: 2017年1月21日 下午6:19:03
    * @author: lyx
    */
    public function addCategory(){
        if (IS_POST) { //数据提交
            $NewsCategory = D("NewsCategory"); // 实例化对象
            if (!$NewsCategory->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $NewsCategory->getError()
                );
            } else {
                $res = $NewsCategory->add(); // 写入数据到数据库
        
                if ($res !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '添加公告分类成功'
                    );
                    //操作日志
                    $this->addLog('添加公告分类。分类名称为：' . I('post.title'), $res);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '添加公告分类失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    }

    /**
    * 修改公告分类
    *
    * @since: 2017年1月21日 下午6:19:39
    * @author: lyx
    */
    public function editCategory(){
        if (IS_POST) { //数据提交
            $NewsCategory = D("NewsCategory"); // 实例化对象
            if (!$NewsCategory->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $NewsCategory->getError()
                );
            } else {
                $category = M('NewsCategory')->find(I('post.id'));
                $res = $NewsCategory->save(); // 写入数据到数据库
        
                if ($res !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '修改公告分类成功'
                    );
                    //操作日志
                    $this->addLog('修改名称为' . $category['title'] . '的公告分类。修改后的名称为' . I('post.title') . '。', I('post.id'));
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '修改公告分类失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 删除公告分类
     *
     * @since: 2017年1月21日 下午6:35:57
     * @author: lyx
     */
    public function deleteCategory() {
        if (IS_POST) { //数据提交
    
            //查询分类下是否有公告
            $count = M('News')
                        ->where(array('news_category_id'  => I('post.id')))
                        ->count('id');
    
            if ($count > 0) {
                $result = array(
                        'status'  => false,
                        'message' => '分类下存在公告，不能删除！'
                );
                $this->ajaxReturn($result);
            }
    
            $category = M('NewsCategory')->find(I('post.id'));
            
            //删除
            $res = M("NewsCategory")->delete(I('post.id'));
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                //操作日志
                $this->addLog('删除名称为' . $category['title'] . '的公告分类。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '删除失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }
    
    /**
     * 删除公告
     *
     * @since: 2017年1月21日 下午6:50:08
     * @author: lyx
     */
    public function del() {
        if (IS_POST) { //数据提交
    
            $news = M('News')->find(I('post.id'));
    
            //删除
            $res = M("News")->delete(I('post.id'));
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                //操作日志
                $this->addLog('删除标题为' . $news['title'] . '的公告。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '删除失败！'
                );
            }
            $this->ajaxReturn($result);
        }
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