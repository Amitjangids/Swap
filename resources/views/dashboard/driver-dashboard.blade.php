@extends('layouts.login')
@section('content')
    <style type="text/css">
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }

        .toggle-password i {
            font-size: 18px;
        }


        div#message .alert.alert-danger.alert-dismissible.fade.show {
            width: 100%;
            position: relative;
            font-size: 14px;
            padding: 16px;
        }
    </style>

    <section class="same-section login-page dashboard-driver-section">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="login-content-wrapper">
                        <div class="login-content-parent">
                            <a href="javascript:void(0)">
                                <img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image">
                            </a>
                            <h2>Card Delivered</h2>
                        </div>
                        {{ Form::open(array('method' => 'post', 'id' => 'loginform', 'class' => 'form form-signin')) }}

                        <div class="login-from-parent">
                            <!-- <div class="login-otp-wrapper">
                                    <div class="login-otp-boc">
                                        <input class="required digits opt_input d0" type="text" name="otp1" autocomplete="off" maxlength="1">
                                        <input class="required digits opt_input d1" type="text" name="otp2" autocomplete="off" maxlength="1">
                                        <input class="required digits opt_input d2" type="text" name="otp3" autocomplete="off" maxlength="1">
                                        <input class="required digits opt_input d3" type="text" name="otp4" autocomplete="off" maxlength="1">
                                    </div>
                                </div> -->
                            <div class="form-group">
                                <div id="message" style="width: 100%;position: relative;font-size: 14px;">
                                </div>
                                <label>Enter Account Id</label>
                                <div class="login-contact">
                                    <div class="input-box-parent">
                                        <input class="required" type="text" name="accountId" placeholder="Enter Account ID"
                                            maxlength="10">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Enter Last four Digits</label>
                                <div class="login-contact">
                                    <div class="input-box-parent">
                                        <input class="required" type="text" name="last4Digits"
                                            placeholder="Enter Last four Digits" maxlength="4">
                                    </div>
                                </div>
                            </div>
                            <div class="login-btn">
                                <button type="submit" class="btn btn-primaryx">Deliver</button>
                                <a href="{{ route('driver.logout') }}" class="btn btn-defaultx">Logout</a>
                            </div>
                        </div>
                        {{ Form::close()}}
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- OTP Success Modal -->
    <div class="modal fade" id="otpSuccessModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">Card Delivered</h5>
                </div>
                <div class="modal-body">
                    <!-- OTP sent successfully to your registered mobile number. Verification in progress... -->
                    You have successfully delivered the card
                </div>
                <div class="modal-footer">
                    <!-- <span id="otpStatusMessage" class="text-success"></span> -->
                    <button type="button" class="btn btn-info" data-bs-dismiss="modal">OK</button>

                </div>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function () {
            $("#loginform").validate({
                rules: {
                    "accountId": {
                        required: true,
                        digits: true,
                        maxlength: 10
                    },
                    "last4Digits": {
                        required: true,
                        digits: true,
                        maxlength: 4
                    }
                },
                messages: {
                    "accountId": {
                        required: "Enter Account ID",
                        digits: "Please enter only digits",
                        maxlength: "Account ID cannot be more than 10 digits"
                    },
                    "last4Digits": {
                        required: "Enter Last 4 Digits",
                        digits: "Please enter only digits",
                        maxlength: "Only last 4 digits are allowed"
                    }
                },
                submitHandler: function (form) {
                    // Prevent default submit and send AJAX

                    $.ajax({
                        url: "{{ url('/driver-dashboard') }}",
                        type: "POST",
                        data: $("#loginform").serialize(),
                        success: function (response) {
                            if (response.status === 'success') {
                                const modalElement = document.getElementById('otpSuccessModal');
                                const modalInstance = new bootstrap.Modal(modalElement, {
                                    //backdrop: 'static',
                                    //keyboard: false
                                });

                                modalInstance.show();
                                document.getElementById('message').textContent = '';
                                // document.getElementById('otpStatusMessage').textContent = 'OTP sent successfully';

                                    //Start polling every 5 seconds to check verification status
                                    /* const pollingInterval = setInterval(function () {
                                        $.ajax({
                                            url: "{{ url('/verify-card-status') }}",
                                type: "POST",
                                    data: {
                                    accountId: $('input[name=accountId]').val(),
                                        _token: "{{ csrf_token() }}"
                                },
                                success: function (verifyResponse) {
                                    if (verifyResponse.status === 'verified') {
                                        clearInterval(pollingInterval); //  Stop polling
                                        document.getElementById('otpStatusMessage').textContent = 'OTP Verified Successfully ✅';

                                        setTimeout(function () {
                                            modalInstance.hide();
                                            $('#message').html(`<div class="alert alert-success alert-dismissible fade show" role="alert">
                                                                    OTP verified successful!
                                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                                </div>`);
                                            setTimeout(function () {
                                                window.location.reload();
                                            }, 3000);
                                        }, 2000);
                                    } else if (verifyResponse.status === 'failed') {
                                        clearInterval(pollingInterval);
                                        document.getElementById('otpStatusMessage').textContent = 'OTP Verification Failed ❌';
                                    }
                                    // Else keep polling if not yet verified
                                },
                                error: function () {
                                    clearInterval(pollingInterval);
                                    document.getElementById('otpStatusMessage').textContent = 'Error verifying OTP ❌';
                                }
                            });
                }, 30000);  */
        } else {
            $('#message').html(`<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        ${response.message}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>`);
        }
                            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                const errorMsg = response && response.message ? response.message : 'Something went wrong!';
                $('#message').html(`<div class="alert alert-danger">${errorMsg}</div>`);
            }
                        });

                                                                                        /* $.ajax({
                                                                                            url: "{{ url('/driver-dashboard') }}",
        type: "POST",
            data: $(form).serialize(),
                success: function (response) {

                    if (response.status === 'success') {
                        const modalElement = document.getElementById('otpSuccessModal');
                        const modalInstance = new bootstrap.Modal(modalElement, {
                            backdrop: 'static',
                            keyboard: false
                        });

                        modalInstance.show();
                        document.getElementById('otpStatusMessage').textContent = 'Verifying OTP...';

                        $.ajax({
                            url: "{{ url('/verify-card-status') }}",
                            type: "POST",
                            data: {
                                accountId: $('input[name=accountId]').val(), // Send any required data
                                _token: "{{ csrf_token() }}"
                            },
                            success: function (verifyResponse) {
                                if (verifyResponse.status === 'verified') {
                                    document.getElementById('otpStatusMessage').textContent = 'OTP Verified Successfully ✅';

                                    setTimeout(function () {
                                        modalInstance.hide();

                                        // Show success message outside
                                        $('#message').html(`<div class="alert alert-success">OTP verified and login successful!</div>`);
                                    }, 2000);
                                } else {
                                    document.getElementById('otpStatusMessage').textContent = 'OTP Verification Failed ❌';

                                    // Optional: Allow user to retry or close modal after failure
                                }
                            },
                            error: function () {
                                document.getElementById('otpStatusMessage').textContent = 'Error verifying OTP ❌';
                            }
                        });

                    } else {
                        $('#message').html(`<div class="alert alert-danger">${response.message}</div>`);
                    }
                },
        error: function (xhr) {
            const response = xhr.responseJSON;
            const errorMsg = response && response.message ? response.message : 'Something went wrong!';
            $('#message').html(`<div class="alert alert-danger">${errorMsg}</div>`);
        }
                    }); */
            }
                                                                                });
                                                                            });


        $('#otpSuccessModal').on('hidden.bs.modal', function () {
            $('#loginform')[0].reset(); // ← This will clear form values
        });
    </script>

@endsection