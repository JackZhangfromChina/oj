<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;
use Common\Library\PHPExcel;

/**
 * 汇款 Model
 *
 * @since: 2016年12月13日 下午3:19:25
 * @author: lyx
 * @version: V1.0.0
 */
class RemitModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('user_no', 'require', '会员编号必须填写', self::MUST_VALIDATE),
            array('name', 'require', '真实姓名必须填写', self::MUST_VALIDATE),
            array('bank', 'require', '银行名称必须填写', self::MUST_VALIDATE),
            array('sub_bank', 'require', '开户支行必须填写', self::MUST_VALIDATE),
            array('bank_no', 'require', '银行卡号必须填写', self::MUST_VALIDATE),
            array('amount', 'require', '汇款金额必须填写', self::MUST_VALIDATE),
            array('remit_date', 'require', '汇款日期必须填写', self::MUST_VALIDATE),
            array('remark', 'require', '汇款备注必须填写', self::MUST_VALIDATE),
    );
    
    //自动完成
    protected $_auto = array (
            array('status',Constants::OPERATE_STATUS_INITIAL,self::MODEL_INSERT),  // 新增时候status字段设置为0
            array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
    );
    
    /**
     * 汇款记录
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *                   statistics 统计对象
     *
     * @since: 2016年12月30日 下午4:56:00
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
    
        //统计数据
        if (!array_key_exists('status',$where)) {
            $where['status'] = Constants::OPERATE_STATUS_CONFIRM;
        }
        if ($where['status'] == Constants::OPERATE_STATUS_CONFIRM) {
            $data['statistics'] = $this->where($where)
                                        ->field('SUM(amount) as sum_total')
                                        ->find();
        }
        
        return $data;
    }

    /**
     * 汇款信息 Model
     *
     * @since: 2016年12月13日 下午3:15:21
     * @author: Wang Peng
     * @version: V1.0.0
     */
    public function getExcelData($data){
        //导入php插件文件
        require './Application/Common/Library/PHPExcel.php';

        //phpexcel操作对象
        $phpexcel = new \PHPExcel;

        //制表头
        $phpexcel->getActiveSheet()->setCellValue('A1', '会员编号');
        $phpexcel->getActiveSheet()->setCellValue('B1', '银行信息');
        $phpexcel->getActiveSheet()->setCellValue('C1', '汇款金额');
        $phpexcel->getActiveSheet()->setCellValue('D1', '状态');
        $phpexcel->getActiveSheet()->setCellValue('E1', '备注');
        $phpexcel->getActiveSheet()->setCellValue('F1', '汇款日期');
        $phpexcel->getActiveSheet()->setCellValue('G1', '申请时间');
        $phpexcel->getActiveSheet()->setCellValue('H1', '处理时间');
        
        //验证订单状态
        if($data['status'] == Constants::OPERATE_STATUS_INITIAL || $data['status'] == Constants::OPERATE_STATUS_CONFIRM || $data['status'] == Constants::OPERATE_STATUS_REJECT){
           $info['status'] = $data['status'];
        }
        
        //验证搜索时间
        if(!empty($data['date_start']) && !empty($data['date_end'])){
             $info['add_time']=array(
                    array('EGT', $data['date_start'] . ' 00:00:00'),
                    array('ELT', $data['date_end'] . ' 23:59:59')
             );
        }elseif (!empty($data['date_start'])) {
              $info['add_time'] = array('EGT', $data['date_start'] . ' 00:00:00');
              
        }elseif (!empty($data['date_end'])) {
             $info['add_time'] = array('ELT', $data['date_end'] . ' 23:59:59');
        }

        //验证会员编号
        if(!empty($data['keyword'])){
            $info['user_no'] = array('like','%' . $data['keyword'] . '%');
        }

        //获取汇款信息
        $remitinfo = $this->where($info)->select();
        $symbol = M('Config')->where(array('code'=>'SYSTEM_CURRENCY_SYMBOL'))->getField('value');
        
        if($remitinfo){
            $i=2;
            foreach ($remitinfo as $key=>$info){
                if($info['status'] == Constants::OPERATE_STATUS_INITIAL){
                    $status = '未处理';
                }else if($info['status'] == Constants::OPERATE_STATUS_CONFIRM){
                    $status = '已通过';
                }else if($info['status'] == Constants::OPERATE_STATUS_REJECT){
                    $status = '已驳回';
                }

                $str = "真实姓名：".$info['name'].chr(10)."银行：".$info['bank'].chr(10)." 支行：".$info['sub_bank'].chr(10)." 卡号：".$info['bank_no'];

                $phpexcel->getActiveSheet()->setCellValue('A' . $i, $info['user_no']);

                $phpexcel->getActiveSheet()->setCellValue('B'. $i, $str); 
                $phpexcel->getActiveSheet()->getStyle('B')->getAlignment()->setWrapText(true);  

                $phpexcel->getActiveSheet()->setCellValue('C' . $i, $symbol.$info['amount']);
                $phpexcel->getActiveSheet()->setCellValue('D' . $i, $status);
                $phpexcel->getActiveSheet()->setCellValue('E' . $i, $info['remark']);
                $phpexcel->getActiveSheet()->setCellValue('F' . $i, $info['remit_date']);
                $phpexcel->getActiveSheet()->setCellValue('G' . $i, $info['add_time']);
                $phpexcel->getActiveSheet()->setCellValue('H' . $i, $info['operate_time']);
                $i++;
            }
        }

        return $phpexcel;
    }
}