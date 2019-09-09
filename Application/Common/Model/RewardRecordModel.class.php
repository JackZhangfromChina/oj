<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Library\PHPExcel;
use Common\Conf\Constants;

/**
 * 奖金记录 Model
 *
 * @since: 2016年12月13日 下午3:21:12
 * @author: lyx
 * @version: V1.0.0
 */
class RewardRecordModel extends BaseModel{
    
    /**
     * 奖金列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *                   statistics 统计对象
     *
     * @since: 2016年12月30日 上午10:24:48
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
        
        //统计数据
        $data['statistics'] = $this->where($where)
                            ->field('SUM(amount) as sum_amount,SUM(tax) as sum_tax,SUM(total) as sum_total')
                            ->find();
        return $data;
    }
    
    /**
     * 奖金报表
     *
     * @param    array   $where          查询的条件
     * @return   array
     *                   data       报表数据
     *                   statistics 统计对象
     *
     * @since: 2016年12月30日 下午3:30:16
     * @author: lyx
     */
    public function getReport($where){
    
        $data['report'] = $this->field("DATE_FORMAT(add_time,'%Y-%m-%d') AS date, SUM(total) AS s_total")->where($where)->group('date')->select();
    
        //统计数据
        $data['statistics'] = $this->where($where)
                                    ->field('SUM(amount) as sum_total')
                                    ->find();
        return $data;
    }


    /**
     * 奖金信息 Model
     *
     * @since: 2017年2月10日 下午3:15:21
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
        $phpexcel->getActiveSheet()->setCellValue('B1', '奖金类型');
        $phpexcel->getActiveSheet()->setCellValue('C1', '金额');
        $phpexcel->getActiveSheet()->setCellValue('D1', '手续费');
        $phpexcel->getActiveSheet()->setCellValue('E1', '小计');
        $phpexcel->getActiveSheet()->setCellValue('F1', '备注');
        $phpexcel->getActiveSheet()->setCellValue('G1', '奖金时间');
        
        //验证奖金类型
        if($data['type'] == Constants::REWARD_TYPE_TOUCH || $data['type'] == Constants::REWARD_TYPE_RECOMMEND || $data['type'] == Constants::REWARD_TYPE_LEADER || $data['type'] == Constants::REWARD_TYPE_POINT || $data['type'] == Constants::REWARD_TYPE_DECLARATION || $data['type'] == Constants::REWARD_TYPE_LAYER || $data['type'] == Constants::REWARD_TYPE_TOUCHLAYER){
           $info['type'] = $data['type'];
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

        //获取奖金信息
        $remitinfo = $this->where($info)->select();
        $symbol = M('Config')->where(array('code'=>'SYSTEM_CURRENCY_SYMBOL'))->getField('value');

        if($remitinfo){
            $i=2;
            foreach ($remitinfo as $key=>$info){
                if($info['type'] == Constants::REWARD_TYPE_TOUCH){
                    $type = '对碰奖';
                }else if($info['type'] == Constants::REWARD_TYPE_RECOMMEND){
                    $type = '推荐奖';
                }else if($info['type'] == Constants::REWARD_TYPE_LEADER){
                    $type = '领导奖';
                }else if($info['type'] == Constants::REWARD_TYPE_POINT){
                    $type = '见点奖';                    
                }else if($info['type'] == Constants::REWARD_TYPE_DECLARATION){
                    $type = '报单奖';
                }else if($info['type'] == Constants::REWARD_TYPE_LAYER){
                    $type = '层奖';
                }else if($info['type'] == Constants::REWARD_TYPE_TOUCHLAYER){
                    $type = '层碰奖';
                }

                $phpexcel->getActiveSheet()->setCellValue('A' . $i, $info['user_no']);
                $phpexcel->getActiveSheet()->setCellValue('B' . $i, $type);
                $phpexcel->getActiveSheet()->setCellValue('C' . $i, $symbol.$info['amount']);
                $phpexcel->getActiveSheet()->setCellValue('D' . $i, $symbol.$info['tax']);
                $phpexcel->getActiveSheet()->setCellValue('E' . $i, $symbol.$info['total']);
                $phpexcel->getActiveSheet()->setCellValue('F' . $i, $info['remark']);
                $phpexcel->getActiveSheet()->setCellValue('G' . $i, $info['occur_time']);
                $i++;
            }
        }

        return $phpexcel;
    }
}