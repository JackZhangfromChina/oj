<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;
use Common\Common\MySQLReback;
use Think\Exception;

/**
 * 数据维护控制器
 *
 * @since: 2016年12月13日 下午3:59:18
 * @author: lyx
 * @version: V1.0.0
 */
class DataController extends AdminBaseController {

    /**
     * 初始化
     *
     * @since: 2017年5月10日 上午10:43:04
     * @author: lyx
     */
    public function _initialize() {
        parent::_initialize();

        $this->assign('system_state', $this->sys_config['WEB_SYSTEM_STATE']);
    }

	/**
	 * 数据维护欢迎页
	 *
	 * @since: 2017年1月7日 下午2:59:08
	 * @author: lyx
	 */
	public function index() {

		$this->display();
	}

	/**
	 * 数据库初始化
	 *
	 * @since: 2016年12月13日 下午4:02:36
	 * @author: lyx
	 */
	public function init() {
		//post请求，处理数据库初始化
		if (IS_POST) {

			//系统在开启状态下，不允许进行敏感操作，以免引起数据异常
			if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
				$result = array(
    					'status'  => false,
    					'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
				);
				$this->ajaxReturn($result);
			}

			//再次确认密码
			if (!password_verify($_POST['password'], $this->admin['password'])) {
			    $result = array(
			            'status'  => false,
			            'message' => '口令不正确！'
			    );
			    $this->ajaxReturn($result);
			}

			//不限制相应时间
			set_time_limit(0);
			ini_set("memory_limit",-1);

			//先进行数据备份
			$config = array(
    				'host'          => C('DB_HOST'),
    				'port'          => C('DB_PORT'),
    				'userName'      => C('DB_USER'),
    				'userPassword'  => C('DB_PWD'),
    				'dbprefix'      => C('DB_PREFIX'),
    				'savePath'      => Constants::UPLOAD_ROOT_PATH.Constants::UPLOAD_DATABASE_PATH,
    				'charset'       => C('DB_CHARSET')
			);
			$mr = new MySQLReback($config);
			$mr->setDBName(C('DB_NAME'));
			$mr->backup();

			//清空数据库中所有数据
			$this->_deleteData();

			//初始化配置分组
			$group_names = array('系统设置','站点设置','奖项设置','会员设置','短信设置','支付设置','邮件设置');
			foreach ($group_names as $group_name) {
				D('ConfigGroup')->add(array("title"=>$group_name));
			}
			//初始化配置(系统设置)
			$this->_initConfigSys('系统设置');
			//初始化配置(站点设置)
			$this->_initConfigWeb('站点设置');
			//初始化配置(奖项设置)
			$this->_initConfigAward('奖项设置');
			//初始化配置(会员设置)
			$this->_initConfigUser('会员设置');
			//初始化配置(短信设置)
			$this->_initConfigMessage('短信设置');
			//初始化配置(支付设置)
			$this->_initConfigPay('支付设置');
			//初始化配置(邮件设置)
			$this->_initConfigMail('邮件设置');

			//初始化会员级别
			$this->_initUserLevel();

			//初始化管理员权限认证
			$this->_initAuth();

			//初始化顶点信息
			$user_no = "mlmcms888";
			$user_level=M('UserLevel')->order('id DESC')->find();
			$user = array(
    				'user_no'       => $user_no,
    				'password'      => password_hash(sha1($this->sys_config['USER_INITIAL_PASSWORD']), PASSWORD_DEFAULT),
    				'two_password'  => password_hash(sha1($this->sys_config['USER_INITIAL_PASSWORD']), PASSWORD_DEFAULT),
    				'realname'      => $user_no,
    				'location'      => Constants::LOCATION_LEFT,
    				'path'          => Constants::LOCATION_LEFT,
    				'floor'         => 1,
    				'rec_floor'     => 1,
    				'user_level_id' => $user_level['id'],
    				'investment'    => $user_level['investment'],
    				'eb_account'    => 100000,
    				'tb_account'    => 100000,
    				'is_activated'  => Constants::YES,
    				'add_time'      => curr_time(),
    				'activate_time' => curr_time(),
			);
			D('User')->add($user);
			//初始化报单中心
			$user = array(
    				'user_no'       => $user_no,
    				'status'        => Constants::OPERATE_STATUS_CONFIRM,
    				'add_time'      => curr_time(),
    				'update_time'   => curr_time(),
			);
			D('ServiceCenter')->add($user);

			//操作日志
			$this->addLog('进行了数据库初始化的操作。');

			$result = array(
    				'status'  => true,
    				'message' => '数据初始化成功!'
			);
			$this->ajaxReturn($result);
		}

		$this->display();
	}

	/**
	 * 配置初始化
	 *
	 * @since: 2016年12月30日 上午9:26:19
	 * @author: lyx
	 */
	public function initConfig() {
		//post请求，处理数据库初始化
		if (IS_POST) {
			//系统在开启状态下，不允许进行敏感操作，以免引起数据异常
			if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
				$result = array(
    					'status'  => false,
    					'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
				);
				$this->ajaxReturn($result);
			}

			//再次确认密码
			if (!password_verify($_POST['password'], $this->admin['password'])) {
			    $result = array(
			            'status'  => false,
			            'message' => '口令不正确！'
			    );
			    $this->ajaxReturn($result);
			}

			//不限制相应时间
			set_time_limit(0);

			//清空配置信息
			$sql_arr[] = "truncate table `".$this->db_prefix."config`";
			$sql_arr[] = "truncate table `".$this->db_prefix."user_level`";
			$sql_arr[] = "truncate table `".$this->db_prefix."config_group`";
			$sql_arr[] = "truncate table `".$this->db_prefix."admin`";
			$sql_arr[] = "truncate table `".$this->db_prefix."auth_group`";
			$sql_arr[] = "truncate table `".$this->db_prefix."auth_group_access`";
			$sql_arr[] = "truncate table `".$this->db_prefix."auth_rule`";

			//执行清空数据的sql操作
			$i = 0;
			foreach ($sql_arr as $sql ) {
				try {
					M()->execute($sql);
				} catch (Exception $e) {
					$i++;
				}
			}

			//初始化配置分组
			$group_names = array('系统设置','站点设置','奖项设置','会员设置','短信设置','支付设置','邮件设置');
			foreach ($group_names as $group_name) {
				D('ConfigGroup')->add(array("title"=>$group_name));
			}
			//初始化配置(系统设置)
			$this->_initConfigSys('系统设置');
			//初始化配置(站点设置)
			$this->_initConfigWeb('站点设置');
			//初始化配置(奖项设置)
			$this->_initConfigAward('奖项设置');
			//初始化配置(会员设置)
			$this->_initConfigUser('会员设置');
			//初始化配置(短信设置)
			$this->_initConfigMessage('短信设置');
			//初始化配置(支付设置)
			$this->_initConfigPay('支付设置');
			//初始化配置(邮件设置)
			$this->_initConfigMail('邮件设置');
			//初始化会员级别
			$this->_initUserLevel();
			//初始化管理员权限认证
			$this->_initAuth();

			//操作日志
			$this->addLog('进行了配置初始化的操作。');

			$result = array(
    				'status'  => true,
    				'message' => '配置初始化成功!'
			);
			$this->ajaxReturn($result);
		}

		$this->display();
	}

	/**
	 * 数据库备份（手动）
	 *
	 * @since: 2016年12月19日 下午4:13:02
	 * @author: lyx
	 */
	public function backup() {
		//备份文件存储的相对项目的位置
		$savePath = Constants::UPLOAD_ROOT_PATH.Constants::UPLOAD_DATABASE_PATH;
		//post请求，处理数据库备份
		if (IS_POST) {
			$config = array(
    				'host'          => C('DB_HOST'),
    				'port'          => C('DB_PORT'),
    				'userName'      => C('DB_USER'),
    				'userPassword'  => C('DB_PWD'),
    				'dbprefix'      => C('DB_PREFIX'),
    				'savePath'      => $savePath,
    				'charset'       => C('DB_CHARSET')
			);
			set_time_limit(0);
			ini_set("memory_limit",-1);
			$mr = new MySQLReback($config);
			$mr->setDBName(C('DB_NAME'));
			if ($mr->backup()) {
				$result = array(
    					'status'  => true,
    					'message' => '数据库备份成功!'
				);

				//操作日志
				$this->addLog('进行了数据库备份的操作。');
			} else {
				$result = array(
    					'status'  => true,
    					'message' => '数据库备份失败成功!'
				);
			}
			$this->ajaxReturn($result);
		}


		$file_arr = $this->_scanDir($savePath,1);
		$files = array();
		foreach ($file_arr as $i => $file){
			$file_time = date('Y-m-d H:i:s',filemtime($savePath.$file));
			$file_size = filesize($savePath . $file)/1024;

			if ($file_size < 1024){
				$file_size = number_format($file_size,2) . ' KB';
			} else {
				$file_size = '<font class="red">' . number_format($file_size/1024,2) . '</font> MB';
			}
			$files[$i] = array(
    				'path'    => $savePath.$file,
    				'name'    => $file,
    				'time'    => $file_time,
    				'size'    => $file_size,
			);
		}

		$this->assign('files',$files);
		$this->display();
	}

	/**
	 * 数据库备份下载
	 *
	 * @param    string  path    文件地址
	 *
	 * @since: 2016年12月19日 下午5:14:57
	 * @author: lyx
	 */
	public function backupDownload(){
		$path = I('path');
		if ($path) {
			$fileName = $path;
			ob_end_clean();
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Length: ' . filesize($fileName));
			header('Content-Disposition: attachment; filename=' . basename($fileName));
			readfile($fileName);
			exit();
		}
	}

	/**
	 * 数据库备份记录删除
	 *
	 * @param    string  path    文件地址
	 *
	 * @since: 2016年12月19日 下午5:21:17
	 * @author: lyx
	 */
	public function backupDel(){
		//post请求，处理数据库备份删除
		if (IS_POST && I('post.path')) {
			if (unlink(I('post.path'))) {
				$result = array(
    					'status'  => true,
    					'message' => '删除数据库备份文件成功!'
				);

				//操作日志
				$this->addLog('删除了数据库备份。备份文件名称为：' . I('post.file') . '。');
			} else {
				$result = array(
    					'status'  => true,
    					'message' => '删除数据库备份文件失败!'
				);
			}
			$this->ajaxReturn($result);
		}
	}

	/**
	 * 数据库备份还原
	 *
	 * @param    string  path    文件地址
	 *
	 * @since: 2016年12月20日 上午11:01:56
	 * @author: lyx
	 */
	public function backupRestore(){
		//post请求，处理数据库备份还原
		if (IS_POST && I('post.path')) {
			//系统在开启状态下，不允许进行敏感操作，以免引起数据异常
			if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
				$result = array(
    					'status'  => false,
    					'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
				);
				$this->ajaxReturn($result);
			}

			//再次确认密码
			if (!password_verify($_POST['password'], $this->admin['password'])) {
			    $result = array(
			            'status'  => false,
			            'message' => '口令不正确！'
			    );
			    $this->ajaxReturn($result);
			}

			set_time_limit(0);
			ini_set("memory_limit",-1);

			$path = I('post.path');
			$config = array(
    				'host'          => C('DB_HOST'),
    				'port'          => C('DB_PORT'),
    				'userName'      => C('DB_USER'),
    				'userPassword'  => C('DB_PWD'),
    				'dbprefix'      => C('DB_PREFIX'),
    				'charset'       => C('DB_CHARSET')
			);
			$mr = new MySQLReback($config);
			$mr->setDBName(C('DB_NAME'));

			if ($mr->recover($this->sys_config['WEB_DOMAIN'].'/'.$path)) {
				$result = array(
    					'status'  => true,
    					'message' => '数据库还原成功!'
				);

				//操作日志
				$this->addLog('进行了数据库备份还原的操作。');
			} else {
				$result = array(
    					'status'  => true,
    					'message' => '数据库还原失败!'
				);
			}
			$this->ajaxReturn($result);
		}
	}

	/**
	 * 生成数据
	 *
	 * @since: 2016年12月24日 上午9:26:15
	 * @author: xielu
	 */
	public function addTest() {
		if (IS_POST) {
		    //系统在开启状态下，不允许进行敏感操作，以免引起数据异常
		    if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
		        $result = array(
		                'status'  => false,
		                'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
		        );
		        $this->ajaxReturn($result);
		    }

		    //再次确认密码
		    if (!password_verify($_POST['password'], $this->admin['password'])) {
		        $result = array(
		                'status'  => false,
		                'message' => '口令不正确！'
		        );
		        $this->ajaxReturn($result);
		    }

		    //不限制相应时间
		    set_time_limit(0);
		    ini_set("memory_limit",-1);

			$count = D('User')->count();
			$init = D('User')->where(array('id' => 1))->find();
			if ($count != 1 || $init == false) {
				$result = array(
    					'status' => false,
    					'message' => '系统已存在数据，请先初始化数据'
				);
				$this->ajaxReturn($result);
			}
			//会员级别
			$level_where = array(
				    'status' => Constants::NORMAL
			);
			$levels = M('UserLevel')->field('id,title,investment')->where($level_where)->select();

			//服务中心
			if ($this->sys_config['SYSTEM_OPEN_SERVICE_CENTER']) {
				$service_where = array(
					   'status' => Constants::OPERATE_STATUS_CONFIRM
				);
				$service_centers = M('ServiceCenter')->field('user_no')->where($service_where)->select();
			}

			$bar = array(1, 2);
			$n = 1;
			$foo = array('1');

			//生成几层
			while ($n <= $this->sys_config['USER_TEST_DATA_LEVEL']) {
				while (count($foo) != pow(2, $n)) {
					$str = array_shift($foo);
					$parent = D('User')
						->field('user_no,rec_floor')
						->where(array('path' => $str))
						->find();
					$no = $this->sys_config['USER_PREFIX'] . getRandStr(2);
					$t = $str . $bar[0];
					$this->_addData($parent['user_no'], $no, 1, $t, $levels, $service_centers);
					array_push($foo, $t);
					$t = $str . $bar[1];
					$no1 = $this->sys_config['USER_PREFIX'] . getRandStr(2);
					$this->_addData($parent['user_no'], $no1, 2, $t, $levels, $service_centers);
					array_push($foo, $t);
					D('User')->where(array('path' => $str))->save(array('left_no' => $no, 'right_no' => $no1));
				}
				$n++;
			}
			$result = array(
				'status' => true,
				'message' => '生成数据成功!'
			);
			$this->ajaxReturn($result);
		}

		$this->assign('test_data_level', $this->sys_config['USER_TEST_DATA_LEVEL']);
		$this->display();
	}
	/**
	 * 奖金结算
	 *
	 * @since: 2016年12月19日 上午9:26:15
	 * @author: xielu
	 */
	public 	function reward() {
		if (IS_POST) {
		    //系统在开启状态下，不允许进行敏感操作，以免引起数据异常
		    if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
		        $result = array(
		                'status'  => false,
		                'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
		        );
		        $this->ajaxReturn($result);
		    }

		    //不限制相应时间
		    set_time_limit(0);
		    ini_set("memory_limit",-1);

		    //获取未结算的业绩
		    $records = D('MarketRecord')->where(array('status'=>Constants::NO))->select();
		    foreach ($records as $record) {
		        A('Common/Service')->settlement($record);
		    }
		    $result = array(
		            'status'  => true,
		            'message' => '奖金结算成功!'
		    );
		    $this->ajaxReturn($result);
		}

		$this->display();
	}
	/**
	 * 清理垃圾数据
	 *
	 * @since: 2016年12月19日 下午3:51:46
	 * @author: lyx
	 */
	public function cleanUp() {
		echo "清理垃圾数据。。。";
	}

	/**
	 * 日志
	 *
	 * @since: 2017年1月11日 上午9:34:59
	 * @author: lyx
	 */
	public function log() {

		$type = I('type');
		$role = I('role');
		$start_date = I('start_date');
		$end_date = I('end_date');
		$keyword = I('keyword');

		//日志类型
		if ((isset($_GET["type"]) || isset($_POST["type"])) && $type != '-1') {
			$where['type'] = $type;
		}
		//会员类型
		if ((isset($_GET["role"]) || isset($_POST["role"])) && $role != '-1') {
			$where['role'] = $role;
		}
		//时间
		if ($start_date && $end_date) {
			$where['operate_time'] = array(
				array('EGT', $start_date . ' 00:00:00'),
				array('ELT', $end_date . ' 23:59:59')
			) ;
		} elseif ($start_date) {
			$where['operate_time'] = array('EGT', $start_date . ' 00:00:00');
		} elseif ($end_date) {
			$where['operate_time'] = array('ELT', $end_date . ' 23:59:59');
		}
		//关键词
		if ($keyword) {
			$where['username'] = array('like', '%' . $keyword . '%') ;
		}

		//查询数据
		$logs = D('Log')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);


		//返回页面的数据
		$this->assign('list', $logs['data']);
		$this->assign('page', $logs['page']);
		$this->assign('role', $role);
		$this->assign('type', $type);
		$this->assign('start_date', $start_date);
		$this->assign('end_date', $end_date);
		$this->assign('keyword', $keyword);
		$this->display();
	}

	/**
	 * 数据库升级
	 *
	 * @since: 2016年12月13日 下午4:00:06
	 * @author: lyx
	 */
	public function upgrade() {
		//post请求，处理数据库升级
		if (IS_POST) {

			//系统在开启状态下，不允许进行敏感操作，以免引起数据异常
			if ($this->sys_config['WEB_SYSTEM_STATE']==Constants::YES) {
				$result = array(
    					'status'  => false,
    					'message' => '系统在开启状态下，不允许进行敏感操作，以免引起数据异常'
				);
				$this->ajaxReturn($result);
			}

			//不限制相应时间
			set_time_limit(0);
			$sql_arr = array();

			//初始数据库
			$sql_arr = $this->_initDataBase($sql_arr);
			//2016-12-15 config表增加是否允许修改字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."config` ADD COLUMN `is_can_update` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否允许修改。0：不允许；1：允许。默认为1。';";
			//2016-12-19 config表修改value的存储长度
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."config` MODIFY COLUMN `value` VARCHAR(1000) NOT NULL COMMENT '配置项的值';";
			//2016-12-22 account_record表修改amount的描述
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."account_record` MODIFY COLUMN `amount` DECIMAL(10,2) NOT NULL COMMENT '变更金额';";
			//2016-12-28 market_record表增加返还是否结束字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."market_record` ADD COLUMN `return_is_over` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '返还是否结束。0：未结束；1：已结束。默认为0。';";
			//2016-12-30 withdraw表修改name的描述
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."withdraw` MODIFY COLUMN `name` VARCHAR(20) NOT NULL COMMENT '真实姓名';";
			//2016-12-30 remit表修改name的描述
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."remit` MODIFY COLUMN `name` VARCHAR(20) NOT NULL COMMENT '真实姓名';";
			//2016-12-30 remit表修改amount的描述
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."remit` MODIFY COLUMN `amount` DECIMAL(10,2) NOT NULL COMMENT '汇款金额';";
			//2016-12-30 remit表修改add_time的描述
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."remit` MODIFY COLUMN `add_time` DATETIME NOT NULL COMMENT '汇款时间';";
			//2017-01-03  增加bank表，用于银行卡设置
			$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."bank` (
                          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                          `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                          `bank` VARCHAR(20) NOT NULL COMMENT '银行名称',
                          `sub_bank` VARCHAR(20) NOT NULL COMMENT '开户支行',
                          `bank_no` VARCHAR(20) NOT NULL COMMENT '银行卡号',
                          `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认。0：常规银行卡；1：默认银行卡。默认为0',
                          `add_time` DATETIME NOT NULL COMMENT '添加时间',
                          `update_time` DATETIME NOT NULL COMMENT '修改时间',
                          PRIMARY KEY (`id`))
                        ENGINE = InnoDB
                        COMMENT = '会员银行卡表';";
			//2017-01-03  会员扩展表删除银行卡设置信息
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user_extend` DROP COLUMN `bank`;";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user_extend` DROP COLUMN `sub_bank`;";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user_extend` DROP COLUMN `bank_no`;";
			//2017-01-06 短信表删除内容字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."message` DROP COLUMN `content`;";
			//2017-01-06 短信表增加参数内容和模板id字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."message` ADD COLUMN `param` VARCHAR(255) NOT NULL COMMENT '模板参数';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."message` ADD COLUMN `template` VARCHAR(50) NOT NULL COMMENT '模板id';";
			//2017-01-06  增加pay表，用于对接第三方融宝
			$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."pay` (
                          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
    		              `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                          `order_no` VARCHAR(20) NOT NULL COMMENT '支付号',
                          `trade_no` VARCHAR(20) NOT NULL COMMENT '交易流水号',
    		              `amount` DECIMAL(10,2) NOT NULL COMMENT '订单金额',
    		              `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态。0：等待支付；1：支付成功；2：订单关闭。默认为0',
    		              `title` VARCHAR(20) NOT NULL COMMENT '支付订单标题',
    		              `body` VARCHAR(400) NOT NULL COMMENT '支付订单详细',
    		              `extend` VARCHAR(200) NOT NULL COMMENT '扩展字段（json）',
    		              `terminal_type` VARCHAR(20) NOT NULL COMMENT '终端类型',
    		              `member_ip` VARCHAR(20) NOT NULL COMMENT '会员当前ip地址',
    		              `exception` VARCHAR(200) NOT NULL COMMENT '发送异常描述（json）',
    		              `add_time` DATETIME NOT NULL COMMENT '添加时间',
                          `success_time` DATETIME NOT NULL COMMENT '支付成功时间',
                          PRIMARY KEY (`id`))
                        ENGINE = InnoDB
                        COMMENT = '支付订单表';";
			$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `trade_no_UNIQUE` ON `".$this->db_prefix."pay` (`trade_no` ASC);";
			$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `order_no_UNIQUE` ON `".$this->db_prefix."pay` (`order_no` ASC);";
			//2017-01-10 log表修改remark的存储长度
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."log` MODIFY COLUMN `remark` VARCHAR(500) NOT NULL COMMENT '备注';";
			//2017-01-13 log表修改remark的存储长度
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."pay` MODIFY COLUMN `trade_no` VARCHAR(20) NULL COMMENT '交易流水号';";
			//2017-02-13 auth_code表增加email字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."auth_code` ADD COLUMN `email` VARCHAR(50) NOT NULL COMMENT 'email';";
			//2017-02-15  增加layer_touch表，用于层碰奖处理
			$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."layer_touch` (
                            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
			                `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
			                `floor` TINYINT UNSIGNED NOT NULL COMMENT '层数',
			                `left_no` VARCHAR(20) NOT NULL COMMENT '左区新业绩来源编号',
                            `right_no` VARCHAR(20) NOT NULL COMMENT '右区新业绩来源编号',
			                `left_market` DECIMAL(10,2) NOT NULL COMMENT '左区业绩',
                            `right_market` DECIMAL(10,2) NOT NULL COMMENT '右区业绩',
			                `update_time` DATETIME NOT NULL COMMENT '最后操作时间',
                          PRIMARY KEY (`id`))
                        ENGINE = InnoDB
                        COMMENT = '用户层碰奖记录表';";
			//2017-02-15 user_level表增加layer_touch_award字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user_level` ADD COLUMN `layer_touch_award` DECIMAL(4,1) NOT NULL COMMENT '层碰奖（单位是%）';";
			//2017-02-16 admin表修改password的存储长度
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."admin` MODIFY COLUMN `password` VARCHAR(100) NOT NULL COMMENT '密码';";
			//2017-02-16 user表修改password的存储长度
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user` MODIFY COLUMN `password` VARCHAR(100) NOT NULL COMMENT '密码';";
			//2017-02-16 user表修改two_password的存储长度
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user` MODIFY COLUMN `two_password` VARCHAR(100) NOT NULL COMMENT '安全码';";
			//2017-02-23 log表增加相关扩展信息字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."log` ADD COLUMN `extend` VARCHAR(100) NULL COMMENT '相关扩展信息';";
			//2017-02-24 reward_record表修改type字段的描述
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."reward_record` MODIFY COLUMN `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖项。0：对碰奖；1：推荐奖；2：领导奖；3：见点奖；4：报单奖；5：层奖；6：层碰奖。默认为0';";
			//2017-03-09 user_level表修改layer_touch_award字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user_level` MODIFY COLUMN `layer_touch_award` VARCHAR(200) NOT NULL COMMENT '层碰奖（json串）';";
			//2017-03-10 bank表修添加字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `name` varchar(16) COMMENT '用户姓名';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `phone` varchar(16) COMMENT '用户在银行预留手机号';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `cert_type` tinyint(1) DEFAULT 0 COMMENT '0储蓄卡，1信用卡';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `cert_no` char(32)  COMMENT '用户填写的证件号码';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `cvv2` smallint(3)  COMMENT '信用卡背后的3位数字';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `validthru` char(4)  COMMENT '月年格式';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `bind_id` char(32)  COMMENT '签约时返回的绑卡ID';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `type` tinyint(1) DEFAULT 0  COMMENT '银行卡类型 0 普通卡，1签约';";
			//2017-04-01 withdraw表增加手续费字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."withdraw` ADD COLUMN `tax_amount` decimal(10,2) DEFAULT 0  COMMENT '手续费';";
			//2017-04-01 withdraw表增加到账金额字段
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."withdraw` ADD COLUMN `actual_amount` decimal(10,2) DEFAULT 0  COMMENT '到账金额';";
			//2017-05-18 goods表修改goods_categor_id字段名称
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."goods` change COLUMN `goods_categor_id` `goods_category_id` SMALLINT UNSIGNED NOT NULL COMMENT '分类标识';";
			//2017-07-24 layer_touch表修改floor的字段类型
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."layer_touch` MODIFY COLUMN `floor` INT UNSIGNED NOT NULL COMMENT '层数';";
			//2017-07-24 user表修改floor的字段类型
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user` MODIFY COLUMN `floor` INT UNSIGNED NOT NULL COMMENT '层数';";
			//2017-07-24 user表修改rec_floor的字段类型
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user` MODIFY COLUMN `rec_floor` INT UNSIGNED NOT NULL COMMENT '代数';";
			//2017-07-24 parent_nexus表修改floor的字段类型
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."parent_nexus` MODIFY COLUMN `floor` INT UNSIGNED NOT NULL COMMENT '层级';";
			//2017-07-24 recommend_nexus表修改floor的字段类型
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."recommend_nexus` MODIFY COLUMN `rec_floor` INT UNSIGNED NOT NULL COMMENT '代级';";
			//2017-08-01 user表修改path的字段类型
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."user` MODIFY COLUMN `path` TEXT NOT NULL COMMENT '节点路径';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."pay` MODIFY COLUMN `trade_no` VARCHAR(32) NULL COMMENT '交易流水号';";
			$sql_arr[] = "ALTER TABLE `".$this->db_prefix."pay` ADD COLUMN `pay_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付类型';";
			$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."message_template` (
                            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
			                `no` VARCHAR(20) NOT NULL COMMENT '模板编号',
			                `msg` text COMMENT '模板内容',
			                `add_time` DATETIME NOT NULL COMMENT '添加时间',
			                `update_time` DATETIME NOT NULL COMMENT '最后操作时间',
                          PRIMARY KEY (`id`))
                        ENGINE = InnoDB
                        COMMENT = '短信模板表';";
            $sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."sms_code` (
                      `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `name` varchar(64) NOT NULL COMMENT '国家或区域名称',
                      `chinese_name` varchar(64) NOT NULL COMMENT '国家或区域名称(中文)',
                      `code` varchar(4) NOT NULL COMMENT '短信区号',
                      `is_default` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认。0：否；1：是。默认为0',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB COMMENT='国际短信区号表';";
            $sql_arr[] = "ALTER TABLE `".$this->db_prefix."user` ADD COLUMN `sms_code` int DEFAULT '86' COMMENT '手机号国际码';";
            $sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."bank_type` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `title` VARCHAR(20) NOT NULL COMMENT '银行名称',
                      `sort` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '银行类型表';";
            $sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `subbank_province` VARCHAR(20) NOT NULL COMMENT '省份';";
            $sql_arr[] = "ALTER TABLE `".$this->db_prefix."bank` ADD COLUMN `subbank_city` VARCHAR(20) NOT NULL COMMENT '市级';";
            $sql_arr[] = "ALTER TABLE `".$this->db_prefix."withdraw` ADD COLUMN `subbank_province` VARCHAR(20) NOT NULL COMMENT '省份';";
            $sql_arr[] = "ALTER TABLE `".$this->db_prefix."withdraw` ADD COLUMN `subbank_city` VARCHAR(20) NOT NULL COMMENT '市级';";

			$i = 0;
			foreach ($sql_arr as $sql ) {
				try {
					M()->execute($sql);
				} catch (Exception $e) {
					$i++;
				}
			}

			//操作日志
			$this->addLog('进行了数据库升级的操作。');

			$result = array(
    				'status'  => true,
    				'message' => '数据库升级成功!'
			);
			$this->ajaxReturn($result);
		}

		$this->display();
	}

	/**
	 * 获取指定目录下的文件列表
	 *
	 * @param    string  $file_path  文件夹的目录
	 * @param    int     $order      文件排序（0，表示升序；1，表示降序）
	 * @return   array               文件数组
	 *
	 * @since: 2016年12月19日 下午4:42:12
	 * @author: lyx
	 */
	private function _scanDir($file_path='./',$order=0){
		if(!is_dir($file_path)) mkdir($file_path);
		$file_path = opendir($file_path);

		while (false !== ($filename = readdir($file_path))) {
			if($filename=='.' || $filename=='..'){
				continue;
			}
			$file_folder_arr[] = $filename;
		}
		$order == 0 ? sort($file_folder_arr) : rsort($file_folder_arr);
		return $file_folder_arr;
	}

	/**
	 * 初始化管理员权限认证
	 *
	 * @since: 2016年12月15日 上午11:35:56
	 * @author: lyx
	 */
	private function _initAuth() {

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('1', '0', 'Admin/User', '会员管理', '1', '1', '', '2017-01-10 14:43:27', '2017-01-10 15:31:30');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('2', '0', 'Admin/Mall/index', '商城管理', '1', '1', '', '2017-01-10 14:44:01', '2017-01-10 14:44:01');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('3', '0', 'Admin/Account/giro', '快捷操作', '1', '1', '', '2017-01-10 14:44:27', '2017-01-10 14:44:27');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('4', '0', 'Admin/Finance', '财务明细', '1', '1', '', '2017-01-10 14:45:10', '2017-01-10 14:45:10');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('5', '0', 'Admin/mail', '站内信管理', '1', '1', '', '2017-01-10 14:45:42', '2017-01-10 14:45:42');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('6', '0', 'Admin/News', '信息管理', '1', '1', '', '2017-01-10 14:46:35', '2017-01-10 14:46:35');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('7', '0', 'Admin/System', '系统维护', '1', '1', '', '2017-01-10 14:47:06', '2017-01-10 14:47:06');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('8', '7', 'Admin/Config/index', '系统参数设置', '1', '1', '', '2017-01-10 14:47:39', '2017-01-10 14:47:39');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('9', '7', 'Admin/Level/index', '级别设置', '1', '1', '', '2017-01-10 14:48:08', '2017-01-10 14:48:08');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('10', '7', 'Admin/Data/index', '数据维护', '1', '1', '', '2017-01-10 14:48:29', '2017-01-10 14:48:29');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('11', '10', 'Admin/Data/upgrade', '数据库升级', '1', '1', '', '2017-01-10 14:49:05', '2017-01-10 14:49:05');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('12', '10', 'Admin/Data/init', '初始化数据', '1', '1', '', '2017-01-10 14:49:51', '2017-01-10 14:49:51');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('13', '10', 'Admin/Data/initConfig', '初始化配置', '1', '1', '', '2017-01-10 14:50:20', '2017-01-10 14:50:20');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('14', '10', 'Admin/Data/addTest', '模拟数据', '1', '1', '', '2017-01-10 14:50:43', '2017-01-10 14:50:43');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('16', '7', 'Admin/Data/backup', '数据库备份', '1', '1', '', '2017-01-10 14:51:31', '2017-01-10 14:51:31');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('17', '16', 'Admin/Data/backupRestore', '数据库还原', '1', '1', '', '2017-01-10 14:52:21', '2017-01-10 14:52:21');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('18', '16', 'Admin/Data/backupDel', '备份删除', '1', '1', '', '2017-01-10 14:52:58', '2017-01-10 14:52:58');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('19', '9', 'Admin/Level/set', '级别参数设置', '1', '1', '', '2017-01-10 14:53:35', '2017-01-10 14:53:35');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('20', '9', 'Admin/Level/setStatus', '级别开启/关闭', '1', '1', '', '2017-01-10 14:53:52', '2017-01-10 14:53:52');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('21', '8', 'Admin/Config/system', '基础设置', '1', '1', '', '2017-01-10 14:55:01', '2017-01-10 14:55:01');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('22', '8', 'Admin/Config/website', '站点设置', '1', '1', '', '2017-01-10 14:55:22', '2017-01-10 14:55:22');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('23', '8', 'Admin/Config/user', '会员设置', '1', '1', '', '2017-01-10 14:55:42', '2017-01-10 14:55:42');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('24', '8', 'Admin/Config/award', '奖项设置', '1', '1', '', '2017-01-10 14:56:00', '2017-01-10 14:56:00');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('25', '8', 'Admin/Config/pay', '支付设置', '1', '1', '', '2017-01-10 14:56:23', '2017-01-10 14:56:23');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('26', '8', 'Admin/Config/message', '短信设置', '1', '1', '', '2017-01-10 14:58:43', '2017-01-10 14:58:43');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('27', '0', 'Admin/Auth', '权限管理', '1', '1', '', '2017-01-10 14:59:13', '2017-01-10 14:59:13');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('28', '27', 'Admin/Admin/index', '管理员列表', '1', '1', '', '2017-01-10 15:00:02', '2017-01-10 15:00:02');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('29', '27', 'Admin/Rule/roleList', '角色管理', '1', '1', '', '2017-01-10 15:00:24', '2017-01-10 15:00:24');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('30', '29', 'Admin/Rule/addRole', '添加角色', '1', '1', '', '2017-01-10 15:00:47', '2017-01-10 15:00:47');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('31', '29', 'Admin/Rule/editRole', '修改角色', '1', '1', '', '2017-01-10 15:01:02', '2017-01-10 15:01:02');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('32', '29', 'Admin/Rule/delRole', '删除角色', '1', '1', '', '2017-01-10 15:01:20', '2017-01-10 15:01:20');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('33', '28', 'Admin/Admin/add', '添加管理员', '1', '1', '', '2017-01-10 15:01:45', '2017-01-10 15:01:45');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('34', '28', 'Admin/Admin/edit', '修改管理员', '1', '1', '', '2017-01-10 15:02:03', '2017-01-10 15:02:03');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('35', '28', 'Admin/Admin/del', '删除管理员', '1', '1', '', '2017-01-10 15:02:19', '2017-01-10 15:02:19');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('36', '3', 'Admin/Account/giro/type/0', '快捷充值', '1', '1', '', '2017-01-10 15:03:15', '2017-01-10 15:03:15');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('37', '3', 'Admin/Account/giro/type/1', '快捷扣款', '1', '1', '', '2017-01-10 15:03:56', '2017-01-10 15:03:56');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('38', '36', 'Admin/Account/ebRecharge', '注册币充值', '1', '1', '', '2017-01-10 15:04:30', '2017-01-10 15:04:30');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('39', '36', 'Admin/Account/tbRecharge', '购物币充值', '1', '1', '', '2017-01-10 15:04:45', '2017-01-10 15:04:45');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('40', '36', 'Admin/Account/cbRecharge', '现金充值', '1', '1', '', '2017-01-10 15:04:58', '2017-01-10 15:04:58');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('41', '36', 'Admin/Account/mbRecharge', '奖金充值', '1', '1', '', '2017-01-10 15:05:14', '2017-01-10 15:05:14');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('42', '36', 'Admin/Account/rbRecharge', '返利充值', '1', '1', '', '2017-01-10 15:05:29', '2017-01-10 15:05:29');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('43', '37', 'Admin/Account/ebDeduct', '注册币扣款', '1', '1', '', '2017-01-10 15:06:04', '2017-01-10 15:06:04');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('44', '37', 'Admin/Account/tbDeduct', '购物币扣款', '1', '1', '', '2017-01-10 15:06:23', '2017-01-10 15:06:23');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('45', '37', 'Admin/Account/cbDeduct', '现金扣款', '1', '1', '', '2017-01-10 15:06:49', '2017-01-10 15:06:49');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('46', '37', 'Admin/Account/mbDeduct', '奖金扣款', '1', '1', '', '2017-01-10 15:07:04', '2017-01-10 15:07:04');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('47', '37', 'Admin/Account/rbDeduct', '返利扣款', '1', '1', '', '2017-01-10 15:07:20', '2017-01-10 15:07:20');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('48', '4', 'Admin/Cash/payList', '充值管理', '1', '1', '', '2017-01-10 15:10:30', '2017-01-10 15:11:51');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('49', '4', 'Admin/Cash/withdrawList', '提现管理', '1', '1', '', '2017-01-10 15:11:19', '2017-01-10 15:11:19');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('50', '4', 'Admin/Cash/remitList', '汇款管理', '1', '1', '', '2017-01-10 15:12:11', '2017-01-10 15:12:25');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('51', '4', 'Admin/Account', '财务相关查询', '1', '1', '', '2017-01-10 15:12:47', '2017-01-10 15:13:29');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('52', '51', 'Admin/Account/giroList', '转账查询', '1', '1', '', '2017-01-10 15:13:13', '2017-01-10 15:13:13');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('53', '51', 'Admin/Account/marketList', '业绩查询', '1', '1', '', '2017-01-10 15:14:09', '2017-01-10 15:14:31');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('54', '51', 'Admin/Account/rewardList', '奖金查询', '1', '1', '', '2017-01-10 15:14:49', '2017-01-10 15:14:49');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('55', '51', 'Admin/Account/returnList', '返本查询', '1', '1', '', '2017-01-10 15:15:01', '2017-01-10 15:15:01');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('56', '51', 'Admin/Account/index', '流水账查询', '1', '1', '', '2017-01-10 15:15:24', '2017-01-10 15:15:24');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('57', '50', 'Admin/Cash/confirmRemit', '汇款同意', '1', '1', '', '2017-01-10 15:16:09', '2017-01-10 15:16:09');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('58', '50', 'Admin/Cash/rejectRemit', '汇款驳回', '1', '1', '', '2017-01-10 15:16:28', '2017-01-10 15:16:28');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('59', '49', 'Admin/Cash/confirmWithdraw', '提现同意', '1', '1', '', '2017-01-10 15:16:50', '2017-01-10 15:16:50');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('60', '49', 'Admin/Cash/rejectWithdraw', '提现驳回', '1', '1', '', '2017-01-10 15:17:05', '2017-01-10 15:17:05');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('61', '10', 'Admin/Data/log', '监控日志', '1', '1', '', '2017-01-11 10:09:23', '2017-01-11 10:09:23');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('62', '4', 'Admin/Cash/bankList', '银行卡管理', '1', '1', '', '2017-01-11 10:33:05', '2017-01-11 10:33:05');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('63', '62', 'Admin/Cash/editBank', '修改银行卡', '1', '1', '', '2017-01-11 10:33:42', '2017-01-11 10:33:42');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('64', '62', 'Admin/Cash/delBank', '删除银行卡', '1', '1', '', '2017-01-11 10:34:22', '2017-01-11 10:34:22');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('65', '48', 'Admin/Cash/paymentQuery', '充值查询', '1', '1', '', '2017-01-14 11:24:50', '2017-01-14 11:24:50');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('66', '4', 'Admin/Report/index', '统计报表', '1', '1', '', '2017-01-14 18:14:03', '2017-01-14 18:14:03');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('67', '66', 'Admin/Report/market', '业绩报表', '1', '1', '', '2017-01-14 18:14:41', '2017-01-14 18:14:41');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('68', '66', 'Admin/Report/user', '会员报表', '1', '1', '', '2017-01-14 18:15:02', '2017-01-14 18:15:02');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('69', '66', 'Admin/Report/reward', '奖金报表', '1', '1', '', '2017-01-14 18:15:32', '2017-01-14 18:15:32');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('70', '66', 'Admin/Report/inOut', '收支比图表', '1', '1', '', '2017-01-14 18:16:07', '2017-01-16 10:50:34');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('71', '1', 'Admin/User/index', '会员管理', '1', '1', '', '2017-01-21 14:44:19', '2017-01-21 14:44:19');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('72', '1', 'Admin/User/placeSystem', '安置网络', '1', '1', '', '2017-01-21 14:45:11', '2017-01-21 14:45:11');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('73', '1', 'Admin/User/recommendSystem', '推荐网络', '1', '1', '', '2017-01-21 14:45:41', '2017-01-21 14:45:41');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('74', '1', 'Admin/User/center', '报单中心管理', '1', '1', '', '2017-01-21 14:46:00', '2017-01-21 14:46:00');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('75', '71', 'Admin/User/edit', '修改资料', '1', '1', '', '2017-01-21 14:46:31', '2017-01-21 14:46:31');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('76', '71', 'Admin/User/resetPassword', '重置密码', '1', '1', '', '2017-01-21 14:47:04', '2017-01-21 14:47:04');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('77', '71', 'Admin/User/setLock', '会员解锁/锁定', '1', '1', '', '2017-01-21 14:47:38', '2017-01-21 14:47:38');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('78', '71', 'Admin/User/userActivate', '会员激活', '1', '1', '', '2017-01-21 14:48:01', '2017-01-21 14:48:01');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('79', '71', 'Admin/User/userDelete', '会员删除', '1', '1', '', '2017-01-21 14:48:26', '2017-01-21 14:48:26');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('80', '71', 'Admin/User/login', '登录会员前台', '1', '1', '', '2017-01-21 14:49:01', '2017-01-21 14:49:01');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('81', '74', 'Admin/User/confirmServiceCenter', '申请同意', '1', '1', '', '2017-01-21 14:49:34', '2017-01-21 14:49:34');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('82', '74', 'Admin/User/rejectServiceCenter', '申请驳回', '1', '1', '', '2017-01-21 14:49:55', '2017-01-21 14:49:55');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('83', '6', 'Admin/News/categoryList', '公告分类管理', '1', '1', '', '2017-01-22 14:43:54', '2017-01-22 14:44:14');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('84', '6', 'Admin/News/index', '公告管理', '1', '1', '', '2017-01-22 14:44:32', '2017-01-22 14:44:32');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('85', '83', 'Admin/News/addCategory', '增加公告分类', '1', '1', '', '2017-01-22 14:44:59', '2017-01-22 14:44:59');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('86', '83', 'Admin/News/editCategory', '修改公告分类', '1', '1', '', '2017-01-22 14:45:26', '2017-01-22 14:45:26');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('87', '83', 'Admin/News/deleteCategory', '删除公告分类', '1', '1', '', '2017-01-22 14:45:49', '2017-01-22 14:45:49');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('88', '84', 'Admin/News/add', '增加公告', '1', '1', '', '2017-01-22 14:46:12', '2017-01-22 14:46:12');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('89', '84', 'Admin/News/edit', '修改公告', '1', '1', '', '2017-01-22 14:46:38', '2017-01-22 14:46:38');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('90', '84', 'Admin/News/del', '删除公告', '1', '1', '', '2017-01-22 14:47:02', '2017-01-22 14:47:02');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('91', '5', 'Admin/Mail/index', '站内信列表', '1', '1', '', '2017-01-22 17:32:32', '2017-01-22 17:36:22');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('92', '5', 'Admin/Message/index', '短信发送查看', '1', '1', '', '2017-01-22 17:33:18', '2017-01-22 17:33:18');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('93', '91', 'Admin/Mail/add', '发站内信', '1', '1', '', '2017-01-22 17:33:54', '2017-01-22 17:33:54');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('94', '91', 'Admin/Mail/outbox', '发件箱', '1', '1', '', '2017-01-22 17:34:39', '2017-01-22 17:35:15');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('95', '91', 'Admin/Mail/inbox', '收件箱', '1', '1', '', '2017-01-22 17:35:03', '2017-01-22 17:35:03');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('96', '91', 'Admin/Mail/unread', '未读邮件查看', '1', '1', '', '2017-01-22 17:35:51', '2017-01-22 17:35:51');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('97', '8', 'Admin/Config/mail', '邮件设置', '1', '1', '', '2017-02-15 13:47:32', '2017-02-15 13:47:32');";

		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('98', '2', 'Admin/Mall/categoryList', '商品分类', '1', '1', '', '2017-02-15 13:47:32', '2017-02-15 13:47:32');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('100', '2', 'Admin/Mall/goodsList', '商品管理', '1', '1', '', '2017-02-15 13:47:32', '2017-02-15 13:47:32');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('101', '2', 'Admin/Mall/orderList', '订单管理', '1', '1', '', '2017-02-15 13:47:32', '2017-02-15 13:47:32');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('102', '2', 'Admin/Mall/addrList', '地址管理', '1', '1', '', '2017-02-15 13:47:32', '2017-02-15 13:47:32');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('103', '98', 'Admin/Mall/addClassify', '新增商品分类', '1', '1', '', '2017-03-24 17:51:42', '2017-03-24 17:51:42');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('104', '98', 'Admin/Mall/editClassify', '修改商品分类', '1', '1', '', '2017-03-24 17:52:03', '2017-03-24 17:52:03');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('105', '98', 'Admin/Mall/deleteGoodsCategory', '删除商品分类', '1', '1', '', '2017-03-24 17:52:43', '2017-03-24 17:52:43');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('106', '98', 'Admin/Mall/openOrClose', '关闭/开启商品分类', '1', '1', '', '2017-03-24 17:53:14', '2017-03-24 17:53:14');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('107', '100', 'Admin/Mall/addGoods', '添加商品', '1', '1', '', '2017-03-24 17:53:47', '2017-03-24 17:53:47');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('108', '100', 'Admin/Mall/editGoods', '修改商品', '1', '1', '', '2017-03-24 17:57:09', '2017-03-24 17:57:09');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('109', '100', 'Admin/Mall/changesInventory', '变更商品库存', '1', '1', '', '2017-03-24 17:57:47', '2017-03-24 17:57:47');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('110', '100', 'Admin/Mall/putawayOrSoldout', '上/下架商品', '1', '1', '', '2017-03-24 17:58:22', '2017-03-24 17:58:22');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('111', '102', 'Admin/Mall/addAddr', '修改地址信息', '1', '1', '', '2017-03-24 17:59:20', '2017-03-24 17:59:20');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('112', '102', 'Admin/Mall/addressDelete', '删除地址信息', '1', '1', '', '2017-03-24 17:59:44', '2017-03-24 17:59:44');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('113', '101', 'Admin/Mall/saveLogistics', '确认订单', '1', '1', '', '2017-03-24 18:02:15', '2017-03-24 18:02:15');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('114', '101', 'Admin/Mall/saveTurndown', '驳回订单', '1', '1', '', '2017-03-24 18:02:54', '2017-03-24 18:02:54');";
		$sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('115', '8', 'Admin/Config/smsTemplate', '短信模板', '1', '1', '', '2017-01-10 14:58:43', '2017-01-10 14:58:43');";
        $sql_arr[] = "INSERT INTO `" . $this->db_prefix . "auth_rule` VALUES ('116', '4', 'Admin/Cash/bank', '银行管理', '1', '1', '', '2017-01-11 10:33:05', '2017-01-11 10:33:05');";

        // 国际码数据
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('1', 'Angola', '安哥拉', '244', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('2', 'Afghanistan', '阿富汗', '93', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('3', 'Albania', '阿尔巴尼亚', '355', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('4', 'Algeria', '阿尔及利亚', '213', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('5', 'Andorra', '安道尔共和国', '376', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('6', 'Anguilla', '安圭拉岛', '1264', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('7', 'Antigua and Barbuda', '安提瓜和巴布达', '1268', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('8', 'Argentina', '阿根廷', '54', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('9', 'Armenia', '亚美尼亚', '374', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('10', 'Ascension', '阿森松', '247', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('11', 'Australia', '澳大利亚', '61', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('12', 'Austria', '奥地利', '43', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('13', 'Azerbaijan', '阿塞拜疆', '994', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('14', 'Bahamas', '巴哈马', '1242', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('15', 'Bahrain', '巴林', '973', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('16', 'Bangladesh', '孟加拉国', '880', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('17', 'Barbados', '巴巴多斯', '1246', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('18', 'Belarus', '白俄罗斯', '375', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('19', 'Belgium', '比利时', '32', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('20', 'Belize', '伯利兹', '501', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('21', 'Benin', '贝宁', '229', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('22', 'BermudaIs.', '百慕大群岛', '1441', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('23', 'Bolivia', '玻利维亚', '591', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('24', 'Botswana', '博茨瓦纳', '267', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('25', 'Brazil', '巴西', '55', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('26', 'Brunei', '文莱', '673', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('27', 'Bulgaria', '保加利亚', '359', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('28', 'Burkina-faso', '布基纳法索', '226', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('29', 'Burma', '缅甸', '95', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('30', 'Burundi', '布隆迪', '257', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('31', 'Cameroon', '喀麦隆', '237', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('32', 'Canada', '加拿大', '1', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('33', 'Cayman Is.', '开曼群岛', '1345', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('34', 'Central African Republic', '中非共和国', '236', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('35', 'Chad', '乍得', '235', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('36', 'Chile', '智利', '56', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('37', 'China', '中国', '86', '1');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('38', 'Colombia', '哥伦比亚', '57', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('39', 'Congo', '刚果', '242', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('40', 'Cook Is.', '库克群岛', '682', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('41', 'Costa Rica', '哥斯达黎加', '506', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('42', 'Cuba', '古巴', '53', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('43', 'Cyprus', '塞浦路斯', '357', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('44', 'Czech Republic', '捷克', '420', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('45', 'Denmark', '丹麦', '45', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('46', 'Djibouti', '吉布提', '253', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('47', 'Dominica Rep.', '多米尼加共和国', '1890', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('48', 'Ecuador', '厄瓜多尔', '593', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('49', 'Egypt', '埃及', '20', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('50', 'EISalvador', '萨尔瓦多', '503', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('51', 'Estonia', '爱沙尼亚', '372', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('52', 'Ethiopia', '埃塞俄比亚', '251', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('53', 'Fiji', '斐济', '679', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('54', 'Finland', '芬兰', '358', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('55', 'France', '法国', '33', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('56', 'French Guiana', '法属圭亚那', '594', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('57', 'Gabon', '加蓬', '241', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('58', 'Gambia', '冈比亚', '220', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('59', 'Georgia', '格鲁吉亚', '995', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('60', 'Germany', '德国', '49', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('61', 'Ghana', '加纳', '233', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('62', 'Gibraltar', '直布罗陀', '350', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('63', 'Greece', '希腊', '30', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('64', 'Grenada', '格林纳达', '1809', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('65', 'Guam', '关岛', '1671', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('66', 'Guatemala', '危地马拉', '502', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('67', 'Guinea', '几内亚', '224', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('68', 'Guyana', '圭亚那', '592', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('69', 'Haiti', '海地', '509', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('70', 'Honduras', '洪都拉斯', '504', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('71', 'Hongkong', '香港', '852', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('72', 'Hungary', '匈牙利', '36', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('73', 'Iceland', '冰岛', '354', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('74', 'India', '印度', '91', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('75', 'Indonesia', '印度尼西亚', '62', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('76', 'Iran', '伊朗', '98', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('77', 'Iraq', '伊拉克', '964', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('78', 'Ireland', '爱尔兰', '353', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('79', 'Israel', '以色列', '972', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('80', 'Italy', '意大利', '39', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('81', 'IvoryCoast', '科特迪瓦', '225', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('82', 'Jamaica', '牙买加', '1876', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('83', 'Japan', '日本', '81', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('84', 'Jordan', '约旦', '962', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('85', 'Kampuchea (Cambodia )', '柬埔寨', '855', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('86', 'Kazakstan', '哈萨克斯坦', '327', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('87', 'Kenya', '肯尼亚', '254', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('88', 'Korea', '韩国', '82', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('89', 'Kuwait', '科威特', '965', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('90', 'Kyrgyzstan', '吉尔吉斯坦', '331', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('91', 'Laos', '老挝', '856', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('92', 'Latvia', '拉脱维亚', '371', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('93', 'Lebanon', '黎巴嫩', '961', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('94', 'Lesotho', '莱索托', '266', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('95', 'Liberia', '利比里亚', '231', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('96', 'Libya', '利比亚', '218', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('97', 'Liechtenstein', '列支敦士登', '423', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('98', 'Lithuania', '立陶宛', '370', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('99', 'Luxembourg', '卢森堡', '352', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('100', 'Macao', '澳门', '853', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('101', 'Madagascar', '马达加斯加', '261', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('102', 'Malawi', '马拉维', '265', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('103', 'Malaysia', '马来西亚', '60', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('104', 'Maldives', '马尔代夫', '960', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('105', 'Mali', '马里', '223', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('106', 'Malta', '马耳他', '356', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('107', 'Mariana Is', '马里亚那群岛', '1670', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('108', 'Martinique', '马提尼克', '596', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('109', 'Mauritius', '毛里求斯', '230', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('110', 'Mexico', '墨西哥', '52', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('111', 'Moldova, Republic of', '摩尔多瓦', '373', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('112', 'Monaco', '摩纳哥', '377', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('113', 'Mongolia', '蒙古', '976', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('114', 'Montserrat Is', '蒙特塞拉特岛', '1664', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('115', 'Morocco', '摩洛哥', '212', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('116', 'Mozambique', '莫桑比克', '258', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('117', 'Namibia', '纳米比亚', '264', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('118', 'Nauru', '瑙鲁', '674', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('119', 'Nepal', '尼泊尔', '977', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('120', 'Netheriands Antilles', '荷属安的列斯', '599', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('121', 'Netherlands', '荷兰', '31', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('122', 'NewZealand', '新西兰', '64', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('123', 'Nicaragua', '尼加拉瓜', '505', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('124', 'Niger', '尼日尔', '227', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('125', 'Nigeria', '尼日利亚', '234', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('126', 'North Korea', '朝鲜', '850', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('127', 'Norway', '挪威', '47', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('128', 'Oman', '阿曼', '968', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('129', 'Pakistan', '巴基斯坦', '92', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('130', 'Panama', '巴拿马', '507', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('131', 'Papua New Cuinea', '巴布亚新几内亚', '675', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('132', 'Paraguay', '巴拉圭', '595', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('133', 'Peru', '秘鲁', '51', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('134', 'Philippines', '菲律宾', '63', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('135', 'Poland', '波兰', '48', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('136', 'French Polynesia', '法属玻利尼西亚', '689', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('137', 'Portugal', '葡萄牙', '351', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('138', 'PuertoRico', '波多黎各', '1787', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('139', 'Qatar', '卡塔尔', '974', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('140', 'Reunion', '留尼旺', '262', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('141', 'Romania', '罗马尼亚', '40', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('142', 'Russia', '俄罗斯', '7', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('143', 'Saint Lueia', '圣卢西亚', '1758', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('144', 'Saint Vincent', '圣文森特岛', '1784', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('145', 'Samoa Eastern', '东萨摩亚(美)', '684', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('146', 'Samoa Western', '西萨摩亚', '685', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('147', 'San Marino', '圣马力诺', '378', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('148', 'Sao Tome and Principe', '圣多美和普林西比', '239', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('149', 'Saudi Arabia', '沙特阿拉伯', '966', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('150', 'Senegal', '塞内加尔', '221', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('151', 'Seychelles', '塞舌尔', '248', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('152', 'Sierra Leone', '塞拉利昂', '232', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('153', 'Singapore', '新加坡', '65', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('154', 'Slovakia', '斯洛伐克', '421', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('155', 'Slovenia', '斯洛文尼亚', '386', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('156', 'Solomon Is', '所罗门群岛', '677', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('157', 'Somali', '索马里', '252', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('158', 'South Africa', '南非', '27', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('159', 'Spain', '西班牙', '34', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('160', 'Sri Lanka', '斯里兰卡', '94', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('161', 'St.Lucia', '圣卢西亚', '1758', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('162', 'St.Vincent', '圣文森特', '1784', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('163', 'Sudan', '苏丹', '249', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('164', 'Suriname', '苏里南', '597', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('165', 'Swaziland', '斯威士兰', '268', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('166', 'Sweden', '瑞典', '46', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('167', 'Switzerland', '瑞士', '41', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('168', 'Syria', '叙利亚', '963', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('169', 'Taiwan', '台湾省', '886', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('170', 'Tajikstan', '塔吉克斯坦', '992', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('171', 'Tanzania', '坦桑尼亚', '255', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('172', 'Thailand', '泰国', '66', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('173', 'Togo', '多哥', '228', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('174', 'Tonga', '汤加', '676', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('175', 'Trinidad and Tobago', '特立尼达和多巴哥', '1809', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('176', 'Tunisia', '突尼斯', '216', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('177', 'Turkey', '土耳其', '90', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('178', 'Turkmenistan', '土库曼斯坦', '993', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('179', 'Uganda', '乌干达', '256', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('180', 'Ukraine', '乌克兰', '380', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('181', 'United Arab Emirates', '阿拉伯联合酋长国', '971', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('182', 'United Kiongdom', '英国', '44', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('183', 'United States of America', '美国', '1', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('184', 'Uruguay', '乌拉圭', '598', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('185', 'Uzbekistan', '乌兹别克斯坦', '233', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('186', 'Venezuela', '委内瑞拉', '58', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('187', 'Vietnam', '越南', '84', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('188', 'Yemen', '也门', '967', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('189', 'Yugoslavia', '南斯拉夫', '381', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('190', 'South Africa', '南非', '27', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('191', 'Zimbabwe', '津巴布韦', '263', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('192', 'Zaire', '扎伊尔', '243', '0');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."sms_code` VALUES ('193', 'Zambia', '赞比亚', '260', '0');";

        // 短信模板数据
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."message_template` VALUES ('1', 'SMS_60120946', '您的验证码是{\$code}，请在{\$time}分钟内使用。', '2017-03-24 17:58:22', '2017-03-24 17:58:22');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."message_template` VALUES ('2', 'SMS_60190053', '您正在注册LPL会员，您的验证码是{\$code}，请在{\$time}分钟内使用。', '2017-03-24 17:58:22', '2017-03-24 17:58:22');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."message_template` VALUES ('3', 'SMS_60270084', '您的提现{\$operate}，提现金额为：{\$amount}，请前往会员中心查看。', '2017-03-24 17:58:22', '2017-03-24 17:58:22');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."message_template` VALUES ('4', 'SMS_60250082', '管理员向您的{\$account_type}账户{\$operate}，金额为：{\$amount}，请前往会员中心查看。', '2017-03-24 17:58:22', '2017-03-24 17:58:22');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."message_template` VALUES ('5', 'SMS_60335070', '管理员向您的{\$account_type}账户{\$operate}，金额为：{\$amount}，请前往会员中心查看。', '2017-03-24 17:58:22', '2017-03-24 17:58:22');";
        $sql_arr[] = "INSERT INTO `".$this->db_prefix."message_template` VALUES ('6', 'SMS_60335070', '恭喜您充值成功，金额为：{\$amount}，您可以使用余额在平台购买产品，详情请前往会员中心查看。', '2017-03-24 17:58:22', '2017-03-24 17:58:22');";

		$i = 0;
		foreach ($sql_arr as $sql ) {
			try {
				M()->execute($sql);
			} catch (Exception $e) {
				$i++;
			}
		}

		//初始化超级管理员信息
		$admin_name = 'admin';
		$admin_pass = '123456';
		$admin = array(
    			'username'      =>$admin_name,
    			'password'      =>password_hash(sha1($admin_pass), PASSWORD_DEFAULT),
    			'realname'      =>$admin_name,
    			'status'        =>Constants::NORMAL,
    			'add_time'      =>curr_time(),
    			'update_time'   =>curr_time(),
    			'is_super'      =>Constants::YES,
		);
		D('Admin')->add($admin);

	}

	/**
	 * 初始化会员级别
	 *
	 * @since: 2016年12月15日 上午11:26:48
	 * @author: lyx
	 */
	private function _initUserLevel(){
		$level1_title = '银卡';
		$level1_investment = 1000;
		$level1_touch_max = $level1_investment / 2;
		$level1_touch_award = 8;
		$level1_service_award = 3;
		$level1_recommend_award = array('1-3'=>5,'4-6'=>3);
		$level1_leader_award = array('1-3'=>5,'4-6'=>3);
		$level1_point_award = array('1-20'=>1);
		$level1_floor_award = array('1-20'=>1);
		$level1_layer_touch_award = array('1-3'=>5,'4-6'=>3);
		$level1_tax = 10;
		$level1_tb_reward = $level1_investment / 2;
		$level1_multiple = 2;
		$level1_return_ratio = 2.5;
		$level1_return_cycle = 7;
		$level1_return_number = 40;
		$level1_return_max = $level1_investment * $level1_multiple;

		$level2_title = '金卡';
		$level2_investment = 3000;
		$level2_touch_max = $level2_investment / 2;
		$level2_touch_award = 10;
		$level2_service_award = 3;
		$level2_recommend_award = array('1-5'=>5,'6-10'=>3);
		$level2_leader_award = array('1-5'=>5,'6-10'=>3);
		$level2_point_award = array('1-20'=>1,'21-40'=>0.5);
		$level2_floor_award = array('1-20'=>1,'21-40'=>0.5);
		$level2_layer_touch_award = array('1-5'=>5,'6-10'=>3);
		$level2_tax = 10;
		$level2_tb_reward = $level2_investment / 2;
		$level2_multiple = 2.5;
		$level2_return_ratio = 1;
		$level2_return_cycle = 7;
		$level2_return_number = 100;
		$level2_return_max = $level2_investment * $level2_multiple;
		$user_levels = array(
    			array(
        				'title'             =>$level1_title, //级别名称
        				'investment'        =>$level1_investment, //投资额
        				'touch_max'         =>$level1_touch_max, //对碰日封顶
        				'touch_award'       =>$level1_touch_award, //对碰奖（单位是%）
        				'service_award'     =>$level1_service_award, //报单奖（单位是%）
    			        'layer_touch_award' =>json_encode($level1_layer_touch_award), //层碰奖（json串）
        				'recommend_award'   =>json_encode($level1_recommend_award), //推荐奖（json串）
        				'leader_award'      =>json_encode($level1_leader_award), //领导奖（json串）
        				'point_award'       =>json_encode($level1_point_award), //见点奖（json串）
        				'floor_award'       =>json_encode($level1_floor_award), //层奖（json串）
        				'tax'               =>$level1_tax, //手续费（单位是%）
        				'tb_reward'         =>$level1_tb_reward, //奖励购物币
        				'multiple'          =>$level1_multiple, //杠杆倍数
        				'return_ratio'      =>$level1_return_ratio, //返还百分比（单位是%）
        				'return_cycle'      =>$level1_return_cycle, //返还周期（单位是天）
        				'return_number'     =>$level1_return_number, //返还次数
        				'return_max'        =>$level1_return_max, //返还封顶
    			),
    			array(
        				'title'             =>$level2_title, //级别名称
        				'investment'        =>$level2_investment, //投资额
        				'touch_max'         =>$level2_touch_max, //对碰日封顶
        				'touch_award'       =>$level2_touch_award, //对碰奖（单位是%）
        				'service_award'     =>$level2_service_award, //报单奖（单位是%）
    			        'layer_touch_award' =>json_encode($level2_layer_touch_award), //层碰奖（json串）
        				'recommend_award'   =>json_encode($level2_recommend_award), //推荐奖（json串）
        				'leader_award'      =>json_encode($level2_leader_award), //领导奖（json串）
        				'point_award'       =>json_encode($level2_point_award), //见点奖（json串）
        				'floor_award'       =>json_encode($level2_floor_award), //层奖（json串）
        				'tax'               =>$level2_tax, //手续费（单位是%）
        				'tb_reward'         =>$level2_tb_reward, //奖励购物币
        				'multiple'          =>$level2_multiple, //杠杆倍数
        				'return_ratio'      =>$level2_return_ratio, //返还百分比（单位是%）
        				'return_cycle'      =>$level2_return_cycle, //返还周期（单位是天）
        				'return_number'     =>$level2_return_number, //返还次数
        				'return_max'        =>$level2_return_max, //返还封顶
    			),
		);
		foreach ($user_levels as $user_level) {
			$user_level['update_time'] = curr_time();
			$user_level['status'] = Constants::YES;
			D('UserLevel')->add($user_level);
		}
	}

	/**
	 * 初始化配置(系统设置)
	 *
	 * @param  string $group_name  分组名称
	 *
	 * @since: 2016年12月15日 上午9:24:26
	 * @author: lyx
	 */
	private function _initConfigSys($group_name){
		$config_sys_id=M('ConfigGroup')->getFieldByTitle($group_name,'id');
		$address_number = 10;
		$order_free_shipping = 3000;
		$order_shipping = 10;
		$settlement_time = '2016-12-13 23:00:00';
		$currency_symbol = '￥';
		$currency = '人民币';
		$exrate = 1;
		$page_number = 10;
		$unit = 1.00;
		$withdraw_day = '10;20;30';
		$configs = array(
    			array(
        				'code'          =>'SYSTEM_OPEN_RETURN',
        				'title'         =>'静态返本',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否开启返本：true(原点升级)/false(差值升级)',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_OPEN_SERVICE_CENTER',
        				'title'         =>'报单中心',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启报单中心：true/false',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_OPEN_MALL',
        				'title'         =>'商城',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启商城：true/false',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_MALL_ALLOW_ROLE',
        				'title'         =>'允许购物角色',
        				'value'         =>'all',
        				'remark'        =>'允许购物角色：service_center(报单中心)/all(所有会员)',
    			),
    			array(
        				'code'          =>'SYSTEM_ADDRESS_NUMBER',
        				'title'         =>'收货地址数量',
        				'value'         =>$address_number,
        				'remark'        =>'收货地址数量：input；',
    			),
    			array(
        				'code'          =>'SYSTEM_ORDER_FREE_SHIPPING',
        				'title'         =>'订单包邮',
        				'value'         =>$order_free_shipping,
        				//                         'value'         =>Constants::SHIPPING_ALL_NO_FREE,
        				//                         'value'         =>Constants::SHIPPING_ALL_FREE,
        				'remark'        =>'订单包邮金额：全部不包邮(-1)/全部包邮(0)/部分包邮(包邮金额)',
    			),
    			array(
        				'code'          =>'SYSTEM_MULTI_UNFINISHED_ORDER',
        				'title'         =>'多笔未完成订单',
        				'value'         =>Constants::NO,
        				'remark'        =>'多笔未完成订单：true/false',
    			),
    			array(
        				'code'          =>'SYSTEM_ORDER_SHIPPING',
        				'title'         =>'运费设置',
        				//                         'value'         =>$order_shipping,
        				'value'         =>Constants::SHIPPING_FREIGHT_COLLECT,
        				'remark'        =>'运费设置：到付(-1)/固定运费;',
    			),
    			array(
        				'code'          =>'SYSTEM_SETTLEMENT_METHOD',
        				'title'         =>'结算方式',
        				'value'         =>Constants::SETTLEMENT_METHOD_SECOND,
        				'remark'        =>'结算方式：day（日结）/second（秒结）',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_SETTLEMENT_TIME',
        				'title'         =>'结算时间',
        				'value'         =>$settlement_time,
        				'remark'        =>'结算时间：input；',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_AUTO_SLIDE',
        				'title'         =>'是否开启自动滑落',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否开启自动滑落(左区优先)：true/false',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_FORGET_PASSWORD',
        				'title'         =>'密码找回方式',
        				'value'         =>Constants::FORGET_PASSWORD_IGNORE,
        				'remark'        =>'密码找回方式：ignore(不处理)/message(短信)/email(邮件)',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_CURRENCY',
        				'title'         =>'当前系统币种',
        				'value'         =>$currency,
        				'remark'        =>'当前系统币种：input；',
    			),
    			array(
        				'code'          =>'SYSTEM_CURRENCY_SYMBOL',
        				'title'         =>'币种符号',
        				'value'         =>$currency_symbol,
        				'remark'        =>'币种符号：input；',
    			),
    			array(
        				'code'          =>'SYSTEM_EXRATE',
        				'title'         =>'兑换人民币汇率',
        				'value'         =>$exrate,
        				'remark'        =>'当前系统币种兑换人民币汇率：input；',
    			),
    			array(
        				'code'          =>'SYSTEM_PAGE_NUMBER',
        				'title'         =>'分页数',
        				'value'         =>$page_number,
        				'remark'        =>'分页数：input；',
    			),
    			array(
        				'code'          =>'SYSTEM_INTERNATIONALIZATION',
        				'title'         =>'是否开启多语言',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否开启多语言。true/false',
    			),
    			array(
        				'code'          =>'SYSTEM_DEFAULT_LANGUAGE',
        				'title'         =>'默认语言',
        				'value'         =>Constants::LANGUAGE_ZH_CN,
        				'remark'        =>'默认语言:zh_cn(中文简体)/zh_tw(中文繁体)/en(英文)',
    			),
    			array(
        				'code'          =>'SYSTEM_CAN_SET_USER_LEVEL',
        				'title'         =>'是否允许设置会员级别',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否允许设置会员级别：true/false',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'SYSTEM_TRADE_MONEY_UNIT',
        				'title'         =>'资金交易单元',
        				'value'         =>$unit,
        				'remark'        =>'资金交易的最小单元：input；',
    			),
    			array(
        				'code'          =>'SYSTEM_WITHDRAW_DAY',
        				'title'         =>'提现日',
        				'value'         =>$withdraw_day,
        				'remark'        =>'提现日：input;为空表示不限制提现时间',
    			),
		        array(
		                'code'			=>'SYSTEM_WITHDRAW_FEE',
		                'title'			=>'提现手续费',
		                'value' 		=>0,
		                'remark'		=>'手续费:input;',
		        ),
    			array(
        				'code'          =>'SYSTEM_MULTI_UNFINISHED_WITHDRAW',
        				'title'         =>'多笔未完成提现',
        				'value'         =>Constants::NO,
        				'remark'        =>'多笔未完成提现：true/false',
    			),
    			array(
        				'code'          =>'SYSTEM_MULTI_UNFINISHED_REMIT',
        				'title'         =>'多笔未完成汇款',
        				'value'         =>Constants::NO,
        				'remark'        =>'多笔未完成汇款：true/false',
    			),
		);
		foreach ($configs as $config) {
			$config['update_time'] = curr_time();
			$config['config_group_id'] = $config_sys_id;
			D('Config')->add($config);
		}
	}

	/**
	 * 初始化配置(站点设置)
	 *
	 * @param  string $group_name  分组名称
	 *
	 * @since: 2016年12月15日 上午9:24:07
	 * @author: lyx
	 */
	private function _initConfigWeb($group_name){
		$config_sys_id=M('ConfigGroup')->getFieldByTitle($group_name,'id');
		$website_name = 'mlmcms';
		$domain = 'http://demo.mlmcms.com';
		$company_name = "mlmcms";
		$company_email = "mlmcms@mlmcms.com";
		$copyright = "mlmcms";
		$keyword = "mlmcms";
		$description = "mlmcms";
		$prompt_text = "系统维护中。。。";
		$logo = "tpl/Public/images/logo.png";
		$default_avatar = "tpl/Public/images/default-user.png";
		$configs = array(
    			array(
        				'code'          =>'WEB_WEBSITE_NAME',
        				'title'         =>'系统名称',
        				'value'         =>$website_name,
        				'remark'        =>'系统名称：input；',
    			),
    			array(
        				'code'          =>'WEB_DOMAIN',
        				'title'         =>'系统域名',
        				'value'         =>$domain,
        				'remark'        =>'系统域名：input；',
    			),
    			array(
        				'code'          =>'WEB_COMPANY_NAME',
        				'title'         =>'公司名称',
        				'value'         =>$company_name,
        				'remark'        =>'公司名称：input；',
    			),
    			array(
        				'code'          =>'WEB_COMPANY_EMAIL',
        				'title'         =>'公司邮箱',
        				'value'         =>$company_email,
        				'remark'        =>'公司邮箱：input；',
    			),
    			array(
        				'code'          =>'WEB_COPYRIGHT',
        				'title'         =>'系统版权',
        				'value'         =>$copyright,
        				'remark'        =>'系统版权：input；',
    			),
    			array(
        				'code'          =>'WEB_KEYWORD',
        				'title'         =>'关键字',
        				'value'         =>$keyword,
        				'remark'        =>'关键字：input；',
    			),
    			array(
        				'code'          =>'WEB_DESCRIPTION',
        				'title'         =>'描述',
        				'value'         =>$description,
        				'remark'        =>'描述：input；',
    			),
    			array(
        				'code'          =>'WEB_SYSTEM_STATE',
        				'title'         =>'系统状态',
        				'value'         =>Constants::YES,
        				'remark'        =>'系统状态：true/false;',
    			),
    			array(
        				'code'          =>'WEB_CLOSE_PROMPT_TEXT',
        				'title'         =>'系统关闭提示文字',
        				'value'         =>$prompt_text,
        				'remark'        =>'系统关闭提示文字：input；',
    			),
    			array(
        				'code'          =>'WEB_LOGO',
        				'title'         =>'系统logo',
        				'value'         =>$logo,
        				'remark'        =>'系统logo：input；',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'WEB_DEFAULT_AVATAR',
        				'title'         =>'系统默认头像',
        				'value'         =>$default_avatar,
        				'remark'        =>'系统默认头像：input；',
        				'is_can_update' =>Constants::NO,
    			),
		);
		foreach ($configs as $config) {
			$config['update_time'] = curr_time();
			$config['config_group_id'] = $config_sys_id;
			D('Config')->add($config);
		}
	}

	/**
	 * 初始化配置(奖项设置)
	 *
	 * @param  string $group_name  分组名称
	 *
	 * @since: 2016年12月15日 上午9:23:31
	 * @author: lyx
	 */
	private function _initConfigAward($group_name){
		$config_sys_id=M('ConfigGroup')->getFieldByTitle($group_name,'id');
		$fee = array('touch', 'service', 'recommend', 'leader', 'floor', 'point', 'layer_touch');
		$configs = array(
    			array(
        				'code'          =>'AWARD_OPEN_TOUCH',
        				'title'         =>'对碰奖',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启对碰奖项：true/false;',
    			),
    			array(
        				'code'          =>'AWARD_OPEN_SERVICE',
        				'title'         =>'报单奖',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启报单奖项：true/false;',
    			),
    			array(
        				'code'          =>'AWARD_OPEN_RECOMMEND',
        				'title'         =>'推荐奖',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启推荐奖项：true/false;',
    			),
    			array(
        				'code'          =>'AWARD_OPEN_LEADER',
        				'title'         =>'领导奖',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启领导奖项：true/false;',
    			),
    			array(
        				'code'          =>'AWARD_OPEN_FLOOR',
        				'title'         =>'层奖',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启层奖项：true/false;',
    			),
    	        array(
    	                'code'          =>'AWARD_OPEN_LAYER_TOUCH',
    	                'title'         =>'层碰奖',
    	                'value'         =>Constants::YES,
    	                'remark'        =>'是否开启层碰奖项：true/false;',
    	        ),
    			array(
        				'code'          =>'AWARD_OPEN_POINT',
        				'title'         =>'见点奖',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启见点奖项 ：true/false;',
    			),
    			array(
        				'code'          =>'AWARD_SET_NUMBER',
        				'title'         =>'奖项允许设置的最大子项数',
        				'value'         =>5,
        				'remark'        =>'奖项允许设置的最大子项数：input;',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'AWARD_FEE',
        				'title'         =>'手续费设置',
        				'value'         =>json_encode($fee),
        				'remark'        =>'手续费设置：input;',
    			),
		);
		foreach ($configs as $config) {
			$config['update_time'] = curr_time();
			$config['config_group_id'] = $config_sys_id;
			D('Config')->add($config);
		}
	}

	/**
	 * 初始化配置(会员设置)
	 *
	 * @param  string $group_name  分组名称
	 *
	 * @since: 2016年12月15日 上午9:22:51
	 * @author: lyx
	 */
	private function _initConfigUser($group_name){
		$config_sys_id=M('ConfigGroup')->getFieldByTitle($group_name,'id');
		$prefix = 'mlmcms_';
		$vertex_no = "mlmcms888";
		$vertex_password = "123456";
		$initial_password = "123456";
		/* $all_item = array('email','qq','telephone','zip_code','alipay','wechat','bank','sub_bank','bank_no','sex','birthday','id_card','address');
        $visible_item = array('email','qq','telephone','zip_code','alipay','wechat','bank','sub_bank','bank_no');
        $required_item = array('email','telephone','bank','sub_bank','bank_no');
        $unique_item = array('email','bank_no'); */
		$enroll_item = array(
    			'email'        => array('visible','required','unique'),
    			'qq'           => array('visible'),
    			'telephone'    => array('visible','required'),
    			'zip_code'     => array(),
    			'alipay'       => array('visible'),
    			'wechat'       => array('visible'),
    			'bank'         => array(),
    			'sub_bank'     => array(),
    			'bank_no'      => array(),
    			'sex'          => array(),
    			'birthday'     => array(),
    			'id_card'      => array('visible'),
    			'address'      => array(),
		);
		$configs = array(
    			array(
        				'code'          =>'USER_PREFIX',
        				'title'         =>'会员编号前缀',
        				'value'         =>$prefix,
        				'remark'        =>'会员编号前缀：input；',
    			),
    			array(
        				'code'          =>'USER_VERTEX_NO',
        				'title'         =>'顶点编号',
        				'value'         =>$vertex_no,
        				'remark'        =>'顶点编号：input；',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'USER_VERTEX_PASSWORD',
        				'title'         =>'顶点密码',
        				'value'         =>$vertex_password,
        				'remark'        =>'顶点密码：input；',
        				'is_can_update' =>Constants::NO,
    			),
    			array(
        				'code'          =>'USER_INITIAL_PASSWORD',
        				'title'         =>'初始密码',
        				'value'         =>$initial_password,
        				'remark'        =>'初始密码：input；',
    			),
    			array(
        				'code'          =>'USER_OPEN_AUTO_NO',
        				'title'         =>'启用系统会员编号',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否启用系统会员编号：true/false',
    			),
    			/* array(
                        'code'          =>'USER_VISIBLE_ITEM',
                        'title'         =>'可见项',
                        'value'         =>json_encode($visible_item),
                        'remark'        =>'可见项设置：email;qq;电话;邮编;支付宝;微信账号;银行卡发卡行;银行卡开户支行;银行卡账号;性别;生日;身份证;地址',
                ),
                array(
                        'code'          =>'USER_REQUIRED_ITEM',
                        'title'         =>'必填项',
                        'value'         =>json_encode($required_item),
                        'remark'        =>'必填项设置：email;qq;电话;邮编;支付宝;微信账号;银行卡发卡行;银行卡开户支行;银行卡账号;性别;生日;身份证;地址；',
                ),
                array(
                        'code'          =>'USER_UNIQUE_ITEM',
                        'title'         =>'唯一项',
                        'value'         =>json_encode($unique_item),
                        'remark'        =>'唯一项设置：email;qq;电话;支付宝;微信账号;银行卡账号;身份证；',
                ), */
    			array(
        				'code'          =>'USER_IS_REAL_REGISTER',
        				'title'         =>'是否是实注册',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否是实注册（空注册：不产生业绩，等待激活；实注册：产生业绩，会员已激活。）：true/false',
    			),
    			array(
        				'code'          =>'USER_ENROLL_ITEM',
        				'title'         =>'会员注册项设置',
        				'value'         =>json_encode($enroll_item),
        				'remark'        =>'会员注册项设置：email;qq;电话;邮编;支付宝;微信账号;银行卡发卡行;银行卡开户支行;银行卡账号;性别;生日;身份证;地址；',
    			),
    			array(
        				'code'          =>'USER_CROSS_REGION',
        				'title'         =>'是否允许跨区域操作',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否允许跨区域操作：true/false',
        				'is_can_update' =>Constants::NO,
    			),
    	        array(
    	                'code'          =>'USER_TEST_DATA_LEVEL',
    	                'title'         =>'模拟生成数据的层级',
    	                'value'         => 5,
    	                'remark'        =>'模拟生成数据的层级：input',
    	                'is_can_update' =>Constants::NO,
    	        ),
		);
		foreach ($configs as $config) {
			$config['update_time'] = curr_time();
			$config['config_group_id'] = $config_sys_id;
			D('Config')->add($config);
		}
	}

	/**
	 * 初始化配置(短信设置)
	 *
	 * @param  string $group_name  分组名称
	 *
	 * @since: 2017年1月6日 上午10:58:30
	 * @author: lyx
	 */
	private function _initConfigMessage($group_name){
		$config_sys_id=M('ConfigGroup')->getFieldByTitle($group_name,'id');

		$sign = '偶聚';
		$item = array(
    			'is_open'      => Constants::NO,
    			'template_id'  => ''
		);
		$configs = array(
    			array(
        				'code'          =>'MESSAGE_OPEN_SEND',
        				'title'         =>'是否开启短信发送',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否开启短信发送：true/false;',
    			),
    			// 阿里大鱼短信配置
    			array(
        				'code'          =>'MESSAGE_OPEN_SEND_ALI',
        				'title'         =>'开启阿里大鱼短信发送',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否开启阿里大鱼短信发送：true/false;',
    			),
    			array(
        				'code'          =>'MESSAGE_APPKEY_ALI',
        				'title'         =>'应用key',
        				'value'         =>'23601538',
        				'remark'        =>'应用key：input;',
    			),
    			array(
        				'code'          =>'MESSAGE_SECRETKEY_ALI',
        				'title'         =>'密钥',
        				'value'         =>'8ffae6f7f682bca460603673d0c86c64',
        				'remark'        =>'密钥：input;',
    			),
    			array(
        				'code'          =>'MESSAGE_SIGN_ALI',
        				'title'         =>'短信签名',
        				'value'         =>$sign,
        				'remark'        =>'短信签名：input;',
    			),
    			// 海客短信配置
    			array(
        				'code'          =>'MESSAGE_OPEN_SEND_HEYSKY',
        				'title'         =>'开启海客短信发送',
        				'value'         =>Constants::NO,
        				'remark'        =>'是否开启海客短信发送：true/false;',
    			),
    			array(
        				'code'          =>'MESSAGE_APPKEY_HEYSKY',
        				'title'         =>'应用key',
        				'value'         =>'zf2rkw',
        				'remark'        =>'应用key：input;',
    			),
    			array(
        				'code'          =>'MESSAGE_SECRETKEY_HEYSKY',
        				'title'         =>'密钥',
        				'value'         =>'ZbGou2W5',
        				'remark'        =>'密钥：input;',
    			),
    			array(
        				'code'          =>'MESSAGE_SIGN_HEYSKY',
        				'title'         =>'短信签名',
        				'value'         =>$sign,
        				'remark'        =>'短信签名：input;',
    			),
    			array(
        				'code'          =>'MESSAGE_FORGET',
        				'title'         =>'忘记密码',
        				'value'         =>json_encode($item),
        				'remark'        =>'忘记密码：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_ACTIVATE',
        				'title'         =>'会员激活',
        				'value'         =>json_encode($item),
        				'remark'        =>'会员激活：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_WITHDRAW',
        				'title'         =>'提现审核',
        				'value'         =>json_encode($item),
        				'remark'        =>'提现审核：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_DEDUCT',
        				'title'         =>'后台扣款',
        				'value'         =>json_encode($item),
        				'remark'        =>'后台扣款：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_RECHARGE',
        				'title'         =>'后台充值',
        				'value'         =>json_encode($item),
        				'remark'        =>'后台充值：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_ROLL_IN',
        				'title'         =>'资金转入',
        				'value'         =>json_encode($item),
        				'remark'        =>'资金转入：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_ROLL_OUT',
        				'title'         =>'资金转出',
        				'value'         =>json_encode($item),
        				'remark'        =>'资金转出：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_REMIT',
        				'title'         =>'汇款审核',
        				'value'         =>json_encode($item),
        				'remark'        =>'汇款审核：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_BUY',
        				'title'         =>'商城下单',
        				'value'         =>json_encode($item),
        				'remark'        =>'商城下单：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_ORDER',
        				'title'         =>'订单审核',
        				'value'         =>json_encode($item),
        				'remark'        =>'订单审核：json;',
    			),
    			array(
        				'code'          =>'MESSAGE_PAY',
        				'title'         =>'三方支付',
        				'value'         =>json_encode($item),
        				'remark'        =>'三方支付：json;',
    			)
		);
		foreach ($configs as $config) {
			$config['update_time'] = curr_time();
			$config['config_group_id'] = $config_sys_id;
			D('Config')->add($config);
		}
	}

	/**
	 * 初始化配置(支付设置)
	 *
	 * @param  string $group_name  分组名称
	 *
	 * @since: 2017年1月6日 下午5:54:51
	 * @author: lyx
	 */
	private function _initConfigPay($group_name){
		$config_sys_id=M('ConfigGroup')->getFieldByTitle($group_name,'id');

		$configs = array(
				array(
        				'code'          =>'PAY_OPEN',
        				'title'         =>'是否开启支付',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启支付：true/false;',
    			),
				// 支付宝支付配置
    			array(
        				'code'          =>'PAY_OPEN_ALI',
        				'title'         =>'开启支付宝支付',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启支付宝支付：true/false;',
    			),
    			array(
    					'code'          =>'PAY_APPID_ALI',
        				'title'         =>'应用ID',
        				'value'         =>'2016122104470559',
        				'remark'        =>'应用ID：input;',
    			),
    			array(
    					'code'          =>'PAY_PARTNER_ALI',
        				'title'         =>'合作身份者ID',
        				'value'         =>'2088221238901484',
        				'remark'        =>'合作身份者ID：input;',
    			),
    			array(
    					'code'          =>'PAY_MERNAME_ALI',
        				'title'         =>'支付宝账户',
        				'value'         =>'web@ouju.me',
        				'remark'        =>'支付宝账户：input;',
    			),
    			array(
        				'code'          =>'PAY_PUBLIC_KEY_ALI',
        				'title'         =>'公钥',
        				'value'         =>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB',
        				'remark'        =>'公钥文件地址：input;',
    			),
    			array(
        				'code'          =>'PAY_PRIVAT_EKEY_ALI',
        				'title'         =>'私钥',
        				'value'         =>'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAJq9VCZIf78llv/0kxpA/f6j/RYbDqM3mDBdz3isBO03Bc6je3tNM3TNwwJ8CLsRsIpTcuYhBdyer9RbbUL7IbHF0YALyx+fNUiga/Y+EvuUHqsOWPQrxPy7nyIIE/VriLq22p9QBvOP1ZQXL4iq6SLPm51z/S6ExKSvT8HA8Uf9AgMBAAECgYByVEzmdE6exoFI4EYH8dk42aVXPeqUwbDam5V9TWvecPcDdFr6AVJxjM32+fnhbfKIYZvVWLTiCwVS27Jg+PgtINEMkZzcqWZnTNaKE6l2N43GJv7r/GrIhk7lKbVW9nP75926jj9Jt/agj7PKBkAC/7+gGfmTLlhD6fpFLxdHKQJBANCpJnmKb+C54jxAuicdgltLL+0LxEC54Jy6qQT+m/ZgjQeQ4lU40kJmK6UeFI9sAeZsMmAPSJ3bMhGiEwYIwicCQQC92Hq3Y/rmjP2sXfOzdnZ6X19m2YQm2tNIgMleeCLTC+RTPDu8x9l3+2FDqIg35m+iJdQmoBR9fAIsgdEQ+c87AkEAqR7QPmaEM0K2KVvVBWsXguM33wtQb524fY+U+qVax6CN7fnyWFyLnqGs8lGlHHHQQHCli9IXa0qEFGKmxJdItQJAdbgMsDcg12FJ014WxYuJf+wvvhjW5zj9lpG1TAz2myNem3ZYHIFYChwofcm9XdxYEJWgbasJyZ3hwzNkLkCZDQJALdDQUki/3jDz2buKerS/bg60TK8kSTpjkNLJjbXEgjuOdo5s5SlADcJHyS/Wl7C6R6Imq8MBgTFqfMoshleO+w==',
        				'remark'        =>'私钥文件地址：input;',
    			),
    			// 环迅支付配置
    			array(
        				'code'          =>'PAY_OPEN_IPS',
        				'title'         =>'开启环迅支付',
        				'value'         =>Constants::YES,
        				'remark'        =>'是否开启环迅支付：true/false;',
    			),
    			array(
    					'code'          =>'PAY_MERCODE_IPS',
        				'title'         =>'商户ID',
        				'value'         =>'195433',
        				'remark'        =>'商户ID：input;',
    			),
    			array(
    					'code'          =>'PAY_ACCOUNT_IPS',
        				'title'         =>'账户ID',
        				'value'         =>'1954330013',
        				'remark'        =>'账户ID：input;',
    			),
    			array(
    					'code'          =>'PAY_MERNAME_IPS',
        				'title'         =>'商户名',
        				'value'         =>'厦门汇众科创互联网服务有限公司',
        				'remark'        =>'商户名：input;',
    			),
    			array(
        				'code'          =>'PAY_IPSRSAPUB_IPS',
        				'title'         =>'公钥',
        				'value'         =>'-----BEGIN PUBLIC KEY-----MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCbfyYdw2j5gOF7X9cdFrUKJ+MRTAfpJB+opBxjSw7iAZNUv9TmQHH/LSAim2ucaBRiB/Cqm1agocip3g8YC7Md/AhCtN+di0uc3d0F2c7H/WZm4n98IPjwfjmxNUJxdvKnF3CezY9nCCHWu36NvtMlCKLlO14Iu/PNvsVVv85zowIDAQAB-----END PUBLIC KEY-----',
        				'remark'        =>'公钥文件地址：input;',
    			),
    			array(
        				'code'          =>'PAY_MERCERT_IPS',
        				'title'         =>'私钥',
        				'value'         =>'y9hgmecPLaBVAR1dZLPmY8mD43Aa4BfySAmrKCNM0uFd1QonPBwOpHp7pyfl2wQK75sX7lFdVWfOvDB1dR9L1oMP3FsgHQskcJUScjO0bfMJTfO5TRLhhUNXYohcM59t',
        				'remark'        =>'私钥文件地址：input;',
    			),
    			// 商品默认标题和描述
    			array(
        				'code'          =>'PAY_GOODS_TITLE',
        				'title'         =>'商品标题',
        				'value'         =>'商品标题',
        				'remark'        =>'商品标题：input;',
    			),
    			array(
        				'code'          =>'PAY_GOODS_BODY',
        				'title'         =>'商品描述',
        				'value'         =>'商品描述',
        				'remark'        =>'商品描述：input;',
    			)
		);
		foreach ($configs as $config) {
			$config['update_time'] = curr_time();
			$config['config_group_id'] = $config_sys_id;
			D('Config')->add($config);
		}
	}

	/**
	 * 初始化配置(邮件设置)
	 *
	 * @param  string $group_name  分组名称
	 *
	 * @since: 2017年2月15日 下午1:23:28
	 * @author: lyx
	 */
	private function _initConfigMail($group_name){
	    $config_sys_id=M('ConfigGroup')->getFieldByTitle($group_name,'id');

	    $configs = array(
	            array(
	                    'code'          =>'MAIL_OPEN_SEND',
	                    'title'         =>'是否开启邮件发送',
	                    'value'         =>Constants::NO,
	                    'remark'        =>'是否开启邮件发送：true/false;',
	            ),
	            array(
	                    'code'          =>'MAIL_HOST',
	                    'title'         =>'SMTP服务器',
	                    'value'         =>'smtp.qq.com',
	                    'remark'        =>'SMTP服务器：input;',
	            ),
	            array(
	                    'code'          =>'MAIL_USERNAME',
	                    'title'         =>'SMTP服务器用户名',
	                    'value'         =>'mlmcms',
	                    'remark'        =>'SMTP服务器用户名：input;',
	            ),
	            array(
	                    'code'          =>'MAIL_PASSWORD',
	                    'title'         =>'SMTP服务器密码',
	                    'value'         =>'mlmcms',
	                    'remark'        =>'SMTP服务器密码：input;',
	            ),
	            array(
	                    'code'          =>'MAIL_SECURE',
	                    'title'         =>'SMTP服务器链接方式',
	                    'value'         =>'ssl',
	                    'remark'        =>'SMTP服务器链接方式：input;',
	            ),
	    );
	    foreach ($configs as $config) {
	        $config['update_time'] = curr_time();
	        $config['config_group_id'] = $config_sys_id;
	        D('Config')->add($config);
	    }
	}

	/**
	 * 添加测试数据
	 *
	 * @since: 2016年12月13日 下午4:03:44
	 * @author: xielu
	 *
	 * @since: 2017年2月22日 上午11:38:10
	 * @update: lyx
	 */
	private function _addData($parentNo,$userNo,$loca,$path,$user_levels,$service_centers) {
	    $level=array_rand($user_levels,1);
	    $floor = strlen($path);//层数;
	    $rec_floor =$floor;//代数
	    $user = array(
	            'user_no'          => $userNo,
	            'password'         => password_hash(sha1($this->sys_config['USER_INITIAL_PASSWORD']), PASSWORD_DEFAULT),
	            'two_password'     => password_hash(sha1($this->sys_config['USER_INITIAL_PASSWORD']), PASSWORD_DEFAULT),
	            'realname'         => $userNo,
	            'location'         => $loca,
	            'path'             => $path,
	            'floor'            => $floor,
	            'rec_floor'        => $rec_floor,
	            'user_level_id'    => $user_levels[$level]['id'],
	            'investment'       => $user_levels[$level]['investment'],
	            'add_time'         => curr_time(),
	            'recommend_no'     => $parentNo,
	            'register_no'      => $parentNo,
	            'parent_no'        => $parentNo
	    );
	    //开启实注册
	    if ($this->sys_config['USER_IS_REAL_REGISTER']) {
	        $user['is_activated']  = Constants::YES;
	        $user['activate_time'] = curr_time();
	    }
	    //开启报单中心
	    if ($service_centers) {
	        $service=array_rand($service_centers,1);
	        $user['service_center_no']  = $service_centers[$service]['user_no'];
	    }
	    M('User')->add($user);
	    $this->saveRecommendNexus($userNo,$parentNo);//会员推荐关系
	    $this->saveParentNexus($userNo,$parentNo);//会员安置关系

	    //开启实注册
	    if ($this->sys_config['USER_IS_REAL_REGISTER']) {
	        //业绩记录
	        $market = array(
	                'user_no'       => $userNo,
	                'user_level_id' => $user_levels[$level]['id'],
	                'market_type'   => Constants::MARKET_TYPE_ENROLL,
	                'amount'        => $user_levels[$level]['investment'],
	                'status'        => Constants::NO,
	                'add_time'      => curr_time(),
	                'return_number' => 0,
	                'return_time'   => curr_time()
	        );
	        D('MarketRecord')->add($market);
	    }
	}

	/**
	 * 清空数据库中所有数据
	 *
	 * @since: 2016年12月15日 上午11:31:42
	 * @author: lyx
	 */
	private function _deleteData(){
		$sql_arr[] = "truncate table `".$this->db_prefix."account_record`";
		$sql_arr[] = "truncate table `".$this->db_prefix."address`";
		$sql_arr[] = "truncate table `".$this->db_prefix."admin`";
		$sql_arr[] = "truncate table `".$this->db_prefix."auth_code`";
		$sql_arr[] = "truncate table `".$this->db_prefix."auth_group`";
		$sql_arr[] = "truncate table `".$this->db_prefix."auth_group_access`";
		$sql_arr[] = "truncate table `".$this->db_prefix."auth_rule`";
		$sql_arr[] = "truncate table `".$this->db_prefix."cart`";
		$sql_arr[] = "truncate table `".$this->db_prefix."config`";
		$sql_arr[] = "truncate table `".$this->db_prefix."giro`";
		$sql_arr[] = "truncate table `".$this->db_prefix."goods`";
		$sql_arr[] = "truncate table `".$this->db_prefix."goods_category`";
		$sql_arr[] = "truncate table `".$this->db_prefix."goods_extend`";
		$sql_arr[] = "truncate table `".$this->db_prefix."goods_inventory`";
		$sql_arr[] = "truncate table `".$this->db_prefix."mail`";
		$sql_arr[] = "truncate table `".$this->db_prefix."market_record`";
		$sql_arr[] = "truncate table `".$this->db_prefix."message`";
		$sql_arr[] = "truncate table `".$this->db_prefix."news`";
		$sql_arr[] = "truncate table `".$this->db_prefix."news_category`";
		$sql_arr[] = "truncate table `".$this->db_prefix."order`";
		$sql_arr[] = "truncate table `".$this->db_prefix."order_goods`";
		$sql_arr[] = "truncate table `".$this->db_prefix."parent_nexus`";
		$sql_arr[] = "truncate table `".$this->db_prefix."recommend_nexus`";
		$sql_arr[] = "truncate table `".$this->db_prefix."remit`";
		$sql_arr[] = "truncate table `".$this->db_prefix."return_record`";
		$sql_arr[] = "truncate table `".$this->db_prefix."reward_record`";
		$sql_arr[] = "truncate table `".$this->db_prefix."service_center`";
		$sql_arr[] = "truncate table `".$this->db_prefix."user`";
		$sql_arr[] = "truncate table `".$this->db_prefix."user_extend`";
		$sql_arr[] = "truncate table `".$this->db_prefix."user_level`";
		$sql_arr[] = "truncate table `".$this->db_prefix."withdraw`";
		$sql_arr[] = "truncate table `".$this->db_prefix."config_group`";
		$sql_arr[] = "truncate table `".$this->db_prefix."log`";
		$sql_arr[] = "truncate table `".$this->db_prefix."pay`";
		$sql_arr[] = "truncate table `".$this->db_prefix."bank`";
		$sql_arr[] = "truncate table `".$this->db_prefix."layer_touch`";
		$sql_arr[] = "truncate table `".$this->db_prefix."message_template`";
        $sql_arr[] = "truncate table `".$this->db_prefix."sms_code`";
        $sql_arr[] = "truncate table `".$this->db_prefix."bank_type`";
		//执行清空数据的sql操作
		$i = 0;
		foreach ($sql_arr as $sql ) {
			try {
				M()->execute($sql);
			} catch (Exception $e) {
				$i++;
			}
		}
	}

	/**
	 * 创建最初始时数据库语句
	 *
	 * @param  array $sql_arr  旧的数据库语句数组
	 * @return array           新的数据库语句数组
	 *
	 * @since: 2016年12月13日 下午4:53:17
	 * @author: lyx
	 */
	private function _initDataBase($sql_arr){
		//创建配置分组表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."config_group` (
                      `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `title` VARCHAR(20) NOT NULL COMMENT '分组名称',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '配置分组表';";
		//创建配置表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."config` (
                      `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `config_group_id` TINYINT UNSIGNED NOT NULL COMMENT '分组标识',
                      `code` VARCHAR(50) NOT NULL COMMENT '配置项',
                      `title` VARCHAR(20) NOT NULL COMMENT '配置项中文名称',
                      `value` VARCHAR(200) NOT NULL COMMENT '配置项的值',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '配置表';";
		//创建会员级别表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."user_level` (
                      `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识。由小到大，表示级别越来越高',
                      `title` VARCHAR(20) NOT NULL COMMENT '级别名称',
                      `investment` DECIMAL(10,2) NOT NULL COMMENT '投资额',
                      `touch_max` DECIMAL(10,2) NOT NULL COMMENT '对碰日封顶',
                      `touch_award` DECIMAL(4,1) NOT NULL COMMENT '对碰奖（单位是%）',
                      `service_award` DECIMAL(4,1) NOT NULL COMMENT '报单奖（单位是%）',
                      `recommend_award` VARCHAR(200) NOT NULL COMMENT '推荐奖（json串）',
                      `leader_award` VARCHAR(200) NOT NULL COMMENT '领导奖（json串）',
                      `point_award` VARCHAR(200) NOT NULL COMMENT '见点奖（json串）',
                      `floor_award` VARCHAR(200) NOT NULL COMMENT '层奖（json串）',
                      `tax` DECIMAL(4,1) NOT NULL COMMENT '手续费（单位是%）',
                      `tb_reward` DECIMAL(10,2) NOT NULL COMMENT '奖励购物币',
                      `multiple` DECIMAL(4,1) NOT NULL COMMENT '杠杆倍数',
                      `return_ratio` DECIMAL(4,1) NOT NULL COMMENT '返还百分比（单位是%）',
                      `return_cycle` TINYINT UNSIGNED NOT NULL COMMENT '返还周期（单位是天）',
                      `return_number` SMALLINT UNSIGNED NOT NULL COMMENT '返还次数',
                      `return_max` DECIMAL(10,2) NOT NULL COMMENT '返还封顶',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。0：禁用；1：正常。默认为1。',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '会员级别表';";
		//创建新闻分类表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."news_category` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `title` VARCHAR(20) NOT NULL COMMENT '分类名称',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。1：正常。默认为1，以后扩展使用',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '新闻分类表';";
		//创建新闻表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."news` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `news_category_id` SMALLINT UNSIGNED NOT NULL COMMENT '分类标识',
                      `title` VARCHAR(50) NOT NULL COMMENT '标题',
                      `content` MEDIUMTEXT NOT NULL COMMENT '内容',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。1：正常。默认为1，以后扩展使用',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '新闻表';";
		//创建规则表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."auth_rule` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `parent_id` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级标识，为0表示是顶级。默认为0',
                      `name` VARCHAR(100) NOT NULL COMMENT '规则标识（action）',
                      `title` VARCHAR(20) NOT NULL COMMENT '规则中文名称',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。0：禁用；1：正常。默认为1',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型。默认为1。如果type为1， condition字段就可以定义规则表达式',
                      `condition` VARCHAR(100) NOT NULL COMMENT '规则表达式。为空表示存在就验证，不为空表示按照条件验证',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '规则表';";
		//创建用户组表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."auth_group` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `title` VARCHAR(20) NOT NULL COMMENT '用户组名称',
                      `rules` VARCHAR(500) NOT NULL COMMENT '规则组合。用户组拥有的规则id， 多个规则\",\"隔开',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。0：禁用；1：正常。默认为1',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '用户组表';";
		//创建管理员表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."admin` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `username` VARCHAR(20) NOT NULL COMMENT '用户名（唯一约束）',
                      `password` VARCHAR(50) NOT NULL COMMENT '密码',
                      `realname` VARCHAR(20) NOT NULL COMMENT '真实姓名',
                      `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
                      `email` VARCHAR(50) NOT NULL COMMENT '邮箱',
                      `nickname` VARCHAR(20) NOT NULL COMMENT '昵称',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。1：正常。默认为1，以后扩展使用',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      `login_time` DATETIME NULL COMMENT '最后登录时间',
                      `is_super` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超级管理员。0：普通管理员；1：超级管理员。默认为0',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '管理员表';";
		//创建用户组明细表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."auth_group_access` (
                      `uid` SMALLINT UNSIGNED NOT NULL COMMENT '管理员id',
                      `group_id` SMALLINT UNSIGNED NOT NULL COMMENT '用户组id',
                      PRIMARY KEY (`uid`, `group_id`))
                    ENGINE = InnoDB
                    COMMENT = '用户组明细表';";
		//创建会员基本信息表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."user` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `realname` VARCHAR(20) NOT NULL COMMENT '真实姓名',
                      `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
                      `password` VARCHAR(50) NOT NULL COMMENT '密码',
                      `two_password` VARCHAR(50) NOT NULL COMMENT '安全码',
                      `parent_no` VARCHAR(20) NOT NULL COMMENT '安置人（会员编号）',
                      `location` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '安置位置。1：左区；2：右区。默认为1',
                      `path` VARCHAR(500) NOT NULL COMMENT '节点路径',
                      `recommend_no` VARCHAR(20) NOT NULL COMMENT '推荐人（会员编号）',
                      `left_no` VARCHAR(20) NOT NULL COMMENT '左节点（会员编号）',
                      `right_no` VARCHAR(20) NOT NULL COMMENT '右节点（会员编号）',
                      `register_no` VARCHAR(20) NOT NULL COMMENT '注册人（会员编号）',
                      `service_center_no` VARCHAR(20) NOT NULL COMMENT '报单中心（会员编号）',
                      `floor` TINYINT UNSIGNED NOT NULL COMMENT '层数',
                      `rec_floor` TINYINT UNSIGNED NOT NULL COMMENT '代数',
                      `user_level_id` TINYINT UNSIGNED NOT NULL COMMENT '会员级别标识',
                      `investment` DECIMAL(10,2) NOT NULL COMMENT '投资额',
                      `left_market` DECIMAL(10,2) NOT NULL COMMENT '左区剩余业绩',
                      `right_market` DECIMAL(10,2) NOT NULL COMMENT '右区剩余业绩',
                      `touch_market` DECIMAL(10,2) NOT NULL COMMENT '对碰业绩',
                      `eb_account` DECIMAL(10,2) NOT NULL COMMENT '注册币（EB）',
                      `tb_account` DECIMAL(10,2) NOT NULL COMMENT '购物币（TB）',
                      `cb_account` DECIMAL(10,2) NOT NULL COMMENT '现金账户（CB）',
                      `mb_account` DECIMAL(10,2) NOT NULL COMMENT '奖金账户（MB）',
                      `rb_account` DECIMAL(10,2) NOT NULL COMMENT '返利账户（RB）',
                      `is_activated` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否激活。0：未激活（只有部分操作）；1：已激活。默认为0',
                      `is_locked` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否锁定。0：未锁定；1：已锁定（不可以登录）。默认为0',
                      `is_frozen` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否冻结。0：未冻结；1：已冻结（不可以操作）。默认为0',
                      `add_time` DATETIME NOT NULL COMMENT '注册时间',
                      `activate_time` DATETIME NULL COMMENT '激活时间',
                      `login_time` DATETIME NULL COMMENT '最后登录时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '会员基本信息表';";
		//创建会员个人信息表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."user_extend` (
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号（唯一标识）',
                      `email` VARCHAR(50) NOT NULL COMMENT 'email',
                      `qq` VARCHAR(20) NOT NULL COMMENT 'qq',
                      `telephone` VARCHAR(20) NOT NULL COMMENT '电话',
                      `zip_code` VARCHAR(10) NOT NULL COMMENT '邮编',
                      `alipay` VARCHAR(50) NOT NULL COMMENT '支付宝',
                      `wechat` VARCHAR(50) NOT NULL COMMENT '微信账号',
                      `bank` VARCHAR(20) NOT NULL COMMENT '银行名称',
                      `sub_bank` VARCHAR(20) NOT NULL COMMENT '开户支行',
                      `bank_no` VARCHAR(20) NOT NULL COMMENT '银行卡号',
                      `sex` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别。0：保密；1：男；2：女。默认为0',
                      `birthday` VARCHAR(10) NOT NULL COMMENT '生日',
                      `id_card` VARCHAR(20) NOT NULL COMMENT '身份证',
                      `address` VARCHAR(50) NOT NULL COMMENT '地址',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`user_no`))
                    ENGINE = InnoDB
                    COMMENT = '会员个人信息表';";
		//创建会员安置关系表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."parent_nexus` (
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `parent_no` VARCHAR(20) NOT NULL COMMENT '父级安置人编号',
                      `floor` TINYINT UNSIGNED NOT NULL COMMENT '层级',
                      PRIMARY KEY (`user_no`, `parent_no`))
                    ENGINE = InnoDB
                    COMMENT = '会员安置关系表';";
		//创建会员推荐关系表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."recommend_nexus` (
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `recommend_no` VARCHAR(20) NOT NULL COMMENT '父级推荐人编号',
                      `rec_floor` TINYINT UNSIGNED NOT NULL COMMENT '代级',
                      PRIMARY KEY (`user_no`, `recommend_no`))
                    ENGINE = InnoDB
                    COMMENT = '会员推荐关系表';";
		//创建服务中心表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."service_center` (
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号（唯一标识）',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态。0：未审核；1：已通过；2：被驳回。默认为0',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`user_no`))
                    ENGINE = InnoDB
                    COMMENT = '服务中心表';";
		//创建站内信表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."mail` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `sender_no` VARCHAR(20) NOT NULL COMMENT '发件人编号，为空表示是系统发件',
                      `receiver_no` VARCHAR(20) NOT NULL COMMENT '收件人编号，为空表示发件给系统',
                      `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级标志。信件砌墙，为0表示顶级信件。默认为0',
                      `title` VARCHAR(50) NOT NULL COMMENT '主题',
                      `content` MEDIUMTEXT NOT NULL COMMENT '内容',
                      `is_read` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已读。0：未读；1：已读。默认为0',
                      `is_sender_delete` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发件人是否删除。0：未删除；1：已删除。默认为0。',
                      `is_receiver_delete` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收件人是否删除。0：未删除；1：已删除。默认为0。',
                      `send_time` DATETIME NOT NULL COMMENT '发送时间',
                      `read_time` DATETIME NULL COMMENT '首次阅读时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '站内信表';";
		//创建短信表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."message` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
                      `content` VARCHAR(200) NOT NULL COMMENT '内容',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型。0：忘记密码；1：激活；2：提现审核；3：后台扣款；4：后台充值；5：转入；6：转出；7：汇款审核；8：购物；9：订单审核。默认为0',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。0：发送异常；1：正常发送。默认为1。',
                      `exception` VARCHAR(200) NOT NULL COMMENT '发送异常描述',
                      `send_time` DATETIME NOT NULL COMMENT '发送时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '短信表';";
		//创建验证码表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."auth_code` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
                      `code` VARCHAR(10) NOT NULL COMMENT '验证码',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型。0：忘记密码。默认为0',
                      `send_time` DATETIME NOT NULL COMMENT '发送时间',
                      `is_validate` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否验证。0：未验证；1：已验证。默认为0。',
                      `validate_time` DATETIME NULL COMMENT '验证时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '验证码表';";
		//创建商品分类表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."goods_category` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `parent_id` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级分类标识。顶级分类为0。',
                      `title` VARCHAR(20) NOT NULL COMMENT '分类名称（唯一）',
                      `path` VARCHAR(50) NOT NULL COMMENT '路径组合串，:分割。',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态。0：关闭；1：开启。默认为1',
                      `sort` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '商品分类表';";
		//创建商品表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."goods` (
                      `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `title` VARCHAR(20) NOT NULL COMMENT '商品名称',
                      `goods_category_id` SMALLINT UNSIGNED NOT NULL COMMENT '分类标识',
                      `pic_url` VARCHAR(200) NOT NULL COMMENT '商品小图，商品列表中展示',
                      `old_price` DECIMAL(10,2) NOT NULL COMMENT '商品原价',
                      `new_price` DECIMAL(10,2) NOT NULL COMMENT '商品现价',
                      `total_number` INT NOT NULL DEFAULT 0 COMMENT '商品总量',
                      `inventory_number` INT NOT NULL DEFAULT 0 COMMENT '库存量',
                      `sell_number` INT NOT NULL DEFAULT 0 COMMENT '已售数量',
                      `limit_number` SMALLINT NOT NULL DEFAULT 0 COMMENT '限购数量。为0表示不限购',
                      `warn_number` SMALLINT NOT NULL DEFAULT 0 COMMENT '预警数量',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品状态。0：未发布（下架）；1：发布（上架）。默认为0',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '商品表';";
		//创建商品附属表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."goods_extend` (
                      `goods_id` SMALLINT UNSIGNED NOT NULL COMMENT '唯一标识（商品标识）',
                      `big_pic_url` VARCHAR(200) NOT NULL COMMENT '商品展图，压缩或裁剪处理->小图',
                      `detail` MEDIUMTEXT NOT NULL COMMENT '商品展图，压缩或裁剪处理->小图',
                      `pictures` MEDIUMTEXT NOT NULL COMMENT '商品图片信息，商品图组json串',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`goods_id`))
                    ENGINE = InnoDB
                    COMMENT = '商品附属表';";
		//创建商品库存变更记录表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."goods_inventory` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `goods_id` SMALLINT UNSIGNED NOT NULL COMMENT '商品标识',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更类型。0：追加库存；1：库存损耗。默认为0',
                      `quantity` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更数量',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `update_time` DATETIME NOT NULL COMMENT '变更时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '商品库存变更记录表';";
		//创建收货地址表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."address` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `receiver` VARCHAR(20) NOT NULL COMMENT '收货人',
                      `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
                      `province` VARCHAR(20) NOT NULL COMMENT '省份',
                      `city` VARCHAR(20) NOT NULL COMMENT '市级',
                      `zone` VARCHAR(20) NOT NULL COMMENT '区级',
                      `address` VARCHAR(50) NOT NULL COMMENT '详细地址',
                      `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认地址。0：常规地址；1：默认地址。默认为0',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NOT NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '收货地址表';";
		//创建购物车表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."cart` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `goods_id` SMALLINT UNSIGNED NOT NULL COMMENT '商品标识',
                      `quantity` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '购买数量',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间',
                      `update_time` DATETIME NULL COMMENT '修改时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '购物车表';";
		//创建订单表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."order` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `order_no` VARCHAR(20) NOT NULL COMMENT '订单号',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `receiver` VARCHAR(20) NOT NULL COMMENT '收货人',
                      `receiver_phone` VARCHAR(20) NOT NULL COMMENT '收货人手机号',
                      `receiver_address` VARCHAR(100) NOT NULL COMMENT '收货人地址',
                      `remark` VARCHAR(100) NOT NULL COMMENT '订单备注',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '订单金额',
                      `shipping_set` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '运费设置。0：包邮；1：到付；2：运费代扣。默认为0',
                      `shipping` DECIMAL(10,2) NOT NULL COMMENT '运费',
                      `total` DECIMAL(10,2) NOT NULL COMMENT '订单总计。订单金额+运费',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态。0：已支付；1：已确认；2：已驳回。默认为0',
                      `operate_remark` VARCHAR(100) NOT NULL COMMENT '操作备注。确认时填写物流公司和物流单号；驳回时填写驳回原因。',
                      `add_time` DATETIME NOT NULL COMMENT '下单时间',
                      `operate_time` DATETIME NULL COMMENT '操作时间。订单确认/驳回的时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '订单表';";
		//创建订单商品表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."order_goods` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `order_id` INT UNSIGNED NOT NULL COMMENT '订单标识',
                      `goods_id` SMALLINT UNSIGNED NOT NULL COMMENT '商品标识',
                      `quantity` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '购买数量',
                      `price` DECIMAL(10,2) NOT NULL COMMENT '商品单价',
                      `subtotal` DECIMAL(10,2) NOT NULL COMMENT '订单总计',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '订单商品表';";
		//创建业绩记录表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."market_record` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `user_level_id` TINYINT UNSIGNED NOT NULL COMMENT '会员类型标识',
                      `market_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业绩类型。0：注册；1：升级。默认为0',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '金额',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态。0：新建；1：已结算。默认为0',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间。会员激活/升级成功的时间，也是产生业绩的时间，与奖金明细中产生时间一致',
                      `settle_time` DATETIME NULL COMMENT '结算时间。业绩结算的时间，与奖金明细中添加时间一致',
                      `return_number` SMALLINT UNSIGNED NOT NULL COMMENT '已返还次数',
                      `return_time` DATETIME NULL COMMENT '返本时间。返本的时间，初始为添加时间。当前时间-此时间>此会员类型对应的返还周期，进行返本处理',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '业绩记录表';";
		//创建返本记录表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."return_record` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `market_record_id` INT UNSIGNED NOT NULL COMMENT '业绩记录标志',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `user_level_id` TINYINT UNSIGNED NOT NULL COMMENT '会员类型标识',
                      `market_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业绩类型。0：注册；1：升级。默认为0',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '金额',
                      `add_time` DATETIME NOT NULL COMMENT '返本时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '返本记录表';";
		//创建奖金记录表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."reward_record` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `market_record_id` INT UNSIGNED NOT NULL COMMENT '业绩记录标志',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖项。0：对碰奖；1：推荐奖；2：领导奖；3：见点奖；4：报单奖；5：层奖；6：层碰奖。默认为0',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '金额',
                      `tax` DECIMAL(10,2) NOT NULL COMMENT '手续费',
                      `total` DECIMAL(10,2) NOT NULL COMMENT '小计',
                      `occur_time` DATETIME NOT NULL COMMENT '奖金产生时间。产生业绩的时间，与业绩记录中添加时间一致。奖项的按天封顶时间已此时间为准。',
                      `add_time` DATETIME NOT NULL COMMENT '添加时间。奖金结算的时间，与业绩记录中结算时间一致',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '奖金记录表';";
		//创建提现表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."withdraw` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `name` VARCHAR(20) NOT NULL COMMENT '开户支行',
                      `bank` VARCHAR(20) NOT NULL COMMENT '银行名称',
                      `sub_bank` VARCHAR(20) NOT NULL COMMENT '开户支行',
                      `bank_no` VARCHAR(20) NOT NULL COMMENT '银行卡号',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '提现金额',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态。0：未完成；1：已完成；2：已驳回。默认为0',
                      `add_time` DATETIME NOT NULL COMMENT '提现时间',
                      `operate_time` DATETIME NULL COMMENT '处理时间。确认/驳回的时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '提现表';";
		//创建汇款表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."remit` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `name` VARCHAR(20) NOT NULL COMMENT '开户支行',
                      `bank` VARCHAR(20) NOT NULL COMMENT '银行名称',
                      `sub_bank` VARCHAR(20) NOT NULL COMMENT '开户支行',
                      `bank_no` VARCHAR(20) NOT NULL COMMENT '银行卡号',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '提现金额',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态。0：未完成；1：已完成；2：已驳回。默认为0',
                      `remit_date` DATE NOT NULL COMMENT '汇款日期',
                      `add_time` DATETIME NOT NULL COMMENT '提现时间',
                      `operate_time` DATETIME NULL COMMENT '处理时间。确认/驳回的时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '汇款表';";
		//创建转账表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."giro` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `from_user_no` VARCHAR(20) NOT NULL COMMENT '转出会员编号',
                      `to_user_no` VARCHAR(20) NOT NULL COMMENT '转入会员编号',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型。0：会员；1：系统。默认为0',
                      `account_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '账户类型。0：EB；1：TB；2：CB；3：MB；4：RB。默认为0',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '提现金额',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `add_time` DATETIME NOT NULL COMMENT '转账时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '转账表';";
		//创建账户变更记录表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."account_record` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `user_no` VARCHAR(20) NOT NULL COMMENT '会员编号',
                      `account_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '账户类型。0：EB；1：TB；2：CB；3：MB；4：RB。默认为0',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更类型。0：增加；1：减少。默认为0',
                      `amount` DECIMAL(10,2) NOT NULL COMMENT '提现金额',
                      `balance` DECIMAL(10,2) NOT NULL COMMENT '账户余额',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `add_time` DATETIME NOT NULL COMMENT '变更时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '账户变更记录表';";
		//创建日志表
		$sql_arr[] = "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."log` (
                      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
                      `role` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户类型。0：会员；1：管理员。默认为0',
                      `username` VARCHAR(20) NOT NULL COMMENT '用户名（会员编号/管理员用户名）',
                      `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '日志类型。0：登录；1：操作。默认为0',
                      `remark` VARCHAR(100) NOT NULL COMMENT '备注',
                      `operate_time` DATETIME NOT NULL COMMENT '操作时间',
                      PRIMARY KEY (`id`))
                    ENGINE = InnoDB
                    COMMENT = '日志表';";
		//初始数据库(索引)
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `order_no_UNIQUE` ON `".$this->db_prefix."order` (`order_no` ASC);";
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `name_UNIQUE` ON `".$this->db_prefix."config_group` (`title` ASC);";
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `code_UNIQUE` ON `".$this->db_prefix."config` (`code` ASC);";
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `title_UNIQUE` ON `".$this->db_prefix."user_level` (`title` ASC);";
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `name_UNIQUE` ON `".$this->db_prefix."auth_rule` (`name` ASC);";
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `title_UNIQUE` ON `".$this->db_prefix."auth_group` (`title` ASC);";
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `username_UNIQUE` ON `".$this->db_prefix."admin` (`username` ASC);";
		$sql_arr[] = "CREATE UNIQUE INDEX IF NOT EXISTS `username_UNIQUE` ON `".$this->db_prefix."user` (`user_no` ASC);";

		return $sql_arr;
	}
}