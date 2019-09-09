<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Conf\Constants;

/**
 * 商城模块管理控制器
 *
 * @since: 2016年12月14日 上午9:27:17
 * @author: lyx
 * @version: V1.0.0
 */
class MallController extends HomeBaseController {

   /**
    * 初始化
    *
    * @since: 2016年12月9日 上午10:33:56
    * @author: Wang Peng
    */
	public function _initialize() {
		parent::_initialize();
		
		//系统是否开启商城，没有开启商城不能进行任何操作
        if(!$this->sys_config['SYSTEM_OPEN_MALL']){
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启商城功能，不能进行操作！'));
        }
	}

	/**
	 * 商品列表
	 * @return [type] [description]
	 */
	public function goods()
	{
		$keyword = I('keyword');
	    $goods_category_id = I('goods_category_id');
	    
	    //商品分类
	    if ($goods_category_id) {
	        $where['goods_category_id'] = $goods_category_id;
	    }
        $where['status'] = Constants::NORMAL;
	    //关键词
	    if ($keyword) {
	        $where['title'] = array('like', '%' . $keyword . '%') ;
	    }
	    
	    //查询数据
	    $goods_list = D('Goods')->getList($where, I(), Constants::PAGE_NUMBER_MALL);
	    foreach ($goods_list['data'] as $key => $goods) {
	        $goods_extent  =  M('GoodsExtend')->field('big_pic_url')->where(array('goods_id'=>$goods['id']))->find();
	        if ($goods_extent['big_pic_url']) {
	            $goods_list['data'][$key]['big_pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/'. $goods_extent['big_pic_url'];//传入商品图片信息
	        } else {
	            $goods_list['data'][$key]['big_pic_url'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
	        }
	    }
	    
	    //获取商品分类
	    $categorys = M('GoodsCategory')->where(array('status'=>Constants::NORMAL))->select();
	    
	    //返回页面的数据
	    $this->assign('list', $goods_list['data']);
	    $this->assign('page', $goods_list['page']);
	    $this->assign('goods_category_id', $goods_category_id);
	    $this->assign('keyword', $keyword);
	    $this->assign('categorys',$categorys);
	    $this->assign('currency_symbol',$this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
	    $this->display();
	}

	/**
	 * 商品详情页
	 * @return [type] [description]
	 */
	public function detail(){
        $goods = M('Goods')->where(array('id'=>I('id')))->find();
        $goods_extent  =  M('GoodsExtend')->field('detail,big_pic_url')->where(array('goods_id'=>I('id')))->find();
        $goods_category = M('GoodsCategory')->field('title')->find($goods['goods_category_id']);
        
        $goods['detail'] = $goods_extent['detail'];
        //获取图片信息
        if ($goods_extent['big_pic_url']) {
            $goods['big_pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/'. $goods_extent['big_pic_url'];
        } else {
            $goods['big_pic_url'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
        }
        $goods['categoty'] = $goods_category['title'];
        
        $this->assign('currency_symbol',$this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->assign('goods', $goods);
        $this->display();
    }

    /**
     * 加入购物车
     */
    public function addCart()
    {
        if (IS_POST) { //数据提交
            $user_no = $this->user['user_no'];
            $goods_id = I('post.goods_id');
            $quantity = I('post.quantity');
            //验证是否为报单中心
            if($this->sys_config['SYSTEM_MALL_ALLOW_ROLE'] == Constants::MALL_ALLOW_ROLE_SERVICE_CENTER && !D('ServiceCenter')->getServiceCenter($user_no)){
				$result = array(
                        'status'  => false,
                        'message' => '不是报单中心会员，不能选购'
                );
                $this->ajaxReturn($result);
            }
            //获取商品信息
            $goods = M('Goods')->where(array('id'=>$goods_id))->find();
            //验证商品状态库存
            if($goods['status'] == Constants::DISABLE){
                $result=array(
                        'status'    => false,
                        'message'   => '商品已下架,不能选购'
                );
                //验证返回信息
                $this->ajaxReturn($result);
            }
            //验证商品库存
            if($goods['inventory_number']<$quantity){ 
                $result=array(
                        'status'    => false,
                        'message'   => '库存不足,不能选购'
                );
                $this->ajaxReturn($result);
            
            }
            //验证商品限购
            if($goods['limit_number'] >0 && $goods['limit_number']<$quantity){
                $result=array(
                        'status'    => false,
                        'message'   => '超过限购标准，不能选购'
                );
                $this->ajaxReturn($result);
            }
            
            $Cart = D("Cart"); // 实例化对象
            $Cart->create();
            $cart = M('Cart')->where(array('user_no'=>$user_no,'goods_id'=>I('post.goods_id')))->find();
            
            if($cart){
                //修改购物车
                $Cart->id = $cart['id'];
                $Cart->user_no = $user_no;
                $Cart->quantity = $quantity + $cart['quantity'];
                $res_cart = $Cart->save();
            }else{
                //添加购物车
                $Cart->user_no = $user_no;
                $res_cart = $Cart->add();
            }
            
            if ($res_cart !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '加入购物车成功'
                );
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '加入购物车失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 购物车列表
     * @return [type] [description]
     */
    public function cart()
    {
        $where = array(
                'c.user_no' => $this->user['user_no']
        );
        $carts = D('Cart')->getList($where);
        foreach ($carts as $key => $cart) {
            //获取图片信息
            if ($cart['pic_url']) {
                $carts[$key]['pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/'. $cart['pic_url'];
            } else {
                $carts[$key]['pic_url'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
            }
            $carts[$key]['validate'] = ($cart['limit_number']==0||$cart['limit_number']>0&&$cart['limit_number']>=$cart['quantity'])&&$cart['inventory_number']>=$cart['quantity']&&$cart['status']==1 ? true : false;
        }

        $this->assign('list',$carts);
        $this->assign('currency_symbol',$this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }

    /**
     * 删除购物车商品
     * @return [type] [description]
     */
    public function deleteCart()
    {
        if (IS_POST) { //数据提交
            //删除
            $res = M("Cart")->delete(I('post.id'));
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
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
     * 清空购物车
     * @return [type] [description]
     */
    public function cleanCart()
    {
        if (IS_POST) { //数据提交
            //删除
            $res = M("Cart")->where(array('user_no'=>$this->user['user_no']))->delete();
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '清空成功！'
                );
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '清空失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 修改购物车
     * @return [type] [description]
     */
    public function changeCart()
    {
        if (IS_POST) { //数据提交
            $user_no = $this->user['user_no'];
            $goods_id = I('post.goods_id');
            $quantity = I('post.quantity');
            
            //验证是否为报单中心
            if($this->sys_config['SYSTEM_MALL_ALLOW_ROLE'] == Constants::MALL_ALLOW_ROLE_SERVICE_CENTER && !D('ServiceCenter')->getServiceCenter($user_no)){
                $result = array(
                        'status'    => false,
                        'message'   => '不是报单中心会员，不能选购'
                );
                $this->ajaxReturn($result);
            }
            
            //修改购物车
            $res_cart = M('Cart')->where(array('user_no'=>$user_no,'goods_id'=>$goods_id))->save(array('quantity'=>$quantity));
            if ($res_cart !== false) {
                //获取商品信息
                $goods = M('Goods')->where(array('id'=>$goods_id))->find();
                $errors = array();
                //验证商品状态库存
                if($goods['status'] == Constants::DISABLE){
                    $errors[] = 1;
                }
                //验证商品库存
                if($goods['inventory_number']<$quantity){
                    $errors[] = 2;
                }
                //验证商品限购
                if($goods['limit_number'] >0 && $goods['limit_number']<$quantity){
                    $errors[] = 3;
                }
                $result = array(
                        'status'    => true,
                        'errors'    => $errors,
                        'message'   => '加入购物车成功'
                );
            } else {
                $result = array(
                        'status'    => false,
                        'message'   => '购物数量修改失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 下单页面
     * @return [type] [description]
     */
    public function order()
    {
        if (IS_POST) { //数据提交
            $user_no = $this->user['user_no'];//获取会员编号
            $goods_ids = I('post.goods_ids');
            $address_id = I('post.address_id');
            $remark = I('post.remark');
            
            //验证下单参数
            if (!$goods_ids || !$address_id) {
                $result = array(
                        'status'    => false,
                        'message'   => '订单参数有误，请重新下单'
                );
                $this->ajaxReturn($result);
            }
            //验证是否为报单中心
            if ($this->sys_config['SYSTEM_MALL_ALLOW_ROLE'] == Constants::MALL_ALLOW_ROLE_SERVICE_CENTER && !D('ServiceCenter')->getServiceCenter($user_no)) {
                $result = array(
                        'status'    => false,
                        'message'   => '不是报单中心会员，不能下单'
                );
                $this->ajaxReturn($result);
            }
            //验证是否开启多笔未完成订单
            if (!$this->sys_config['SYSTEM_MULTI_UNFINISHED_REMIT']) {
                $order_count = M('Order')->where(array('user_no'=>$user_no,'status'=>Constants::OPERATE_STATUS_INITIAL))->count();
                //验证订单是否处理
                if ($order_count > 0) {
                    $result = array(
                            'status'  => false,
                            'message' => '还有未处理订单',
                    );
                    $this->ajaxReturn($result);
                }
            }
            
            $where = array(
                    'c.user_no' => $user_no,
                    'c.id'      => array('in',$goods_ids)
            );
            $carts = D('Cart')->getList($where);
            foreach ($carts as $cart) {
                if ($cart['status'] == Constants::DISABLE) {
                    $result = array(
                            'status'  => false,
                            'message' => $cart['title'].'已下架'
                    );
                    $this->ajaxReturn($result);
                }
                if ($cart['quantity'] < 1) {
                    $result = array(
                            'status'  => false,
                            'message' => $cart['title'].'的购买数量不能小于1',
                    );
                    $this->ajaxReturn($result);
                }
                if ($cart['quantity'] > $cart['inventory_number']) {
                    $result = array(
                            'status'  => false,
                            'message' => $cart['title'].'库存不足'
                    );
                    $this->ajaxReturn($result);
                }
                if ($cart['quantity'] > $cart['limit_number'] && $cart['limit_number'] > 0) {
                    $result = array(
                            'status'  => false,
                            'message' => $cart['title'].'超出限购'
                    );
                    $this->ajaxReturn($result);
                }
                //金额总计
                $total += $cart['quantity']*$cart['new_price'];
            }
            //验证购物币
            if ($this->user['tb_account'] < $total) {
                $result = array(
                        'status'  => false,
                        'message' => '购物币不足'
                );
                $this->ajaxReturn($result);
            }
            
            $address = M('Address')->where(array('id'=>$address_id))->find();
            $order_no = $this->_orderNum();
            //计算邮费方式
            $shipping_set = Constants::SHIPPING_ALL_FREE;
            $shipping = 0;
            if($this->sys_config['SYSTEM_ORDER_FREE_SHIPPING'] == Constants::SHIPPING_ALL_FREE || $total >= $this->sys_config['SYSTEM_ORDER_FREE_SHIPPING'] || $this->sys_config['SYSTEM_ORDER_SHIPPING'] == 0){
                //             $shipping_mode = '包邮';
            }else{
                if($this->sys_config['SYSTEM_ORDER_SHIPPING'] == Constants::SHIPPING_FREIGHT_COLLECT){
                    $shipping_set = Constants::ORDER_SHIPPING_TO_PAY;
                } else{
                    $shipping_set = Constants::ORDER_SHIPPING_WITHHOLD;
                    $shipping = $this->sys_config['SYSTEM_ORDER_SHIPPING'];
                }
            }
            
            M()->startTrans();//开启事务
            //订单信息
            $order_data = array(
                    'order_no'          => $order_no,
                    'user_no'           => $user_no,
                    'receiver'          => $address['receiver'],
                    'receiver_phone'    => $address['phone'],
                    'receiver_address'  => $address['address'],
                    'remark'            => $remark,
                    'amount'            => $total,
                    'shipping_set'      => $shipping_set,
                    'shipping'          => $shipping,
                    'status'            => Constants::OPERATE_STATUS_INITIAL,
                    'add_time'          => curr_time(),
                    'total'             => $total+$shipping,
            );
            //添加订单信息
            $order_id = D('Order')->add($order_data);
            if ($order_id === false) {
                $result = array(
                        'status'  => false,
                        'message' => '订单错误'
                );
                $this->ajaxReturn($result);
            }
            //对订单商品信息进行处理
            foreach($carts as $cart){
                //订单商品写入
                $order_goods = array(
                        'order_id'      => $order_id,
                        'goods_id'      => $cart['goods_id'],
                        'quantity'      => $cart['quantity'],
                        'price'         => $cart['new_price'],
                        'subtotal'     => round($cart['quantity']*$cart['new_price'], 2),
                );
                $order_goods_id = D('OrderGoods')->add($order_goods);
                //变更商品数量
                $goods_data = array(
                        'id'                => $cart['goods_id'],
                        'inventory_number'  => array('exp', 'inventory_number - ' . $cart['quantity']),
                        'sell_number'       => array('exp', 'sell_number + ' . $cart['quantity'])
                );
                $goods_res = M('Goods')->save($goods_data);
                //删除购物车
                $cart_res = M("Cart")->delete($cart['id']);
                if ($order_goods_id === false || $goods_res === false || $cart_res === false) {
                    M()->rollback();
            
                    $result = array(
                            'status'  => false,
                            'message' => '订单错误'
                    );
                    $this->ajaxReturn($result);
                }
            }
            //变更用户资金信息
            $user_data = array(
                    'id'            => $this->user['id'],
                    'tb_account'    => array('exp', 'tb_account - ' . $total),
            );
            $user_res = M('User')->save($user_data);
            if ($user_res === false) {
                M()->rollback();
                 
                $result = array(
                        'status'  => false,
                        'message' => '资金错误'
                );
                $this->ajaxReturn($result);
            }
            //保存用户购物币账户变更信息
            if ($total > 0) {
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_TB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $total,
                        'balance'       => $this->user['tb_account'] - $total,
                        'remark'        => '商城订单扣除购物币,订单号' . $order_no,
                        'add_time'      => curr_time()
                );
                $tb_account = M('AccountRecord')->add($record);
            }
            
            if ($tb_account !== false) {
                M()->commit();
                //清空cookie
                cookie('order',null);
            
                //下单成功发送短信
                $msg_param = array('order_no' => $order_no);
                $receiver = array(
                        'user_no'   => $user_no,
                        'phone'     => $this->user['phone']
                );
                $this->sendMessage($receiver,'MESSAGE_BUY',json_encode($msg_param),Constants::MESSAGE_TYPE_BUY);
            
                //操作日志
                $this->addLog('订单下单成功。', $order_id);
                	
                $result = array(
                        'status'  => true,
                        'message' => '订单下单成功'
                );
                $this->ajaxReturn($result);
            }else{
                M()->rollback();
                $result = array(
                        'status'  => false,
                        'message' => '订单错误'
                );
            }
            $this->ajaxReturn($result);
        }
        
        $ids = I('ids');
        if ($ids) {
            cookie('order', $ids);
        } else {
            $ids = cookie('order');
        }
        
        $where = array(
                'c.user_no' => $this->user['user_no'],
                'c.id'      => array('in',$ids)
        );
        $carts = D('Cart')->getList($where);
        foreach ($carts as $key => $cart) {
            //获取图片信息
            if ($cart['pic_url']) {
                $carts[$key]['pic_url'] = $this->sys_config['WEB_DOMAIN'] . '/'. $cart['pic_url'];
            } else {
                $carts[$key]['pic_url'] = C("TMPL_PARSE_STRING.__PUBLIC_IMAGES__")  . "/no-pic.jpg";
            }
            
            $total += $cart['quantity']*$cart['new_price'] ;
        }
        
        //计算邮费方式
        $shipping_mode = '包邮';
        $shipping = 0;
        if($this->sys_config['SYSTEM_ORDER_FREE_SHIPPING'] == Constants::SHIPPING_ALL_FREE || $total >= $this->sys_config['SYSTEM_ORDER_FREE_SHIPPING'] || $this->sys_config['SYSTEM_ORDER_SHIPPING'] == 0){
//             $shipping_mode = '包邮';
        }else{
            if($this->sys_config['SYSTEM_ORDER_SHIPPING'] == Constants::SHIPPING_FREIGHT_COLLECT){
                $shipping_mode = '到付';
            } else{
                $shipping_mode = '代扣';
                $shipping = $this->sys_config['SYSTEM_ORDER_SHIPPING'];
            }
        }
        $address_list = D('Address')->addrsList(array('user_no'=>$this->user['user_no']));
    
        $this->assign('list',$carts);
        $this->assign('currency_symbol',$this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->assign('address_list',$address_list);
        $this->assign('shipping_mode',$shipping_mode);
        $this->assign('shipping',$shipping);
        $this->assign('total',$total);
        $this->display();
    }

    /**
     * 验证是否可以添加地址信息
     * @return boolean [description]
     */
    public function isCanAddAddress(){
        //查询用户的地址个数
        $count = M('Address')->where(array('user_no'=>$this->user['user_no']))->count();
        //验证地址的个数
        if ($count < $this->sys_config['SYSTEM_ADDRESS_NUMBER']) {
            $result = array(
                    'status'    => true,
                    'message'   => '可以添加地址'
            );
        } else {
            $result = array(
                    'status'    => false,
                    'message'   => '最多只能添加'.$this->sys_config['SYSTEM_ADDRESS_NUMBER'].'条地址信息！'
            );
            
        }

        $this->ajaxReturn($result);
    }

    /**
     * 设为默认地址
     */
    public function setAddress()
    {
        if (IS_POST) { //数据提交
    
            //清除默认设置
            $data['is_default'] = Constants::NO;
            $where = array(
                    'user_no'       => $this->user['user_no']
            );
            $re_all = M('Address')->where($where)->save($data);
    
            //设置默认
            $_POST['update_time'] = curr_time();
            $_POST['is_default'] = Constants::YES;
            $re = M('Address')->save($_POST);
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '设置成功！'
                );
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '设置失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 删除地址
     * @return [type] [description]
     */
    public function delAddress() {
        if (IS_POST) { //数据提交
            //数据删除
            $re = M('Address')->delete(I('post.id'));
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
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
     * 添加地址页面（包括操作处理）
     */
    public function addAddress()
    {
        if (IS_POST) { //数据提交
    
            $Address = D("Address"); // 实例化对象
            if (!$Address->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Address->getError()
                );
            } else {
                //数据保存
                $Address->user_no = $this->user['user_no'];
                $address_info = I('addrinfo') .' '. trim(I('address'));
                $Address->address = $address_info;
                
                $re = $Address->add(); // 写入数据到数据库
                if ($re !== false) {
                    if($_COOKIE['order']){
                        $result = array(
                                'status'    => true,
                                'flag'      => 'mall',
                                'message'   => '地址添加成功'
                        );
                    } else {
                        $result = array(
                                'status'    => true,
                                'message'   => '地址添加成功！'
                        );
                    }
                    //操作日志
                    $this->addLog('添加地址：' . $address_info . '。', $re);
                } else {
                    $result = array(
                            'status'    => false,
                            'message'   => '地址添加失败！'
                    );
                }
            }
    
            $this->ajaxReturn($result);
        }
    
        $this->assign('type',I('type'));
        $this->display();
    }

    /**
     * 修改地址页面（包括操作处理）
     * @return [type] [description]
     */
    public function editAddress() {
        if (IS_POST) { //数据提交
    
            $Address = D("Address"); // 实例化对象
            if (!$Address->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Address->getError()
                );
            } else {
                //数据保存
                $address_info = I('addrinfo') .' '. trim(I('address'));
                $Address->address = $address_info;
                $re = $Address->save();
                if ($re !== false) {
                    if($_COOKIE['order']){
                        $result = array(
                                'status'    => true,
                                'flag'      => 'mall',
                                'message'   => '地址修改成功'
                        );
                    } else {
                        $result = array(
                                'status'    => true,
                                'message'   => '地址修改成功！'
                        );
                    }
                    //操作日志
                    $this->addLog('修改地址：' . $address_info . '。', I('id'));
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '地址修改失败！'
                    );
                }
            }
    
            $this->ajaxReturn($result);
        }
    
        $address = M('Address')->find(I('get.id'));
        
        $address_arr = explode(' ', $address['address']);
        $address['info'] = $address_arr[0];
        $address['address'] = $address_arr[1];
        
        $this->assign('type',I('type'));
        $this->assign('address', $address);
        $this->display();
    }

    /**
     * 收货地址列表
     * @return [type] [description]
     */
    public function addrList()
    {
    	$address = D('Address')->selectList('','', $this->sys_config['SYSTEM_PAGE_NUMBER']);
    	$this->assign('list', $address['data']);
    	$this->assign('page', $address['page']);
    	$this->display();
    }

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
            $where['order_no'] = array('like', '%' . $keyword . '%') ;
        }
        //当前会员的
        $where['user_no'] = $this->user['user_no'];
        
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
     * 订单详情页
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
     * 生成订单号
     *
     * @since: 2017年4月21日 下午6:04:22
     * @author: lyx
     */
    private function _orderNum(){
        //生成订单号
        $name = mall_order_no();
        $order_count = D('Order')->where(array('order_no'=>$name))->count();
        if($order_count !== false && $order_count == 0){
            return $name;
        }else{
            $this->_orderNum();
        }
    }
}