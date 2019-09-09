<?php

namespace Common\Model;

use Common\Model\BaseModel;
use Common\Conf\Constants;

class MessageTemplateModel extends BaseModel
{
	// 自动验证
	protected $_validate = [
		['no', 'require', '短信模板编号不能为空', self::MODEL_BOTH],
		['no', 'checklen', '短信模板编号必须在2~20个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)],
		['no', 'checkUnique', '短信模板编号已经存在', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH],
		['msg', 'require', '短信模板内容不能为空', self::MODEL_BOTH],
	];

	// 自动完成
	protected $_auto = [
		['add_time', 'curr_time', self::MODEL_INSERT, 'callback'],
		['update_time', 'curr_time', self::MODEL_BOTH, 'callback'],
	];

	public function getList($where, $parameter, $page_number)
    {
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id asc', $page_number);
        return $data;
    }

    protected function checkUnique()
    {
	    $item = $this->getByNo(I('no'));
	    if ($item) {
	        return $item['no'] == I('no') ? true : false;
	    } else {
	        return true;
	    }
	}
}