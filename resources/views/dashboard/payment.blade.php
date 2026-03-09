<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<section class="banner-section password-section">
    <style>
        section.banner-section.password-section {
            height: 100%;
            background: transparent;
            padding: 50px 0;
            overflow-y: auto;
        }

        body {
            margin: 0;
            padding: 0;
            background: #eee;
        }

        /* Loader Container */
        #loader {
            position: fixed;
            /* Stay fixed on the screen */
            top: 0;
            left: 0;
            width: 100%;
            /* Cover the full screen */
            height: 100%;
            /* Cover the full screen */
            background: rgba(255, 255, 255, 0.8);
            /* Semi-transparent background */
            z-index: 9999;
            /* Stay on top of everything */
            display: flex;
            /* Flexbox for centering */
            align-items: center;
            /* Vertically center */
            justify-content: center;
            /* Horizontally center */
        }

        /* Loader GIF */
        #loader img {
            width: 50px;
            /* Adjust the size of the GIF */
            height: 50px;
            animation: spin 1s linear infinite;
            /* Optional spinning animation */
        }

        /* Optional Spin Animation */
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .row.iban-inner-parent .form-group {
            margin: 0 0 10px;
            word-break: break-all;
        }

        section.banner-section.password-section .iban-wrapper {
            max-width: 800px;
            margin: 50px auto;
        }

        .row.iban-inner-parent {
            background: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgb(0 0 0 / 14%);
            border-radius: 20px;
        }

        .row.iban-inner-parent .form-group label {
            margin: 0 0 5px;
            color: #000;
            font-weight: 500;
            line-height: 1.4;
        }

        .row.iban-inner-parent+.row.iban-inner-parent {
            margin-top: 30px;
        }

        .row.iban-inner-parent .form-group .form-control {
            padding: 13px 12px;
            color: #000;
            font-weight: 400;
            line-height: 1.4;
            margin: 0;
        }

        .row.iban-inner-parent .form-group:last-child {
            margin-bottom: 0;
        }

        .row.iban-inner-parent .form-group input.form-control.btn.btn-success {
            background: #4b2e74;
            color: #fff;
            box-shadow: none !important;
            border-color: #4b2e74;
        }

        .row.iban-inner-parent .form-group input.form-control.btn.btn-success:hover {
            background: transparent;
            color: #4b2e74;
        }

        .row.iban-inner-parent .form-group .form-control:focus {
            box-shadow: none;
            border-color: #4b2e74;
        }

        .row.iban-inner-parent .form-group div#updateWalletManager {
            position: relative;
        }

        .row.iban-inner-parent .form-group div#updateWalletManager span {
            position: absolute;
            top: 50%;
            right: 13px;
            transform: translateY(-50%);
        }

        .row.iban-inner-parent .form-group div#updateWalletManager span img {
            max-width: 12px;
            user-select: none;
            pointer-events: none;
            appearance: none;
        }

        .row.iban-inner-parent .login-content-parent a img {
            max-width: 100px;
            display: inline-block;
            margin: 0 0 25px;
        }

        .form-group.border-line {
            border-top: 1px solid rgb(0 0 0 / 16%);
            margin: 30px 0 !important;
            position: relative;
        }

        .form-group.border-line span {
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 5px 20px;
        }

        .arrow-select-image {
            position: relative;
        }

        .arrow-select-image span img {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            max-height: 6px;
            user-select: none;
            pointer-events: none;
            appearance: none;
        }
    </style>
    <div class="container">
        <?php if (session()->has('success_message')) { ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <strong>{{__('Success!')}}</strong> {{Session::get('success_message')}}
        </div>
        <?php    
        Session::forget('success_message');
} ?>
        <?php if (session()->has('error_message')) { ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <strong>{{__('Error!')}}</strong> {{Session::get('error_message')}}
        </div>
        <?php    
        Session::forget('error_message');
} ?>
        <div class="iban-wrapper">
            <form action="" id="paymentForm" method="POST">
                @csrf
                <div class="row iban-inner-parent">
                    <div class="login-content-parent">
                        <a href="{{HTTP_PATH}}"><img src="{{PUBLIC_PATH}}/assets/front/images/logo.svg" alt="image"></a>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="country">{{__('Country')}}</label>
                            <div class="arrow-select-image">
                                <select name="country_id" id="country" class="country_list form-control">
                                    <option value="">{{__('Select Country')}}</option>
                                    <option value="1">{{__('Cameroon')}}</option>
                                    <option value="2">{{__('Congo')}}</option>
                                    <option value="6">{{__('Equatorial Guinea')}}</option>
                                    <option value="3">{{__('Gabon')}}</option>
                                    <option value="4">{{__('Republique Centrafricaine')}}</option>
                                    <option value="5">{{__('Tchad')}}</option>
                                    <option value="BJ">{{__('Benin')}}</option>
                                    <option value="BF">{{__('Burkina Faso')}}</option>
                                    <option value="GW">{{__('Guinea Bissau')}}</option>
                                    <option value="NE">{{__('Niger')}}</option>
                                    <option value="SN">{{__('Senegal')}}</option>
                                    <option value="ML">{{__('Mali')}}</option>
                                    <option value="TG">{{__('Togo')}}</option>
                                    <option value="CI">{{__('Ivoiry Coast')}}</option>
                                </select>
                                <span><img src="https://api.swap-africa.net/public/assets/front/images/select-arrow.png"
                                        alt="image"></span>
                            </div>
                        </div>
                        <div class="form-group WALLET_MANAGER_ONAFRIQ">
                            <label for="walletManagerSelect">{{__('Wallet Manager:')}}</label>
                            <div class="arrow-select-image">
                                <select id="walletManagerSelect" class="form-control required isWalletManager"
                                    name="walletManager" required>
                                    <option value="">{{__('Select Wallet Manager')}}</option>
                                </select>
                                <span><img src="https://api.swap-africa.net/public/assets/front/images/select-arrow.png"
                                        alt="image"></span>
                            </div>
                        </div>
                        <div class="form-group WALLET_MANAGER_GIMAC" style="display: none">
                            <label for="">{{__('Wallet Manager')}}</label>
                            <div id="updateWalletManager"> </div>
                        </div>
                        <div class="form-group border-line">
                            <span>{{__('OR')}}</span>
                        </div>
                        {{-- <div class="form-group">
                            <label for="countryss">{{__('Country')}}</label>
                            <div class="arrow-select-image">
                                <select name="country_idss" id="countryss" class="country_listss form-control">
                                    <option value="">{{__('Select Country')}}</option>
                                    <option value="1">{{__('Cameroon')}}</option>
                                    <option value="2">{{__('Congo')}}</option>
                                    <option value="6">{{__('Equatorial Guinea')}}</option>
                                    <option value="3">{{__('Gabon')}}</option>
                                    <option value="4">{{__('Republique Centrafricaine')}}</option>
                                    <option value="5">{{__('Tchad')}}</option>
                                    <option value="BJ">{{__('Benin')}}</option>
                                    <option value="BF">{{__('Burkina Faso')}}</option>
                                    <option value="GW">{{__('Guinea Bissau')}}</option>
                                    <option value="NE">{{__('Niger')}}</option>
                                    <option value="SN">{{__('Senegal')}}</option>
                                    <option value="ML">{{__('Mali')}}</option>
                                    <option value="TG">{{__('Togo')}}</option>
                                </select>
                                <span><img src="https://api.swap-africa.net/public/assets/front/images/select-arrow.png"
                                        alt="image"></span>
                            </div>
                        </div> --}}
                        <div class="form-group">
                            <label for="iban">{{__('IBAN')}}</label>
                            <input type="text" class="form-control required" id="iban" name="iban"
                                placeholder="Enter Iban" autocomplete="off" minlength="24" maxlength="30">
                            <input type="hidden" name="type" value="">
                        </div>
                    </div>
                </div>
                <div class="row gimac iban-inner-parent" style="display:none">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="firstName">{{__('First Name')}}</label>
                            <input type="text" name="firstName" id="firstName" autocomplete="off"
                                class="form-control required">
                        </div>
                        <div class="form-group">
                            <label for="name">{{__('Name')}}</label>
                            <input type="text" name="name" id="name" autocomplete="off" class="form-control required">
                        </div>
                        <div class="form-group">
                            <label for="comment">{{__('Comment')}}</label>
                            <input type="text" name="comment" id="comment" autocomplete="off"
                                class="form-control required">
                        </div>

                        <div class="form-group">
                            <label for="phone">{{__('Tel number')}}</label>
                            <input type="text" name="phone" id="phone" autocomplete="off" class="form-control required">
                        </div>
                        <div class="form-group">
                            <label for="amount">{{__('Amount')}}</label>
                            <input type="text" name="amount" id="amount" autocomplete="off"
                                class="form-control required">
                        </div>
                        <div class="form-group">
                            <label for="">&nbsp;</label>
                            <input type="submit" name="submit" value="Paynow" class="form-control btn btn-success">
                        </div>
                    </div>
                </div>

                <div class="row onafriq iban-inner-parent" style="display:none">
                    <div class="col-md-12">

                        <div class="form-group swap_onafriq">
                            <label>{{__('Recipient Phone Number:')}}</label>
                            <input type="text" class="form-control required msisdn-input" name="phoneNo"
                                autocomplete="off" pattern="[0-9]*" minlength="" maxlength="15"
                                onkeypress="return validateFloatKeyPress(this,event);" required>
                        </div>



                        <div class="form-group">
                            <label for="amountO">{{__('Amount')}}</label>
                            <input type="text" name="amountO" id="amountO" autocomplete="off"
                                class="form-control required">
                        </div>

                        <div class="form-group">
                            <label for="firstNameO">{{__('Recipient Name')}}</label>
                            <input type="text" name="firstNameO" id="firstNameO" autocomplete="off"
                                class="form-control required">
                        </div>
                        <div class="form-group">
                            <label for="lastNameO">{{__('Recipient Surname')}}</label>
                            <input type="text" name="lastNameO" id="lastNameO" autocomplete="off"
                                class="form-control required">
                        </div>

                        <div class="form-group">
                            <label>{{__('Sender From Country:')}}</label>
                            <div class="arrow-select-image">
                                <select class="form-control required" name="senderCountry" id="senderCountry" required>
                                    <option value="">{{__('Select Country')}}</option>
                                    <option value="CM">{{__('Cameroon')}}</option>
                                    <option value="GA">{{__('Gabon')}}</option>
                                    <option value="FR">{{__('France')}}</option>
                                    <option value="BJ">{{__('Benin')}}</option>
                                    <option value="BF">{{__('Burkina Faso')}}</option>
                                    <option value="GW">{{__('Guinea Bissau')}}</option>
                                    <option value="NE">{{__('Niger')}}</option>
                                    <option value="SN">{{__('Senegal')}}</option>
                                    <option value="ML">{{__('Mali')}}</option>
                                    <option value="TG">{{__('Togo')}}</option>
                                    <option value="CI">{{__('Ivoiry Coast')}}</option>
                                </select>
                                <span><img src="https://api.swap-africa.net/public/assets/front/images/select-arrow.png"
                                        alt="image"></span>
                            </div>
                        </div>

                        <div class="form-group swap_onafriq">
                            <label>{{__('message.Sender Phone Number:')}}</label>
                            <input type="text" class="form-control required msisdn-input" name="phoneNoS"
                                placeholder="{{__('message.Enter Sender Phone Number')}}" autocomplete="off"
                                pattern="[0-9]*" minlength="" maxlength="15"
                                onkeypress="return validateFloatKeyPress(this,event);" required>
                        </div>


                        <div class="form-group swap_onafriq">
                            <label>{{__('Sender First Name:')}}</label>
                            <input type="text" class="form-control required" name="senderName"
                                placeholder="{{__('Enter Sender First Name')}}" autocomplete="off" required>
                        </div>
                        <div class="form-group swap_onafriq">
                            <label>{{__('Sender Surname:')}}</label>
                            <input type="text" class="form-control required" name="senderSurname"
                                placeholder="{{__('Enter Sender Surname')}}" autocomplete="off" required>
                        </div>

                        <div class="input-box-parent form-group senderAddress" style="display:none;">
                            <label>{{__('Sender Address:')}}</label>
                            <input type="text" class="form-control required" name="senderAddress"
                                placeholder="{{__('Enter Sender Address')}}" autocomplete="off" required>
                        </div>

                        <div class="form-group senderDob" style="display:none;">
                            <label>{{__('Sender DOB (YYYY-MM-DD):')}}</label>
                            <input type="text" class="form-control required" id="dateInput" name="senderDob"
                                placeholder="{{__('Enter Sender DOB (YYYY-MM-DD)')}}" pattern="\d{4}-\d{2}-\d{2}"
                                autocomplete="off" required>
                            <p id="dateInputError" style="color:red"></p>
                        </div>

                        <div class="form-group senderIdType" style="display:none;">
                            <label>{{__('Sender ID Type:')}}</label>
                            <select name="senderIdType" class="form-control required" required>
                                <option value="">{{__('Select Sender ID Type')}}</option>
                                <option value="PASSPORT">{{__('Passport')}}</option>
                                <option value="RESIDENCE">{{__('Residence Permit')}}</option>
                                <option value="IDCARD">{{__('ID Card')}}</option>
                                <option value="OTHER">{{__('Other')}}</option>
                            </select>
                        </div>

                        <div class="form-group senderIdNumber" style="display:none;">
                            <label>{{__('Sender ID Number:')}}</label>
                            <input type="text" class="form-control required" name="senderIdNumber"
                                placeholder="{{__('Enter Sender ID Number')}}" autocomplete="off" required>
                        </div>



                        <div class="form-group">
                            <label for="">&nbsp;</label>
                            <input type="submit" name="submit" value="Paynow" class="form-control btn btn-success">
                        </div>
                    </div>
                </div>
                <div class="row bda iban-inner-parent" style="display:none">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="newBeneficiary">{{__('Beneficiary')}}</label>
                            <input type="text" name="newBeneficiary" id="newBeneficiary" class="form-control required">
                        </div>
                        <div class="form-group">
                            <label for="reason">{{__('Reason')}}</label>
                            <input type="textarea" class="form-control required" id="reason" name="reason"
                                placeholder="Enter Reason" autocomplete="off" required="">
                        </div>
                        <div class="form-group">
                            <label for="amountB">{{__('Amount')}}</label>
                            <input type="text" name="amountB" id="amountB" class="form-control required">
                        </div><br>

                        <div class="form-group">
                            <label for="">&nbsp;</label>
                            <input type="submit" name="submit" value="Paynow" class="form-control btn btn-success">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div id="loader" style="display: none;">
        <img src="https://i.gifer.com/ZZ5H.gif" alt="Loading..." />
    </div>

</section>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{{ HTML::script('public/assets/js/front/jquery.validate_en.min.js')}}
<script>
    function validateFloatKeyPress(el, evt) {
        var charCode = (evt.which) ? evt.which : event.keyCode;
        var number = el.value.split('.');
        if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        //just one dot
        if (number.length > 1 && charCode == 46) {
            return false;
        }
        //get the carat position
        var caratPos = getSelectionStart(el);
        var dotPos = el.value.indexOf(".");
        if (caratPos > dotPos && dotPos > -1 && (number[1].length > 1)) {
            return false;
        }
        return true;
    }

    function getSelectionStart(element) {
        if (element.selectionStart) {
            return element.selectionStart;
        } else if (document.selection) {
            element.focus();
            var Sel = document.selection.createRange();
            var SelLength = document.selection.createRange().text.length;
            Sel.moveStart('character', -element.value.length);
            return Sel.text.length - SelLength;
        }
        return 0;
    }
    $(document).ready(function () {

        if (sessionStorage.getItem("refreshOnBack") === 'true') {
            // Remove the flag
            sessionStorage.removeItem("refreshOnBack");
            window.location.reload();
        }

        $.validator.addMethod("regex", function (value, element, regexpr) {
            return regexpr.test(value);
        });

        $('.country_list').on('change', function () {
            var selectedValue = $(this).val();
            $.ajax({
                url: '{{HTTP_PATH}}/getWalletmanagerList',
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",  // Include CSRF token
                    country_id: selectedValue  // Include the selected country ID
                },
                success: function (response) {
                    $('#updateWalletManager').html(response);
                },
            })
            $("#iban").valid();
        });

        $('#country').on('change', function () {

            var myArray = ['BJ', 'BF', 'GW', 'CI', 'NE', 'SN', 'ML', 'TG'];
            var valueToCheck = $(this).val();
            if (myArray.includes(valueToCheck)) {
                $('.onafriq').show();
                $('.gimac').hide();
                $('.bda').hide();
                $('.WALLET_MANAGER_ONAFRIQ').show();
                $('.WALLET_MANAGER_GIMAC').hide();
                $('input[name="type"]').val('ONAFRIQ');
            } else {
                $('.gimac').show();
                $('.onafriq').hide();
                $('.bda').hide();
                $('.WALLET_MANAGER_ONAFRIQ').hide();
                $('.WALLET_MANAGER_GIMAC').show();
                $('input[name="type"]').val('GIMAC');
            }
        });

        $('.isWalletManager').on('change', function () {
            $('#iban').val('');
            $("#iban").valid();
        })

        $('#iban').on('input change click', function () {

            if ($(this).val() == "") {
                $('.onafriq').hide();
                $('.gimac').hide();
                $('.bda').hide();
            }
            if ($(this).val() != "") {
                $('.gimac').hide();
                $('.onafriq').hide();
                $('.bda').show();
                $('input[name="type"]').val('BDA');
                $('.isWalletManager').removeClass('required');
            }
            $('.isWalletManager').val('');
        });
    })

    $("#paymentForm").validate({
        rules: {
            country: {
                required: function (element) {
                    return $("#iban").val().trim() === ""; // Country is required if IBAN is blank
                },

            },
            iban: {
                required: function (element) {
                    return $("#country").val().trim() === ""; // IBAN is required if Country is blank
                },
                minlength: 24,
                maxlength: 30
            },
            phone: {
                required: true,
                regex: /^\+?[0-9]*$/,
                minlength: 9,
                maxlength: 12
            },
            amount: {
                required: true,
                regex: /^\+?[0-9]*$/,
                maxlength: 5
            },
            phoneNo: {
                required: true,
                regex: /^\+?[0-9]*$/,
            },
            amountO: {
                required: true,
                regex: /^\+?[0-9]*$/,
                maxlength: 5
            },
            amountB: {
                required: true,
                regex: /^\+?[0-9]*$/,
                maxlength: 5
            },
        },
        messages: {
            country: {
                required: "Country is required if IBAN is blank.",
            },
            iban: {
                required: "IBAN is required if Country is blank.",
                minlength: "IBAN must be exactly 24 characters long.",
                maxlength: "IBAN must be exactly 30 characters long."
            },
            phone: {
                required: "Tel number is required",
                regex: "Please enter only numeric digits.",
                minlength: "Phone number must be at least 9 digits long.",
                maxlength: "Phone number must not exceed 12 digits."
            },
            amount: {
                required: "Amount is required",
                regex: "Please enter only numeric digits.",
                maxlength: "Amount must not exceed 5 digits."
            },
            phoneNo: {
                required: "Tel number is required",
                regex: "Please enter only numeric digits.",
            },
            amountO: {
                required: "Amount is required",
                regex: "Please enter only numeric digits.",
                maxlength: "Amount must not exceed 5 digits."
            },
            amountB: {
                required: "Amount is required",
                regex: "Please enter only numeric digits.",
                maxlength: "Amount must not exceed 5 digits."
            },
        },
        errorElement: "span",
        errorPlacement: function (error, element) {
            error.css("color", "red"); // Style the error message
            error.insertAfter(element); // Place error message below the input
        },
        submitHandler: function (form) {
            $("#loader").show();
            HTMLFormElement.prototype.submit.call(form);
        }
    });

</script>

<script>
    var walletData = @json(getWalletData());
    $('select[name="country_id"]').on('change', function () {
        var selectedCountry = document.getElementsByName("country_id")[0].value;

        var walletSelect = document.getElementById('walletManagerSelect');
        var selectedCountryCodeSpan = document.getElementById('selectedCountryCodeOna');
        var countryFlagImg = document.querySelector('#countryCodeOna img');
        // Clear previous options
        walletSelect.innerHTML = '<option value="">Select Wallet Manager</option>';

        if (walletData[selectedCountry]) {
            walletData[selectedCountry].forEach(function (wallet) {
                var option = document.createElement('option');
                option.value = wallet.manager;
                option.text = wallet.manager;
                walletSelect.appendChild(option);

                if (selectedCountry) {
                    // Update country code and flag
                    // selectedCountryCodeSpan.textContent = wallet.code;
                    // countryFlagImg.src = wallet.flag;
                    // document.getElementById('onafriqCountryCode').value = wallet.code;
                }
            });
        } else {
            // selectedCountryCodeSpan.textContent = '';
            // countryFlagImg.src = "{{HTTP_PATH}}/public/assets/front/images/country-flag.png";
        }

        if (selectedCountry == "SN" || selectedCountry == "ML") {
            $(".senderAddress").show();
            $(".senderIdType").show();
            $(".senderIdNumber").show();
            $('input[name="senderAddress"]').removeAttr('disabled');
            $('select[name="senderIdType"]').removeAttr('disabled');
            $('input[name="senderIdNumber"]').removeAttr('disabled');
        } else {
            $(".senderAddress").hide();
            $(".senderIdType").hide();
            $(".senderIdNumber").hide();
            $('input[name="senderAddress"]').attr('disabled', 'disabled');
            $('select[name="senderIdType"]').attr('disabled', 'disabled');
            $('input[name="senderIdNumber"]').attr('disabled', 'disabled');
        }
        if (selectedCountry == "SN" || selectedCountry == "ML" || selectedCountry == "BF") {
            $(".senderDob").show();
            $('input[name="senderDob"]').removeAttr('disabled');
        } else {
            $(".senderDob").hide();
            $('input[name="senderDob"]').attr('disabled', 'disabled');
        }
    })

    /* $('select[name="senderCountry"]').on('change', function () {
        var selectedCountry = document.getElementsByName("senderCountry")[0].value;
        var selectedCountryCodeSpan = document.getElementById('selectedCountryCodeOna');
        var wallet = walletData[selectedCountry][0];

        if (selectedCountry) {
            selectedCountryCodeSpan.textContent = wallet.code;
            document.getElementById('senderCountryCode').textContent = wallet.code;
            document.querySelector('#senderCountryFlag img').src = wallet.flag;
            document.getElementById('senderCountoryCodeInput').value = wallet.code;
        } else {
            document.getElementById('senderCountryCode').textContent = '';
            document.querySelector('#senderCountryFlag img').src = '';
            document.getElementById('senderCountoryCodeInput').value = '';
        }
    }) */
</script>