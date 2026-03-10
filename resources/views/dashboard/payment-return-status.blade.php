<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        height: 100vh;
        display: flex;
        font-size: 14px;
        text-align: center;
        justify-content: center;
        align-items: center;
        font-family: 'Khand', sans-serif;
    }

    .wrapperAlert {
        width: 500px; 
        overflow: hidden;
        border-radius: 12px;
        border: thin solid #ddd;
    }

    .topHalf {
        width: 100%;
        color: white;
        overflow: hidden;
        min-height: 250px;
        position: relative;
        padding: 40px 0;
        background: rgb(0, 0, 0);
        background: -webkit-linear-gradient(45deg, #019871, #a0ebcf);
    }

    .topHalf1 {
        width: 100%;
        color: white;
        overflow: hidden;
        min-height: 250px;
        position: relative;
        padding: 40px 0;
        background: rgb(0, 0, 0);
        background: red;
    }

    .topHalf p {
        margin-bottom: 30px;
    }

    svg {
        fill: white;
    }

    .topHalf h1 {
        font-size: 2.25rem;
        display: block;
        font-weight: 500;
        letter-spacing: 0.15rem;
        text-shadow: 0 2px rgba(128, 128, 128, 0.6);
    }


    .bottomHalf {
        align-items: center;
        padding: 35px;
    }

    .bottomHalf p {
        font-weight: 500;
        font-size: 1.05rem;
        margin-bottom: 20px;
    }

    button {
        border: none;
        color: white;
        cursor: pointer;
        border-radius: 12px;
        padding: 10px 18px;
        background-color: #019871;
        text-shadow: 0 1px rgba(128, 128, 128, 0.75);
    }

    button:hover {
        background-color: #85ddbf;
    }
</style>
<div class="row">
    <div class="col-md-12">
        <?php if (session()->has('success_message')) { ?>
        <div class="wrapperAlert">

            <div class="contentAlert">

                <div class="topHalf">

                    <p><svg viewBox="0 0 512 512" width="100" title="check-circle">
                            <path
                                d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z" />
                        </svg></p>
                    <h1>{{Session::get('success_message')}}</h1>

                </div>
                <a href="{{HTTP_PATH}}/initPayment?merchantId={{$merchant_id_payment}}&orderId={{ $order_id_payment }}" class="btn btn-success" style="margin:20px;">Go Back</a>
            </div>
        </div>
        <?php    Session::forget('success_message');
} ?>

        <?php if (session()->has('error_message')) { ?>
        <div class="wrapperAlert">

            <div class="contentAlert">

                <div class="topHalf1">

                    <p>
                        <svg viewBox="0 0 512 512" width="100" title="times-circle">
                            <path
                                d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm124.284 331.716c6.248 6.248 6.248 16.379 0 22.627l-22.627 22.627c-6.248 6.248-16.379 6.248-22.627 0L256 278.627l-79.03 79.03c-6.248 6.248-16.379 6.248-22.627 0l-22.627-22.627c-6.248-6.248-6.248-16.379 0-22.627l79.03-79.03-79.03-79.03c-6.248-6.248-6.248-16.379 0-22.627l22.627-22.627c6.248-6.248 16.379-6.248 22.627 0l79.03 79.03 79.03-79.03c6.248-6.248 16.379-6.248 22.627 0l22.627 22.627c6.248 6.248 6.248 16.379 0 22.627l-79.03 79.03 79.03 79.03z" />
                        </svg>
                    </p>
                    <h1>{{Session::get('error_message')}}</h1>

                </div>
                <a href="{{$merchant_id_payment}}"></a>
                <a href="{{HTTP_PATH}}/initPayment?merchantId={{$merchant_id_payment}}&orderId={{ $order_id_payment }}" class="btn btn-success" style="margin:20px;">Go Back</a>

            </div>
        </div>
        <?php    Session::forget('error_message');
} ?>
    </div>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
