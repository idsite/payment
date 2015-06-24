<html> 
<head>
</head> 
<body onload="document.getElementById('form').submit();">
<form id="form" method="POST" action="https://merchant.webmoney.ru/lmi/payment.asp">  
  <input type="hidden" name="LMI_PAYMENT_AMOUNT" value="<?=$summ?>">
  <input type="hidden" name="LMI_PAYMENT_DESC" value="<?=CHtml::encode($desc)?>">
  <input type="hidden" name="LMI_PAYEE_PURSE" value="<?=CHtml::encode($purse)?>">
  <input type="hidden" name="LMI_SIM_MODE" value="2">
  <input type="hidden" name="LMI_PAYMENT_NO" value="<?=$pay_id?>">
<!--  <input type="hidden" name="FIELD_ID" value="<?=$pay_id?>"> -->
</form>
</body>
</html>