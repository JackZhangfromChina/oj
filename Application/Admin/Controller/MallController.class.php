<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 商城模块管理控制器
 */
class MallController extends AdminBaseController
{
    /**
     * 初始化
     * @return [type] [description]
     */
    public function _initialize()
    {
        parent::_initialize();
    
        //系统是否开启商城，没有开启商城不能进行任何操作
        if(!$this->sys_config['SYSTEM_OPEN_MALL']){
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启商城功能，不能进行操作！'));
        }
    }

    /**
     * 商城管理欢迎页
     * @return [type] [description]
     */
    public function index()
    {
        $this->display();
    }

    // -------------------- 商品分类 begin --------------------
    /**
     * 商品分类管理
     * @return [type] [description]
     */
    public function categoryList()
    {
        $cates = D('GoodsCategory')->getList(Constants::YES, array(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
        
        $this->assign('list', $cates['data']);
        $this->assign('page', $cates['page']); 
        $this->display();
    }

    /**
     * 添加商品分类
     * @return [type] [description]
     */
    public function addCategory()
    {
        if (IS_POST) { //数据提交
            $GoodsCategory = D("GoodsCategory"); // 实例化对象
            if (!$GoodsCategory->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $GoodsCategory->getError()
                );
            } else {
                $res = $GoodsCategory->add(); // 写入数据到数据库
    
                if ($res !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '添加商品分类成功'
                    );
                    //操作日志
                    $this->addLog('添加商品分类。分类名称为：' . I('post.title'), $res);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '添加商品分类失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 修改商品分类
     * @return [type] [description]
     */
    public function editCategory()
    {
        if (IS_POST) { //数据提交
            $GoodsCategory = D("GoodsCategory"); // 实例化对象
            if (!$GoodsCategory->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $GoodsCategory->getError()
                );
            } else {
                $category = M('GoodsCategory')->find(I('post.id'));
                $res = $GoodsCategory->save(); // 写入数据到数据库
    
                if ($res !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '修改商品分类成功'
                    );
                    //操作日志
                    $this->addLog('修改名称为' . $category['title'] . '的商品分类。修改后的名称为' . I('post.title') . '。', I('post.id'));
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '修改商品分类失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 删除商品分类
     * @return [type] [description]
     */
    public function deleteCategory()
    {
        if (IS_POST) { //数据提交
    
            //查询分类下是否有商品
            $count = M('Goods')->where(array('goods_category_id'=>I('post.id')))->count('id');
            if ($count > 0) {
                $result = array(
                        'status'  => false,
                        'message' => '分类下存在商品，不能删除！'
                );
                $this->ajaxReturn($result);
            }
    
            $category = M('GoodsCategory')->find(I('post.id'));
    
            //删除
            $res = M("GoodsCategory")->delete(I('post.id'));
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                //操作日志
                $this->addLog('删除名称为' . $category['title'] . '的商品分类。', I('post.id'));
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
     * 保存商品分类状态
     * @return [type] [description]
     */
    public function setCategoryStatus()
    {
        if (IS_POST) { //数据提交
            $op_name = I('post.status') ? '开启' : '关闭';
            $category = M("GoodsCategory")->field('title')->find(I('post.id'));
    
            //保存设置
            $where = array(
                    'id'  => I('post.id')
            );
            $data = array(
                    'status'        => I('post.status') ? Constants::YES : Constants::NO,
                    'update_time'   => curr_time()
            );
            $re = M("GoodsCategory")->where($where)->save($data);
    
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => $op_name . '成功！'
                );
    
                //操作日志
                $this->addLog($op_name . $category['title'] . '商品分类。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => $op_name . '失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 商品分类排序
     * @return [type] [description]
     */
    public function sort()
    {
        if (IS_POST) { //数据提交
            $ids = I('post.ids');
            $rs = D('GoodsCategory')->getSort('GoodsCategory', 'id', $ids);
            
            if($rs===false){
                $result = array(
                        'status'  => false,
                        'message' => '排序失败！'
                );
            }else{
                $result = array(
                        'status'  => true,
                        'message' => '排序成功！'
                );
                //操作日志
                $this->addLog('对商品分类进行了排序');
            }
            $this->ajaxReturn($result);
        }
    }
    // -------------------- 商品分类 end --------------------
    
    // -------------------- 商品信息 begin --------------------
    /**
     * 商品列表页面
     * @return [type] [description]
     */
    public function goodsList()
    {
        $status = I('status');
        $keyword = I('keyword');
        $goods_category_id = I('goods_category_id');
    
        //验证商品分类
        if ($goods_category_id) {
            $where['goods_category_id'] = $goods_category_id;
        }
        //商品状态
        if ((isset($_GET["status"]) || isset($_POST["status"])) && $status != '-1') {
            $where['status'] = $status;
        }
        //关键词
        if ($keyword) {
            $where['title'] = array('like', '%' . $keyword . '%') ;
        }
    
        //查询数据
        $goods_list = D('Goods')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
        $GoodsCategory = D('GoodsCategory');
        foreach ($goods_list['data'] as $key => $goods) {
            if ($goods['pic_url']) {
                $goods_list['data'][$key]['file'] = $this->sys_config['WEB_DOMAIN'] . '/'. $goods['pic_url'];//传入商品图片信息
            } else {
                $goods_list['data'][$key]['file'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
            }
            
            $goods_list['data'][$key]['categoty'] = $GoodsCategory->field('title')->find($goods['goods_category_id']);
        }
        
        //获取商品分类
        $categorys = M('GoodsCategory')->select();
        
        //返回页面的数据
        $this->assign('list', $goods_list['data']);
        $this->assign('page', $goods_list['page']);
        $this->assign('status', $status);
        $this->assign('goods_category_id', $goods_category_id);
        $this->assign('keyword', $keyword);
        $this->assign('categorys',$categorys);
        $this->assign('currency_symbol',$this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }

    /**
     * 保存商品状态
     */
    public function setGoodsStatus() {
        if (IS_POST) { //数据提交
            $op_name = I('post.status') ? '上架' : '下架';
            $goods = M("Goods")->field('title')->find(I('post.id'));
    
            //保存设置
            $where = array(
                    'id'  => I('post.id')
            );
            $data = array(
                    'status'        => I('post.status') ? Constants::YES : Constants::NO,
                    'update_time'   => curr_time()
            );
            $re = M("Goods")->where($where)->save($data);
    
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => $op_name . '成功！'
                );
    
                //操作日志
                $this->addLog($op_name . $goods['title'] . '商品。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => $op_name . '失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 变更库存
     * @return [type] [description]
     */
    public function changesInventory(){
        if(IS_POST){
            //获取商品信息
            $where = array(
                    'id'   => I('post.goods_id')
            );
            $goods = M('Goods')->field('inventory_number')->where($where)->find();
            //验证库存损耗
            if (I('post.type') == Constants::GOODS_STOCK_DECLINE && ($goods['inventory_number']<I('post.quantity'))) {
                $result = array(
                        'status'  => false,
                        'message' => '库存损耗大于当前库存，操作失败！'
                );
                $this->ajaxReturn($result);
            }
            
            $GoodsInventory = D("GoodsInventory"); // 实例化对象
            //自动验证
            if (!$GoodsInventory->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $GoodsInventory->getError()
                );
                $this->ajaxReturn($result);
            }
            
            $data = I('post.');
            $res_add = $GoodsInventory->add($data);
            //根据变更类型进行商品数据处理
            if (I('post.type') == Constants::GOODS_STOCK_DECLINE) {
                $goods_data['inventory_number'] = array('exp', 'inventory_number - ' . $data['quantity']);
                $change = '库存损耗';
            } else {
                $goods_data['inventory_number'] = array('exp', 'inventory_number + ' . $data['quantity']);
                $goods_data['total_number'] = array('exp', 'total_number + ' . $data['quantity']);
                $change = '追加库存';
            }
            $res_goods = M('Goods')->where(array('id'=>I('post.goods_id')))->save($goods_data);

            if ($res_goods !== false && $res_add !== false) {
                //操作日志
                $this->addLog('商品'.$change.'操作成功。', I('changeid'));
            
                $result = array(
                        'status'  => true,
                        'message' => '商品'.$change.'操作成功。'
                );
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '商品'.$change.'操作失败。'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $goods = M("Goods")->find(I('get.id'));
        $this->assign('goods',$goods);
        $this->display();
    }

    /**
     * 添加商品页面
     */
    public function addGoods()
    {
        if(IS_POST){
            $Goods = D('Goods');
            if (!$Goods->create()){
                $result = array(
                        'status'  => false,
                        'message' => $Goods->getError()
                );
            }else{
                $GoodsExtend = D('GoodsExtend');
                if (!$GoodsExtend->create()){
                    $result = array(
                            'status'  => false,
                            'message' => $GoodsExtend->getError()
                    );
                    $this->ajaxReturn($result);
                }
                
                $wh = explode('x', $_POST['filedim']);
                //验证上传截图
                if($wh[0]/$wh[1] != 1 && (!I('post.w') || !I('post.h'))){
                    $result = array(
                            'status'  => false,
                            'message' => '上传图片像素不是1:1,请截图后再上传'
                    );
                    $this->ajaxReturn($result);
                }
                
                //调用上传图片
                $upload_res = $this->_upload(100, 100, 1000, 1000);
                if (!$upload_res['status']){
                    $result = array(
                            'status'    => false,
                            'message'   => '商品展图上传失败。失败原因：' . $upload_res['message']
                    );
                    $this->ajaxReturn($result);
                }
                
                //保存商品信息
                $Goods->pic_url = $upload_res['pic_url'];
                $Goods->inventory_number = I('post.total_number');
                $goods_id = $Goods->add();
                if ($goods_id === false) {
                    $result = array(
                            'status'    => false,
                            'message'   => '商品添加失败'
                    );
                    $this->ajaxReturn($result);
                }
                
                //保存商品扩展信息
                $GoodsExtend->big_pic_url = $upload_res['big_pic_url'];
                $GoodsExtend->goods_id = $goods_id;
                $GoodsExtend->detail = $_POST['detail'];
                $res_goods_extend = $GoodsExtend->add();
                
                //初始化商品入库
                $inventory = array(
                        'goods_id'      => $goods_id,
                        'type'          => Constants::GOODS_STOCK_ADD,
                        'quantity'      => I('post.total_number'),
                        'remark'        => '初始商品入库',
                        'update_time'   => curr_time()
                );
                $res_inventory = D('GoodsInventory')->add($inventory);
                 
                if($res_goods_extend !==false && $res_inventory !== false){
                    $result = array(
                            'status'    => true,
                            'message'   => '添加商品成功'
                    );
                    //操作日志
                    $this->addLog('添加商品信息成功。', $goods_id);
                } else {
                    $result = array(
                            'status'    => false,
                            'message'   => '商品添加失败'
                    );
                }
                $this->ajaxReturn($result);
            }
        }
        //获取商品分类信息
        $categorys = M('GoodsCategory')->field('id,title')->where(array('status'=>Constants::NORMAL))->select();
        if(!$categorys){
            //没有权限
            $this->redirect('Index/unAuth', array('tips'=>'商品分类不能为空，请到商品分类中添加！'));
        }
        $this->assign('categorys',$categorys);
        $this->display();
    }

    /**
     * 修改商品页面
     * @return [type] [description]
     */
    public function editGoods()
    {
        if(IS_POST){
            $Goods = D('Goods');
            if (!$Goods->create()){
                $result = array(
                        'status'  => false,
                        'message' => $Goods->getError()
                );
                $this->ajaxReturn($result);
            }else{
                $GoodsExtend = D('GoodsExtend');
                if (!$GoodsExtend->create()){
                    $result = array(
                            'status'  => false,
                            'message' => $GoodsExtend->getError()
                    );
                    $this->ajaxReturn($result);
                }
            
                //有文件上传
                if(!empty($_FILES['image_file'])){
                    $wh = explode('x', $_POST['filedim']);
                    //验证上传截图
                    if($wh[0]/$wh[1] != 1 && (!I('post.w') || !I('post.h'))){
                        $result = array(
                                'status'  => false,
                                'message' => '上传图片像素不是1:1,请截图后再上传'
                        );
                        $this->ajaxReturn($result);
                    }
                    //调用上传图片
                    $upload_res = $this->_upload(100, 100, 1000, 1000);
                    
                    if (!$upload_res['status']){
                        $result = array(
                                'status'    => false,
                                'message'   => '商品展图上传失败。失败原因：' . $upload_res['message']
                        );
                        $this->ajaxReturn($result);
                    }
                    //修改商品图片信息
                    $Goods->pic_url = $upload_res['pic_url'];
                    $GoodsExtend->big_pic_url = $upload_res['big_pic_url'];
                }

                //保存商品信息
                $res_goods = $Goods->save();
                //保存商品扩展信息
                $GoodsExtend->goods_id = I('post.id');
                $GoodsExtend->detail = $_POST['detail'];
                $res_goods_extend = $GoodsExtend->save();
            
                if($res_goods !== false && $res_goods_extend !== false){
                    $result = array(
                            'status'    => true,
                            'message'   => '修改商品成功'
                    );
                    //操作日志
                    $this->addLog('修改商品信息成功。', I('post.id'));
                } else {
                    $result = array(
                            'status'    => false,
                            'message'   => '商品修改失败'
                    );
                }
                $this->ajaxReturn($result);
            }

            $result = array(
                    'status'  => true,
                    'message' => '修改成功'
            );
            $this->ajaxReturn($result);
        }
        //获取商品分类信息
        $categorys = M('GoodsCategory')->field('id,title')->where(array('status'=>Constants::NORMAL))->select();
        if(!$categorys){
            //没有权限
            $this->redirect('Index/unAuth', array('tips'=>'商品分类不能为空，请到商品分类中添加！'));
        }
        //获取商品信息
        $goods = M('Goods')->where(array('id'=>I('id')))->find();
        $goods['pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/' . $goods['pic_url'];
        $goods_extent  =  M('GoodsExtend')->field('detail')->where(array('goods_id'=>I('id')))->find();
        $goods['detail'] = $goods_extent['detail'];
        $this->assign('goods',$goods);
        $this->assign('categorys',$categorys);
        $this->display();
    }

    /**
     * 商品详情页面
     * @return [type] [description]
     */
    public function detail()
    {
        $goods = M('Goods')->where(array('id'=>I('id')))->find();
        $goods_extent  =  M('GoodsExtend')->field('detail,big_pic_url')->where(array('goods_id'=>I('id')))->find();
        $goods_category = M('GoodsCategory')->field('title')->find($goods['goods_category_id']);
    
        $goods['detail'] = $goods_extent['detail'];
        if ($goods_extent['big_pic_url']) {
            $goods['big_pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/'. $goods_extent['big_pic_url'];//传入商品图片信息
        } else {
            $goods['big_pic_url'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
        }
        $goods['categoty'] = $goods_category['title'];
    
        $this->assign('currency_symbol',$this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->assign('goods', $goods);
        $this->display();
    }
    // -------------------- 商品信息 end --------------------

    // -------------------- 订单信息 begin --------------------
    /**
     * 订单列表页面
     * @return [type] [description]
     */
    public function orderList()
    {
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = trim(I('keyword'));
    
        //订单状态
        if ((isset($_GET["status"]) || isset($_POST["status"])) && $status != '-1') {
            $where['status'] = $status;
        }
        //时间
        if ($start_date && $end_date) {
            $where['add_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['add_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['add_time'] = array('ELT', $end_date . ' 23:59:59');
        }
        //关键词
        if ($keyword) {
            $map['user_no'] = array('like', '%' . $keyword . '%') ;
            $map['order_no'] = array('like', '%' . $keyword . '%') ;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
    
        //查询数据
        $orders = D('Order')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
        foreach ($orders['data'] as $key1 => $order) {
            //获取订单商品信息
            $where = array(
                    'aog.order_id'  => $order['id']
            );
            $order_goods = D('OrderGoods')->getList($where);
            //封装图片信息
            foreach ($order_goods as $key2 => $goods) {
                //获取图片信息
                if ($goods['pic_url']) {
                    $order_goods[$key2]['pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/'. $goods['pic_url'];
                } else {
                    $order_goods[$key2]['pic_url'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
                }
            }
            $orders['data'][$key1]['goods'] = $order_goods;
        }
    
        //返回页面的数据
        $this->assign('list', $orders['data']);
        $this->assign('page', $orders['page']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     * 订单详细页面
     * @return [type] [description]
     */
    public function orderDetail()
    {
        //通过订单号查询订单信息
        $order = D('Order')->where(array('order_no'=>I('order_no')))->find();
    
        //获取订单商品信息
        $where = array(
                'aog.order_id'  => $order['id']
        );
        $order_goods = D('OrderGoods')->getList($where);
        foreach ($order_goods as $key => $goods) {
            //获取图片信息
            if ($goods['pic_url']) {
                $order_goods[$key]['pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/'. $goods['pic_url'];
            } else {
                $order_goods[$key]['pic_url'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
            }
        }
        $order['goods'] = $order_goods;
        $this->assign('order',$order);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }

    /**
     * 导出数据到excel
     * @return [type] [description]
     */
    public function export()
    {
        import("Common.Library.PHPExcel.IOFactory");
        import("Common.Library.PHPExcel");
    
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = trim(I('keyword'));
        
        //订单状态
        if ((isset($_GET["status"]) || isset($_POST["status"])) && $status != '-1') {
            $where['status'] = $status;
        }
        //时间
        if ($start_date && $end_date) {
            $where['add_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['add_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['add_time'] = array('ELT', $end_date . ' 23:59:59');
        }
        //关键词
        if ($keyword) {
            $map['user_no'] = array('like', '%' . $keyword . '%') ;
            $map['order_no'] = array('like', '%' . $keyword . '%') ;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
        
        //查询数据
        $orders = M('Order')->where($where)->order('id desc')->select();
        
        //phpexcel操作对象
        $phpexcel = new \PHPExcel;
        //制表头
        $phpexcel->getActiveSheet()->setCellValue('A1', '订单号');
        $phpexcel->getActiveSheet()->setCellValue('B1', '收货人');
        $phpexcel->getActiveSheet()->setCellValue('C1', '收货地址');
        $phpexcel->getActiveSheet()->setCellValue('D1', '收货人手机');
        $phpexcel->getActiveSheet()->setCellValue('E1', '包邮/到付/折扣');
        $phpexcel->getActiveSheet()->setCellValue('F1', '商品');
        
        if($orders){
            $i=2;
            foreach ($orders as $key=>$order){
        
                if($order['shipping_set'] == Constants::SHIPPING_ALL_FREE){
                    $orderstr = '包邮';
                }else if($order['shipping_set'] == Constants::ORDER_SHIPPING_TO_PAY){
                    $orderstr = '到付';
                }else{
                    $orderstr = '折扣';
                }
        
                $phpexcel->getActiveSheet()->setCellValue('A' . $i, $order['order_no']);
                $phpexcel->getActiveSheet()->setCellValue('B' . $i, $order['receiver']);
                $phpexcel->getActiveSheet()->setCellValue('C' . $i, $order['receiver_address']);
                $phpexcel->getActiveSheet()->setCellValue('D' . $i, $order['receiver_phone']);
                $phpexcel->getActiveSheet()->setCellValue('E' . $i, $orderstr);
        
                //获取订单商品信息
                $where = array(
                        'aog.order_id'  => $order['id']
                );
                $order_goods = D('OrderGoods')->getList($where);
                if ($order_goods) {
                    $goods_str = '';
                    foreach ($order_goods as $o_key=> $goods) {
                        $goods_str .= $goods['title']." * ".$goods['quantity']."\n";
                    }
                    $phpexcel->getActiveSheet()->setCellValue('F' . $i, "$goods_str");
                    $phpexcel->getActiveSheet()->getStyle('F' . $i)->getAlignment()->setWrapText(true);
                    $i++;
                }else{
                    $i++;
                }
            }
        }
        
        $file_name = date('YmdHis',time()).'.xls';
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$file_name.'"');
        header("Content-Transfer-Encoding:binary");
        $excelwrite = new \PHPExcel_Writer_Excel5($phpexcel);//将信息记录excel表格中
        $excelwrite->save('php://output');
    
        //操作日志
        $this->addLog('导出订单成功。');
    }

    /**
     * 确认订单
     * @return [type] [description]
     */
    public function confirmOrder()
    {
        if (IS_POST) { //数据提交
            $operate_remark = trim(I('operate_remark'));
            $id = I('id');
            if(!$operate_remark || mb_strlen($operate_remark,'utf-8')>100 || mb_strlen($operate_remark,'utf-8')<2){
                $result = array(
                        'status'  => false,
                        'message' => '请认真填写备注'
                );
                $this->ajaxReturn($result);
            }
            $order = M("Order")->find($id);
            //判断是否重复操作
            if ($order['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }
            
            //保存操作设置
            $data = array(
                    'id'            => $id,
                    'status'        => Constants::OPERATE_STATUS_CONFIRM,
                    'operate_remark'=> $operate_remark,
                    'operate_time'  => curr_time()
            );
            $re = M("Order")->save($data);
        
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );
                //传递模板参数
                $msg_param = array(
                        'order_no'  => $order['order_no'],
                        'operate'   => '已确认'
                );
                //传下单用户
                $receiver = array(
                        'user_no' => $order['user_no'],
                );
                $this->sendMessage($receiver,'MESSAGE_ORDER',json_encode($msg_param),Constants::MESSAGE_TYPE_ORDER);
                //操作日志
                $this->addLog('同意了' . $order['user_no'] . '的订单。订单号为：' . $order['order_no'], $id);
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '操作处理失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 驳回订单
     * @return [type] [description]
     */
    public function rejectOrder(){
        if (IS_POST) { //数据提交
            $operate_remark = trim(I('operate_remark'));
            $id = I('id');
            if(!$operate_remark || mb_strlen($operate_remark,'utf-8')>100 || mb_strlen($operate_remark,'utf-8')<2){
                $result = array(
                        'status'  => false,
                        'message' => '请认真填写备注'
                );
                $this->ajaxReturn($result);
            }
            $order = M("Order")->find($id);
            //判断是否重复操作
            if ($order['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }
    
            $user = M('User')->where(array('user_no'=>$order['user_no']))->find();
            $goods_list = D('OrderGoods')->where(array('order_id'=>$id))->select();
            
            M()->startTrans();
            
            //对订单商品信息进行处理
            foreach($goods_list as $goods){
                //变更商品数量
                $goods_data = array(
                        'id'                => $goods['goods_id'],
                        'inventory_number'  => array('exp', 'inventory_number + ' . $goods['quantity']),
                        'sell_number'       => array('exp', 'sell_number - ' . $goods['quantity'])
                );
                $goods_res = M('Goods')->save($goods_data);
                if ($goods_res === false) {
                    M()->rollback();
            
                    $result = array(
                            'status'  => false,
                            'message' => '驳回订单出现异常'
                    );
                    $this->ajaxReturn($result);
                }
            }
            
            //保存操作设置
            $data = array(
                    'id'            => $id,
                    'status'        => Constants::OPERATE_STATUS_REJECT,
                    'operate_remark'=> $operate_remark,
                    'operate_time'  => curr_time()
            );
            $order_res = M("Order")->save($data);
            
            //变更用户资金信息
            $user_data = array(
                    'id'            => $user['id'],
                    'tb_account'    => array('exp', 'tb_account + ' . $order['total']),
            );
            $user_res = M('User')->save($user_data);
            //保存用户购物币账户变更信息
            if ($order['total'] > 0) {
                $record = array(
                        'user_no'       => $user['user_no'],
                        'account_type'  => Constants::ACCOUNT_TYPE_TB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                        'amount'        => $order['total'],
                        'balance'       => $user['tb_account'] + $order['total'],
                        'remark'        => '商城订单驳回，购物币回滚,订单号' . $order['order_no'],
                        'add_time'      => curr_time()
                );
                $tb_account = M('AccountRecord')->add($record);
            }
            
            if ($order_res !== false && $user_res !== false && $tb_account !== false) {
                M()->commit();
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );
                //传递模板参数
                $msg_param = array(
                        'order_no'  => $order['order_no'],
                        'operate'   => '已驳回'
                );
                //传下单用户
                $receiver = array(
                        'user_no' => $order['user_no'],
                );
                $this->sendMessage($receiver,'MESSAGE_ORDER',json_encode($msg_param),Constants::MESSAGE_TYPE_ORDER);
                //操作日志
                $this->addLog('驳回了' . $order['user_no'] . '的订单。订单号为：' . $order['order_no'], $id);
            } else {
                M()->rollback();
                 
                $result = array(
                        'status'  => false,
                        'message' => '驳回订单出现异常'
                );
            }
            $this->ajaxReturn($result);
        }
    }
    // -------------------- 订单信息 end --------------------


    // -------------------- 收货地址 begin --------------------
    /**
     * 收货地址列表
     * @return [type] [description]
     */
    public function addrList()
    {
        $address = D('Address')->selectList($_POST,'select',$this->sys_config['SYSTEM_PAGE_NUMBER']);//查询地址信息
        $this->assign('list',$address['data']);
        $this->assign('page',$address['page']);
        $this->assign('date_start',I('date_start'));
        $this->assign('date_end',I('date_end'));
        $this->assign('user_no',I('user_no'));
        $this->display();
    }

    /**
     * 添加或修改收货地址
     */
    public function addAddr()
    {
        $id = I('id');
        //验证post表单提交
        if(IS_POST){
            $data['receiver'] = trim(I('receiver'));
            $data['phone'] = trim(I('phone'));
            $data['province'] = I('cmbProvince');
            $data['city'] = I('cmbCity');
            $data['zone'] = I('cmbArea');
            $addrinfo = I('addrinfo');
            //id为空，表示添加，如果有值表示修改的功能
            if(!empty($id)){
                $address = trim(I('address'));
                $data['address'] = $addrinfo.' '.$address;
                $data['update_time'] = curr_time();
                $res = D('Address')->where(array('id'=>$id))->save($data);//信息修改
                if($res !== false){
                    $result = array(
                        'status'  => true,
                        'message' => '修改成功'
                    );
                    //操作日志
                    $this->addLog('修改地址信息成功。', $id); 
                }
            }
           
            $this->ajaxReturn($result);
        }

        $type = I('type');
        if(!empty($id)){
            $selectinfo = D('Address')->where(array('id'=>$id))->find();
            $data = explode(' ', $selectinfo['address']);
            if($selectinfo){
                $this->assign('addrinfo',$data[0]);
                $this->assign('address',$data[1]);
                $this->assign('selectinfo',$selectinfo);
            }
        }

        $this->assign('type',$type);
        $this->display();

    }

    /**
     * 删除收货地址
     * @return [type] [description]
     */
    public function addressDelete()
    {
        $id = I('id');
        if(!empty($id)){
            //通过id删除地址信息
            $dellog =  D('Address')->where(array('id'=>$id))->find();
            $res = D('Address')->where(array('id'=>$id))->delete();
            if($res !== false){
                //操作日志
                $this->addLog('删除地址信息成功。', $id);
                $result = array(
                    'status'  => true,
                    'message' => '删除成功'
                );
                $this->ajaxReturn($result);
            }
        }
    }
    // -------------------- 收货地址 begin --------------------

    /**
     * 图片上传
     * @param  integer $min_width  [description]
     * @param  integer $min_height [description]
     * @param  integer $max_width  [description]
     * @param  integer $max_height [description]
     * @return [type]              [description]
     */
    private function _upload($min_width = 100, $min_height = 100, $max_width = 1000, $max_height = 1000)
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   = 5 * 1024 * 1024; //1M 设置附件上传大小
        $upload->exts      = array("gif", "jpg", "jpeg", "png"); // 设置附件上传类型
        $upload->rootPath  = Constants::UPLOAD_ROOT_PATH; //保存根路径
        $upload->savePath  = Constants::UPLOAD_MALL_PATH; //保存路径
        $upload->subName   = array('date', 'Ymd');

        // 上传文件
        $info = $upload->uploadOne($_FILES['image_file']);
        if(!$info) {    // 上传错误提示错误信息
            $result = array(
                    'status'    => false,
                    'message'   => $upload->getError()
            );
            return $result;
        }else{  // 上传成功
//             file_put_contents("d:\\ok_file.txt", var_export($info,true));
            //获取原图
            $big_pic_url = Constants::UPLOAD_ROOT_PATH . $info['savepath'] . $info['savename'];
            $image = new \Think\Image();
            $image->open($big_pic_url);
            //图片太小
            if ($image->width() < $min_width || $image->height() < $min_height) {
                $result = array(
                        'status'    => false,
                        'message'   => '上传的图片太小'
                );
                unlink($big_pic_url);
                return $result;
            }
            //宽高比例不是1：1，对原图进行裁剪
            if ($image->width()/$image->height() != 1) {
                //进行图片裁剪的参数获取
                $x = I('post.x1');  //裁剪区域x坐标
                $y = I('post.y1');  //裁剪区域y坐标
                $w = I('post.w');   //裁剪区域宽度
                $h = I('post.h');   //裁剪区域高度
                //裁剪区域过小
                if (!$w || !$h || $w < $min_width || $h < $min_height) {
                    $result = array(
                            'status'    => false,
                            'message'   => '裁剪区域不正确'
                    );
                    unlink($big_pic_url);
                    return $result;
                }
                //图片裁剪
                $image->crop($w, $h, $x, $y, $w, $h);
            }
            //如果图片过大，进行压缩
            if ($image->width() > $max_width || $image->height() > $max_height) {
                $image->thumb($max_width, $max_height);
            }
            $image->save($big_pic_url);
            
            //对图片进行生成缩略图处理
            $pic_url = Constants::UPLOAD_ROOT_PATH . $info['savepath'] . 's_' . $info['savename'];
            $image->thumb($min_width, $min_height)->save($pic_url);
            
            $result = array(
                    'status'        => true,
                    'pic_url'       => $pic_url,
                    'big_pic_url'   => $big_pic_url
            );
            return $result;
        }
    }
}