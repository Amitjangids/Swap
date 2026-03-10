<!DOCTYPE html>
<html>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style type="text/css">
        body {
            font-family: 'Sora', sans-serif;
            padding: 0;
            margin: 0;
            display: flex; 
            align-items: center;
        }

        .logo_main {
            width: 150px;
        }
        .logo_main img {
            width: 100%;
        }

        .radio-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: auto;
        }

        .radio-card input[type="radio"]+.radio-label:before {
            content: '';
            background-color: #cfa446;
            box-shadow: inset 0 0 0 4px #fff;
            border-radius: 100%;

            display: inline-block;
            width: 24px;
            height: 24px;
            position: relative;
            top: 0;
            margin-right: 1em;
            vertical-align: top;
            cursor: pointer;
            text-align: center;
            -webkit-transition: all 250ms ease;
            transition: all 250ms ease;
        }

        .radio-card label {
            margin-bottom: 0
        }

        .radio-card input[type="radio"]:checked+.radio-label:before {
            background-color: #cfa446;
            box-shadow: inset 0 0 0 4px #fff;
        }

        .radio-card input[type="radio"]:focus+.radio-label:before {
            outline: none;
            border-color: #3197EE;
        }

        .radio-card input[type="radio"]:disabled+.radio-label:before {
            box-shadow: inset 0 0 0 4px #f4f4f4;
            border-color: #b4b4b4;
            background: #b4b4b4;
        }

        .radio-card input[type="radio"]+.radio-label:empty:before {
            margin-right: 0;
        }

        .box-pay {
            background-image: linear-gradient(to top, rgba(255, 255, 255, 0), rgba(251, 227, 130, 0.3));
            display: inline-flex;
            padding: 30px;
            align-items: center;
            margin-bottom: 10px;
            width: 100%;
            justify-content: space-between;
            box-sizing: border-box;
        }
        form {
            display: flex;
            align-items: center;
            width: 100%;
            flex-direction: column;
        }
        .sub-btn {
            padding: 8px 20px;
            background: #000;
            border: none;
            color: #fff;
            border-radius: 30px;
            cursor: pointer;
        }

        .logo {
            margin: 0 20px;
        }
        .pay-dis{background: #f1f1f1 !important;}
        .text-center{text-align: center;}

        .btn-grad:hover {
            box-shadow: 0 11px 10px -16px rgba(0, 0, 0, 0.2);
            transition: .3s all ease;
        }

        .btn-one {
            margin-top: 15px;
        }

        .btn-grad {
            margin-top: 24px;
            width: 100%;
            border: none;
            font-size: 22px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 10px;
            padding: 14px;
            cursor: pointer;
            box-shadow: 0 11px 38px -16px rgba(0, 0, 0, 0.3);
            transition: .3s all ease;
        }

        .grad-two {
            background: #c99b3d;
            background: -moz-linear-gradient(left, #c99b3d 0%, #fbe382 50%, #c99b3d 99%);
            background: -webkit-linear-gradient(left, #c99b3d 0%, #fbe382 50%, #c99b3d 99%);
            background: linear-gradient(to right, #c99b3d 0%, #fbe382 50%, #c99b3d 99%);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#c99b3d', endColorstr='#c99b3d', GradientType=1);
        }
    </style>

    <body>
        <div style="width: 300px;margin: 0 auto; padding-top: 50px;">
            <h4>Select Payment Option</h4>
            <form action="<?php echo HTTP_PATH; ?>/charges" method="post">
                <div class="box-pay">
                    <div class="radio-card">
                        <input id="radio-2" name="payment_method" type="radio" value="card_transfer">
                        <label for="radio-2" class="radio-label"></label>
                    </div>
                    <div class="logo_main">
                        <img src="<?php echo PUBLIC_PATH; ?>/img/logo.png">
                    </div>
                    <input type="hidden" name="order_id" value="10055567">
                    <input type="hidden" name="order_amount" value="100">
                    <input type="hidden" name="currency_code" value="IQD">
                    <input type="hidden" name="merchant_key" value="EKHcgjUhAjbd">
                    <input type="hidden" name="return_url" value="<?php echo HTTP_PATH; ?>/success">

                </div>

                <div class="box-pay pay-dis">
                    <div class="radio-card">
                        <input id="radio-2" name="payment_method" type="radio" value="card_transfer">
                        <label for="radio-2" class="radio-label"></label>
                    </div>
                    <div class="logo">
                        <img src="{{HTTP_PATH}}/public/img/front/visa-logo.png">
                    </div>

                </div>
                <div class="text-center">
                    <input type="submit" name="submit" value="Submit" class="btn-grad grad-two btn-one">
                </div>
            </form>
        </div>
    </body>

</html>