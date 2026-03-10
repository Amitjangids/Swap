<!DOCTYPE html>
<html>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style type="text/css">
        .widt-req {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-family: 'Sora', sans-serif;
        }
        .widt-req form {
            display: flex;
            flex-direction: column;
            width: 30%;
            background: #fff;
            padding: 60px;
            box-shadow: 0 0 16px rgb(0 0 0 / 10%);
            border-radius: 25px;
        }
        .widt-req h4{font-size: 28px;}
        label{font-size: 16px; font-weight: 600; margin-bottom: 10px;}
        input{font-size: 16px; border: 1px solid #000;height: 40px;padding: 5px 10px; border-radius: 10px; margin-bottom: 15px; }
        textarea{font-size: 16px; border: 1px solid #000;height: 80px;padding: 5px 10px; border-radius: 10px; margin-bottom: 15px; }
        .submit{display: inline-block; background: #000; width: auto; color: #fff; width: 150px; margin: 0 auto; cursor: pointer;}
        
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
            /*padding: 14px;*/
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
    <div class="widt-req">
        <h4>Withdrawal Request</h4>

        <form action="<?php echo HTTP_PATH; ?>/merchant-withdrawal" method="post">

            <input type="hidden" name="merchant_key" value="rdtnRLupjXn5">
            <label>Order ID :</label>
            <input type="text" name="order_id" value="Order-0709" placeholder="" disable>
            <label>Mobile Number :</label>
            <input type="text" name="user_phone" value="" placeholder="">
            <label>Amount :</label>
            <input type="text" name="amount" value="100">

            <input type="hidden" name="return_url" value="<?php echo HTTP_PATH; ?>/success">
            <input type="submit" name="submit" value="Submit" class="btn-grad grad-two btn-one">
        </form>

    </div>

</html>