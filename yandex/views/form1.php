<html> 
    <head>
    </head> 
    <body onload="document.getElementById('form').submit();">
        <form id="form"  method="post" action="https://money.yandex.ru/quickpay/confirm.xml" accept-charset="UTF-8">
            <?php
            foreach ($fields as $key => $value) {
                echo '<input type="hidden" name="' . $key . '" value="' . $value . '">' . PHP_EOL;
            }
            ?>
        </form>
    </body>
</html>