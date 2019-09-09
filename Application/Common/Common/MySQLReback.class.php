<?php
namespace Common\Common;

/**
 * 数据库备份处理封装类
 *
 * @since: 2016年12月19日 下午4:28:29
 * @author: lyx
 * @version: V1.0.0
 */
class MySQLReback {
    private $config;
    private $content;
    private $dbName = array();
    const DIR_SEP = DIRECTORY_SEPARATOR;
    
    /**
    * 构造函数
    *
    * @param    array   $config    数据库配置信息
    *
    * @since: 2016年12月20日 上午10:38:41
    * @author: lyx
    */
    public function __construct($config) {
        $this->config = $config;
        header("Content-type: text/html;charset=utf-8");
        $this->connect();
    }
    
    /**
    * 连接数据库
    *
    * @since: 2016年12月20日 上午10:39:24
    * @author: lyx
    */
    private function connect() {
        if (mysql_connect($this->config['host'] . ':' . $this->config['port'], $this->config['userName'], $this->config['userPassword'])) {
            mysql_query("SET NAMES '{$this->config['charset']}'");
            mysql_query("set interactive_timeout=24*3600");
            mysql_query("SET FOREIGN_KEY_CHECKS=0;");//取消外键约束
        } else {
            $this->throwException('无法连接到数据库!');
        }
    }
    
    /**
    * 设置数据库名称
    *
    * @param    string    $dbName    数据库名称
    *
    * @since: 2016年12月20日 上午10:40:54
    * @author: lyx
    */
    public function setDBName($dbName = '*') {
        if ($dbName == '*') {
            $rs   = mysql_list_dbs();
            $rows = mysql_num_rows($rs);
            if ($rows) {
                for ($i = 0; $i < $rows; $i++) {
                    $dbName = mysql_tablename($rs, $i);
                    $block  = array(
                        'information_schema',
                        'mysql'
                    );
                    if (!in_array($dbName, $block)) {
                        $this->dbName[] = $dbName;
                    }
                }
            } else {
                $this->throwException('没有任何数据库!');
            }
        } else {
            $this->dbName = func_get_args();
        }
    }

    /**
    * 给字符串添加 ``引用
    *
    * @param    string  $str    需要引用的字符串
    * @return   string  `$str`
    *
    * @since: 2016年12月20日 上午10:41:54
    * @author: lyx
    */
    private function backquote($str) {
        return "`{$str}`";
    }
    
    /**
    * 获取数据库的所有表
    *
    * @param    string  $dbName     数据库名称
    * @return   array   表名称数组
    *
    * @since: 2016年12月20日 上午10:44:03
    * @author: lyx
    */
    private function getTables($dbName) {
        @$rs = mysql_list_tables($dbName);
        $rows     = mysql_num_rows($rs);
        $dbprefix = $this->config['dbprefix'];
        for ($i = 0; $i < $rows; $i++) {
            $tbName = mysql_tablename($rs, $i);
            if (substr($tbName, 0, strlen($dbprefix)) == $dbprefix) {
                $tables[] = $tbName;
            }
        }
        return $tables;
    }
    
    /**
    * 把数据按指定长度分割成数组
    *
    * @param    array   $array  要分割的数据
    * @param    int     $byte   要分割的长度
    * @return   array   分割后的数组
    *
    * @since: 2016年12月20日 上午10:59:28
    * @author: lyx
    */
    private function chunkArrayByByte($array, $byte = 5120) {
        $i   = 0;
        $sum = 0;
        foreach ($array as $v) {
            $sum += strlen($v);
            if ($sum < $byte) {
                $return[$i][] = $v;
            } elseif ($sum == $byte) {
                $return[++$i][] = $v;
                $sum            = 0;
            } else {
                $return[++$i][] = $v;
                $i++;
                $sum = 0;
            }
        }
        return $return;
    }
    
    /**
    * 备份数据库
    *
    * @since: 2016年12月20日 上午10:32:48
    * @author: lyx
    */
    public function backup() {
        //解析当前数据库，封装备份文件内容
        $this->content = "/* This file is created by MySQLReback " . date('Y-m-d H:i:s') . " */";
        $this->content .= "\r\n /* MySQLReback Separation */\r\nSET FOREIGN_KEY_CHECKS=0;";
        foreach ($this->dbName as $dbName) {
            $qDbName = $this->backquote($dbName);
            $rs      = mysql_query("SHOW CREATE DATABASE {$qDbName}");
            if ($row = mysql_fetch_row($rs)) {
                mysql_select_db($dbName);
                //导出数据库中的表
                $tables = $this->getTables($dbName);
                foreach ($tables as $table) {
                    $table   = $this->backquote($table);
                    $tableRs = mysql_query("SHOW CREATE TABLE {$table}");                    
                    if ($tableRow = mysql_fetch_row($tableRs)) {
                    	$this->content .= "\r\n /* MySQLReback Separation */";
                        $this->content .= "\r\n /* 创建表结构 {$table} */";
                        $this->content .= "\r\n DROP TABLE IF EXISTS {$table};";
                        //$this->content .= "\r\n FLUSH TABLES;";//清空数据库缓存
                        $this->content .= "\r\n /* MySQLReback Separation */\r\n{$tableRow[1]};\r\n/* MySQLReback Separation */";
                        //解析生成数据库表中的数据
                        $tableDateRs = mysql_query("SELECT * FROM {$table}");
                        $valuesArr   = array();
                        $values      = '';
                        while ($tableDateRow = mysql_fetch_row($tableDateRs)) {
                            foreach ($tableDateRow as &$v) {
	                            if($v!=""){
		                			$v = "'" . addslashes($v) . "'";
		                		}else{
		                			$v = "'" . $v . "'";
		                		}
                            }
                            $valuesArr[] = '(' . implode(',', $tableDateRow) . ')';
                        }
                        
                        $temp = $this->chunkArrayByByte($valuesArr);
                        if (is_array($temp)) {
                            foreach ($temp as $v) {
                                $values = implode(',', $v) . ";\r\n/* MySQLReback Separation */";
                                if ($values != ";/* MySQLReback Separation */") {
                                	$this->content .= "\r\n SET NAMES '{$this->config['charset']}';\r\n/* MySQLReback Separation */";
                                    $this->content .= "\r\n /* 插入数据 {$table} */";
                                    $this->content .= "\r\n INSERT INTO {$table} VALUES {$values}";
                                }
                            }
                        }
                    }
                }
                //导出数据库中的函数
                $table = "mysql.proc";
                $sql = "select * from {$table} where db = '{$dbName}' and `type` = 'FUNCTION' ";
	    		$funcs = M()->query($sql);
                foreach ($funcs as $func) {
               		$valuesArr   = array();
                   	$values      = '';
                   	
                   	$this->content .= "\r\n SET NAMES '{$this->config['charset']}';\r\n/* MySQLReback Separation */";
                   	$this->content .= "\r\n DROP FUNCTION IF EXISTS  {$dbName}.{$func[name]};\r\n/* MySQLReback Separation */";
                   	
                  	foreach ($funcs as $func) {
                  		foreach ($func as &$v) {
                			$v = "'" . addslashes($v) . "'";
	                   	}
	                 	$valuesArr[] = '(' . implode(',', $func) . ')';
                  	}
                 	$temp = $this->chunkArrayByByte($valuesArr);
                 	if (is_array($temp)) {
                  		foreach ($temp as $v) {
                  			$values = implode(',', $v) . ';/* MySQLReback Separation */';
                      		if ($values != ';/* MySQLReback Separation */') {
                            	$this->content .= "\r\n SET NAMES '{$this->config['charset']}';\r\n/* MySQLReback Separation */";
                             	$this->content .= "\r\n /* 插入数据 {$table} */";
                             	$this->content .= "\r\n INSERT INTO {$table} VALUES {$values}";
                           	}
                       	}
                    }
                }
            } else {
                $this->throwException('未能找到数据库!');
            }
        }
        $this->content .= "\r\n SET FOREIGN_KEY_CHECKS=1;";
        
        //备份文件保存
        if (!empty($this->content)) {
	        $savePath =$this->config['savePath'];
			if(!is_dir($savePath)) {
				mkdir($savePath);
			}
			$content = $this->content;
	        $db_name = implode('_', $this->dbName);
	        $fileName  = $savePath . $db_name . '_' . date('YmdHis') . '.sql';
	        //echo $fileName;
	      	if (!file_put_contents($fileName, $content, LOCK_EX)) {
	      		$this->throwException('写入文件失败,请检查磁盘空间或者权限!');
			}
        }
        return true;
    }
    
    /**
    * 还原数据库
    * 
    * @param    string  $fileName  备份文件绝对访问路径
    *
    * @since: 2016年12月20日 上午10:34:06
    * @author: lyx
    */
    public function recover($fileName) {
        //打开备份文件
    	$curl = curl_init($fileName);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$this->content = curl_exec($curl);
        if (!empty($this->content)) {
            //解析备份，并执行sql
            $content = explode('/* MySQLReback Separation */', $this->content);
            foreach ($content as $i => $sql) {
                $sql = trim($sql);
                if (!empty($sql)) {
                    $dbName = $this->dbName[0];
                    if (!mysql_select_db($dbName))  $this->throwException('不存在的数据库!' . mysql_error());
                    $rs = mysql_query($sql);
                    if ($rs) {
                        if (strstr($sql, 'CREATE DATABASE')) {
                            $dbNameArr = sscanf($sql, 'CREATE DATABASE %s');
                            $dbName    = trim($dbNameArr[0], '`');
                            mysql_select_db($dbName);
                        }
                    } else {
                        $this->throwException('备份文件被损坏!' . mysql_error());
                    }
                    
                }
            }
        } else {
            $this->throwException('无法读取备份文件!');
        }
        return true;
    }
    
    /**
    * 自定义异常
    *
    * @since: 2016年12月20日 上午10:33:30
    * @author: lyx
    */
    private function throwException($error) {
        //throw new Exception($error);
        echo $error;
    }
}
?>