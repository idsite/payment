<?php
class PayRobokassa extends PayMetod {
   public $metodName='robokassa';
   private $test=true;
   public function goPay($summ, $desc, $item_id, $email=null,$data = array()) {
       $di=array();
       $di[$this->fieldCompleted]=0;
       $di[$this->fieldDesc]=$desc;
       $di[$this->fieldSumm]=$summ;
       $di[$this->fieldItem_id]=$item_id;
       
       if (!empty($data))
           $di[$this->fieldData]=$data;
       
       if ( ($r=$this->insertData($di)))
       {
           $crc = md5("{$this->dataPay['mrh_login']}:$summ:$r:{$this->dataPay['mrh_pass1']}");
            $url =  ($this->test ? "http://test.robokassa.ru/Index.aspx" : "https://merchant.roboxchange.com/Index.aspx") . "?MrchLogin=" . urlencode($this->dataPay['mrh_login']) . "&OutSum=" . urlencode($summ) . "&InvId=" . urlencode($r) . "&Desc=" . urlencode($desc) . "&SignatureValue=" . urlencode($crc);

            if (!empty($email))
                $url.='&sEmail=' . urlencode($email);
            Yii::app()->controller->redirect($url);
            exit();
       }
       
       throw new CHttpException(400, 'goPay');
   }
   public function isPay() {
              $out_summ = $_REQUEST["OutSum"];
        $inv_id = $_REQUEST["InvId"];
        $crc = strtoupper($_REQUEST["SignatureValue"]);

        $my_crc = strtoupper(md5("$out_summ:$inv_id:" .$this->dataPay['mrh_pass1']));

       // echo $my_crc.' '.$crc;
       // exit();
 
        if ($my_crc === $crc) {
      
            $d=$this->getDatById($inv_id);
            
            
            if ($d && floatval($d[$this->fieldSumm]) == floatval($out_summ)) {
                return $this->formatData($d);
            }
        }
   }
   public function isPayOK() {
       
           $out_summ = $_REQUEST["OutSum"];
        $inv_id = $_REQUEST["InvId"];
        $crc = strtoupper($_REQUEST["SignatureValue"]);

        $my_crc = strtoupper(md5("$out_summ:$inv_id:" .$this->dataPay['mrh_pass2']));

       
        if ($my_crc === $crc) {
      
            $d=$this->getDatById($inv_id);
           
            
            if ($d && floatval($d[$this->fieldSumm]) == floatval($out_summ)) {
                return $this->formatData($d);
            }
        }
        return false;
   }
   public function errorPay() {
       echo 'not fond';
       exit();
   }
   public function payCompleted($id = null) {
       if ($id==null && isset($_REQUEST["InvId"]))
           $id=$_REQUEST["InvId"];
       $this->db->createCommand()->update($this->tableName, array($this->fieldCompleted=>1),'id='.  intval($id));
echo "OK" . $id . "\n";
       
   }
   public function getDataPay($id = null) {
       
       if ($id==null && isset($_REQUEST["InvId"]))
           $id=$_REQUEST["InvId"];
       if ($id!==null)  return  $this->formatData($this->getDatById($id));
      
        return null;
       
   }
   
}

