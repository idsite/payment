<html> 
<head>
</head> 
<body onload="document.getElementsByName('PaymentForm')[0].submit();">
<form  name="PaymentForm" action="http://<?=$test?'playground.paymentgate.ru':'engine.paymentgate.ru'?>/bpcservlet/BPC/AcceptPayment.jsp">
   <?php 
   echo CHtml::hiddenField('MDORDER', $MDORDER);
   ?>
</form>
    <p>Подождите..</p>
</body>
</html>