@extends('layouts.home')
@section('content')
<style>
    .swap_fields {
        display: none
    }

    .swap_bda {
        display: none;
    }

    .swap_onafriq {
        display: none;
    }

    .iti--allow-dropdown .iti__flag-container,
    .iti--separate-dial-code .iti__flag-container {
        height: 60px;
    }
</style>

<section class="banner-section password-section">
    <div class="container">
        <div class="heading-parent same-heading-wrapper">
            <h2>{{__('message.Single Transaction')}}</h2>
        </div>
        {{ Form::open(array('method' => 'post', 'id' => 'createUserForm', 'class' => 'form form-signin')) }}

        <div class="select" style="display: flex; justify-content: flex-end;">
            <!-- Button to open the modal -->
            <button type="button" id="SelectButton" class="btn btn-primaryx" data-bs-toggle="modal"
                data-bs-target="#transList">
                {{__('message.Select Beneficiary')}}
            </button>
        </div>

        <div class="user-password-wrapper">
            <div class="col-lg-12">
                <div class="login-from-parent">
                    <div class="from-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio1"
                                value="swap_to_swap">
                            <label class="form-check-label" for="inlineRadio1">{{__('message.Swap To Swap')}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio2"
                                value="swap_to_gimac" checked>
                            <label class="form-check-label" for="inlineRadio2">{{__('message.Swap To CEMAC Wallet')}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio3"
                                value="swap_to_bda">
                            <label class="form-check-label" for="inlineRadio3">{{__('message.Swap to UEMOA Bank')}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio4"
                                value="swap_to_onafriq">
                            <label class="form-check-label" for="inlineRadio4">{{__('message.Swap to UEMOA Wallet')}}</label>
                        </div>
                    </div>

                    <div class="input-box-parent from-group swap_bda">
                        <label>{{__('message.Beneficiary:')}}</label>
                        <input type="text" class="form-control required" name="newBeneficiary"
                            placeholder="{{__('message.Enter Beneficiary')}}" autocomplete="off" maxlength="50" required>
                    </div>

                    <!-- <input type="hidden" name="country_id" id="country_id"> -->
                    <div class="input-box-parent from-group swap_bda">
                        <label>{{__('message.Iban:')}}</label>
                        <input type="text" class="form-control required" name="iban"
                            placeholder="{{__('message.Enter Iban')}}" autocomplete="off"  minlength="24" maxlength="30" required>
                    </div>

                    <!-- <div class="input-box-parent from-group swap_bda">
                        <label>Partner Reference:</label>
                        <input type="text" class="form-control required" name="partnerreference" placeholder="Enter Partner Reference" maxlength="15" autocomplete="off" required>
                    </div> -->

                    <div class="input-box-parent from-group swap_bda">
                        <label>{{__('message.Reason:')}}</label>
                        <input type="textarea" class="form-control required" name="reason"
                            placeholder="{{__('message.Enter Reason')}}" autocomplete="off" maxlength="160" required>
                    </div>

                    <div class="input-box-parent from-group swap_bda">
                        <label>{{__('message.Amount:')}}</label>
                        <input type="text" class="form-control required" name="rupees"
                            placeholder="{{__('message.Enter amount')}}" autocomplete="off"
                            onkeypress="return validateFloatKeyPress(this,event);" minlength="1" maxlength="8" required>
                    </div>

                    <div class="input-box-parent from-group swap_fields" style="display: none;">
                        <label>{{__('message.First Name:')}}</label>
                        <input type="text" class="form-control required" name="first_name"
                            placeholder="{{__('message.Enter First Name')}}" autocomplete="off" required>
                    </div>

                    <div class="input-box-parent from-group swap_fields" style="display: none;">
                        <label>{{__('message.Name:')}}</label>
                        <input type="text" class="form-control required" name="name"
                            placeholder="{{__('message.Enter Name')}}" autocomplete="off" required>
                    </div>

                    <div class="input-box-parent from-group lastRemove">
                        <label>{{__('message.Comment:')}}</label>
                        <input type="textarea" class="form-control" name="comment"
                            placeholder="{{__('message.Enter Comment')}}" autocomplete="off" maxlength="160">
                    </div>

                    <div class="input-box-parent from-group swap_fields">
                        <label>{{__('message.Country')}}:</label>
                        <div class="custom-option-field">
                            <div class="country">
                                <div id="country" class="select form-control country ">{{__('message.Select Country')}}</div>
                                <div id="country-drop" class="dropdown">
                                    <ul>
                                        <?php foreach ($country_list as $country): ?>
                                        <li class="country_list" data-code="<?php    echo $country['code']; ?>"
                                            data-name="<?php    echo $country['name']; ?>"
                                            data-cid="<?php    echo $country['id']; ?>">
                                            <span class="country-flag-icon"><img
                                                    src="<?php    echo PUBLIC_PATH . '/assets/front/images/' . $country['flag']; ?>"
                                                    alt="flag-icon"></span> <?php    echo $country['name']; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="country_id" id="country_id">

                    <!-- <div class="input-box-parent from-group swap_fields">
                            <label>Country</label>
                            <div class="custom-option-field">
                                <select class="form-control required countryList" name="country_id">
                                     <option value="">Select Country</option>
                                    <?php foreach ($country_list as $value) { ?>
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                    <?php } ?>
                                </select>
                                <span><img src="{{PUBLIC_PATH}}/assets/front/images/select-arrow.png" alt="image"></span>
                            </div>
                        </div> -->

                    <div class="input-box-parent from-group swap_fields">
                        <label>{{__('message.Wallet Manager')}}:</label>
                        <div class="custom-option-field" id="updateWalletManager">
                            <select class="form-control required" name="wallet_manager_id">
                                <option value="">{{__('message.Select Wallet Manager')}}</option>
                                <?php foreach ($wallet_manager_list as $value) { ?>
                                <option value="{{$value->id}}">{{$value->name}}</option>
                                <?php } ?>
                            </select>
                            <span><img src="{{PUBLIC_PATH}}/assets/front/images/select-arrow.png" alt="image"></span>
                        </div>
                    </div>


                    <div class="input-box-parent from-group swapto">
                        <label for="phone">{{__('message.Tel number:')}}</label>
                        <input id="phone" class="form-control required" placeholder="{{__('message.Enter Tel number')}}"
                            autocomplete="off" pattern="[0-9]*" minlength="9" maxlength="9" name="phone" required=""
                            type="tel" style="padding-left: 99px;">
                        {{ Form::hidden('countryCode', null, ['id' => 'countryCode']) }}
                    </div>

                    <div class="input-box-parent from-group swap_fields">
                        <label for="phone">{{__('message.Tel number:')}}</label>
                        <div class="login-contact form-control">
                            <div class="country-box country" id="countrybox">
                                <img id="selectedCountryFlag"
                                    src="{{HTTP_PATH}}/public/assets/front/images/country-flag.png" alt="country flag">
                                <span id="selectedCountryCode"></span>
                            </div>
                            <div class="input-box-parent">
                                <input type="tel" class="required" id="phoneInput" placeholder="{{__('message.Enter Tel number')}}" pattern="[0-9]*" minlength="9"
                                    maxlength="9" disabled required>
                                <!-- Add hidden input field for country code -->
                                <input type="hidden" name="country_code" id="countryCodeInput">
                            </div>
                        </div>
                    </div>

                    <div class="input-box-parent from-group lastRemove">
                        <label>{{__('message.Amount')}}({{CURR}}):</label>
                        <input type="text" class="form-control required" name="amount"
                            placeholder="{{__('message.Enter amount')}}" autocomplete="off"
                            onkeypress="return validateFloatKeyPress(this,event);" minlength="1" maxlength="6" required>
                    </div>

                    <!-- Onafriq -->
                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Recipient Country:')}}</label>
                        <select class="form-control required" name="recipientCountry" required>
                            <option value="">{{__('message.Select Recipient Country')}}</option>
                            <option value="BJ">{{__("message.Benin")}}</option>
                            <option value="BF">{{__("message.Burkina Faso")}}</option>
                            <option value="GW">{{__("message.Guinea Bissau")}}</option>
                            <option value="NE">{{__("message.Niger")}}</option>
                            <option value="SN">{{__("message.Senegal")}}</option>
                            <option value="ML">{{__("message.Mali")}}</option>
                            <option value="TG">{{__("message.Togo")}}</option>
                            <option value="CI">{{__("message.Ivoiry Coast")}}</option>
                        </select>
                    </div>
                    <div class="input-box-parent from-group swap_onafriq">
                        <label for="walletManagerSelect">{{__('message.Recipient Wallet Manager:')}}</label>
                        <select id="walletManagerSelect" class="form-control required" name="walletManager" required>
                            <option value="">{{__('message.Select Recipient Wallet Manager')}}</option>
                        </select>
                    </div>

                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Recipient Phone Number:')}}</label>
                        <div class="login-contact form-control">
                            <div class="country-box" id="countryCodeOna">
                                <img src="{{HTTP_PATH}}/public/assets/front/images/country-flag.png" alt="country flag">
                                <span id="selectedCountryCodeOnaR"></span>
                                <input type="hidden" name="onafriqCountryCode" id="onafriqCountryCode">
                            </div>
                            <div class="input-box-parent">
                                <input type="text" class="form-control required msisdn-input" name="recipientMsisdn"
                                    placeholder="{{__('message.Enter Recipient Phone Number')}}" autocomplete="off"
                                    pattern="[0-9]*" minlength="" maxlength="15"
                                    onkeypress="return validateFloatKeyPress(this,event);" required>
                            </div>
                        </div>
                    </div>

                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Recipient First Name:')}}</label>
                        <input type="text" class="form-control required" name="recipientName"
                            placeholder="{{__('message.Enter Recipient First Name')}}" autocomplete="off" required>
                    </div>
                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Recipient Surname:')}}</label>
                        <input type="text" class="form-control required" name="recipientSurname"
                            placeholder="{{__('message.Enter Recipient Surname')}}" autocomplete="off" required>
                    </div>

                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Amount')}}</label>
                        <input type="text" class="form-control required" name="africamount"
                            placeholder="{{__('message.Enter amount')}}" autocomplete="off"
                            onkeypress="return validateFloatKeyPress(this,event);" minlength="1" maxlength="7" required>
                    </div>

                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Sender From Country:')}}</label>
                        <select class="form-control required" name="senderCountry" id="senderCountry" required>
                            <option value="">{{__('message.Select Country')}}</option>
                            <option value="CM">{{__('message.Cameroon')}}</option>
                            <option value="CI">{{__('message.Ivoiry Coast')}}</option>
                            <option value="GA">{{__('message.Gabon')}}</option>
                            <option value="FR">{{__('message.France')}}</option>
                            <option value="BJ">{{__('message.Benin')}}</option>
                            <option value="BF">{{__('message.Burkina Faso')}}</option>
                            <option value="GW">{{__('message.Guinea Bissau')}}</option>
                            <option value="NE">{{__('message.Niger')}}</option>
                            <option value="SN">{{__('message.Senegal')}}</option>
                            <option value="ML">{{__('message.Mali')}}</option>
                            <option value="TG">{{__('message.Togo')}}</option>
                        </select>
                    </div>


                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Sender Phone Number:')}}</label>
                        <div class="login-contact form-control">
                            <div class="country-box" id="senderCountryFlag" style="max-width: 100px !important;">
                                <img src="{{HTTP_PATH}}/public/assets/front/images/country-flag.png" alt="country flag">
                                <span id="senderCountryCode"></span>
                                <input type="hidden" name="senderCountryCode" id="senderCountoryCodeInput">
                            </div>
                            <div class="input-box-parent">
                                <input type="text" class="form-control required msisdn-input" name="senderMsisdn"
                                    placeholder="{{__('message.Enter Sender Phone Number')}}" autocomplete="off"
                                    pattern="[0-9]*" minlength="" maxlength="15"
                                    onkeypress="return validateFloatKeyPress(this,event);" required>
                            </div>
                        </div>
                    </div>

                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Sender First Name:')}}</label>
                        <input type="text" class="form-control required" name="senderName"
                            placeholder="{{__('message.Enter Sender First Name')}}" autocomplete="off" required>
                    </div>
                    <div class="input-box-parent from-group swap_onafriq">
                        <label>{{__('message.Sender Surname:')}}</label>
                        <input type="text" class="form-control required" name="senderSurname"
                            placeholder="{{__('message.Enter Sender Surname')}}" autocomplete="off" required>
                    </div>

                    <div class="input-box-parent from-group senderAddress" style="display:none;">
                        <label>{{__('message.Sender Address:')}}</label>
                        <input type="text" class="form-control required" name="senderAddress"
                            placeholder="{{__('message.Enter Sender Address')}}" autocomplete="off" required>
                    </div>

                    <div class="input-box-parent from-group senderDob" style="display:none;">
                        <label>{{__('message.Sender DOB (YYYY-MM-DD):')}}</label>
                        <input type="text" class="form-control required" id="dateInput" name="senderDob"
                            placeholder="{{__('message.Enter Sender DOB (YYYY-MM-DD)')}}" pattern="\d{4}-\d{2}-\d{2}" autocomplete="off" required>
                        <p id="dateInputError" style="color:red"></p>
                    </div>

                    <div class="input-box-parent from-group senderIdType" style="display:none;">
                        <label>{{__('message.Sender ID Type:')}}</label>
                        <select name="senderIdType" class="form-control required" required>
                            <option value="">{{__('message.Select Sender ID Type')}}</option>
                            <option value="PASSPORT">{{__('message.Passport')}}</option>
                            <option value="RESIDENCE">{{__('message.Residence Permit')}}</option>
                            <option value="IDCARD">{{__('message.ID Card')}}</option>
                            <option value="OTHER">{{__('message.Other')}}</option>
                        </select>
                    </div>

                    <div class="input-box-parent from-group senderIdNumber" style="display:none;">
                        <label>{{__('message.Sender ID Number:')}}</label>
                        <input type="text" class="form-control required" name="senderIdNumber"
                            placeholder="{{__('message.Enter Sender ID Number')}}" autocomplete="off" required>
                    </div>
                    <!-- Onafriq -->
                    <div class="login-btn">
                        <button type="button" id="clearButton" class="btn btn-secondary">{{__('message.Clear')}}</button>
                        <button type="submit" id="submitBtn" class="btn btn-primaryx">{{__('message.INITIATE')}}</button>
                    </div>
                </div>
            </div>
        </div>
        {{ Form::close()}}
    </div>
</section>
<div id="loader-wrapper">
    <div id="loader-content">
        <div id="loader"></div>
        <div id="loader-text">{{__('message.Transaction In Progress')}}</div>
    </div>
</div>
<script>
    var walletData = @json(getWalletData());
    let debounceTimer;
    
    $('.msisdn-input').on('input', function () {
        clearTimeout(debounceTimer); // Clear the previous timer
        var inputField = $(this); // Store the reference to the input field
        var fieldName = inputField.attr('name'); // Get the input field name

        debounceTimer = setTimeout(function () {
            var phoneNum = inputField.val();
            var selectedCountryField = (fieldName === 'senderMsisdn') ? 'senderCountry' : 'recipientCountry';
            var selectedCountry = $('select[name="' + selectedCountryField + '"]').val();

            if (!selectedCountry) {
                alert('Please select the corresponding country');
                return false;
            }

            var code = walletData[selectedCountry][0].code;
            var codeWithPhone = code + phoneNum;

            // Determine if the input is for sender or receiver
            var actionType = (fieldName === 'senderMsisdn') ? 'sender' : 'receiver';

            $.ajax({
                url: '{{HTTP_PATH}}/getOldRecord',
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    msisdn: codeWithPhone,
                    actionType: actionType
                },
                success: function (response) {
                    if (response.data != null) {
                        if (actionType === 'sender') {
                            $('input[name="senderName"]').val(response.data.senderName);
                            $('input[name="senderSurname"]').val(response.data.senderSurname);
                        } else if (actionType === 'receiver') {
                            $('input[name="recipientName"]').val(response.data.recipientName);
                            $('input[name="recipientSurname"]').val(response.data.recipientSurname);
                        }
                    } else {
                        if (actionType === 'sender') {
                            $('input[name="senderName"]').val('');
                            $('input[name="senderSurname"]').val('');
                        } else if (actionType === 'receiver') {
                            $('input[name="recipientName"]').val('');
                            $('input[name="recipientSurname"]').val('');
                        }
                    }
                },
            });
        }, 500);
    });

</script>
<script>
    $('select[name="recipientCountry"]').on('change', function () {
        var selectedCountry = document.getElementsByName("recipientCountry")[0].value;
        var walletSelect = document.getElementById('walletManagerSelect');
        var selectedCountryCodeSpan1 = document.getElementById('selectedCountryCodeOnaR');
        var countryFlagImg = document.querySelector('#countryCodeOna img');
        // Clear previous options
        walletSelect.innerHTML = '<option value="">--Select Wallet Manager--</option>';

        if (walletData[selectedCountry]) {
            walletData[selectedCountry].forEach(function (wallet) {
                var option = document.createElement('option');
                option.value = wallet.manager;
                option.text = wallet.manager;
                walletSelect.appendChild(option);

                if (selectedCountry) {
                    // Update country code and flag
                    selectedCountryCodeSpan1.textContent = wallet.code;
                    countryFlagImg.src = wallet.flag;
                    document.getElementById('onafriqCountryCode').value = wallet.code;
                }
            });
        } else {
            selectedCountryCodeSpan1.textContent = '';
            countryFlagImg.src = "{{HTTP_PATH}}/public/assets/front/images/country-flag.png";
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


    $('select[name="senderCountry"]').on('change', function () {
        var selectedCountry = document.getElementsByName("senderCountry")[0].value;
        var selectedCountryCodeSpan = document.getElementById('senderCountryCode');
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
    })
</script>
<script>
    $(document).ready(function () {
        setTimeout(function () {
            $('#phoneInput').prop('disabled', false);
            $('#phone').prop('disabled', true);
        }, 1000);
    });
    document.getElementById('submitBtn').addEventListener('click', function (event) {
        var form = document.getElementById('createUserForm');
        const dateInput = document.getElementById('dateInput').value;
        const datePattern = /^\d{4}-\d{2}-\d{2}$/;

        if ($(".swap_option:checked").val() == "swap_to_onafriq") {
            if (!datePattern.test(dateInput)) {
                $("#dateInputError").text("{{ __('message.Please enter the date in the format YYYY-MM-DD') }}");
                event.preventDefault(); // Prevent form submission
            } else {
                $("#dateInputError").text("")
            }
        }

        event.preventDefault();

        if (form.checkValidity()) {
            document.getElementById('loader-wrapper').style.display = 'block';
            setTimeout(function () {
                form.submit();
            }, 500);
        } else {
            form.reportValidity();
        }
    });
</script>

<link media="all" type="text/css" rel="stylesheet" href=" {{url('/public/assets/front/css/intlTelInput.css')}}">
<script src="{{url('/public/assets/front/js/intlTelInput.js')}}"></script>
<script>
    $(document).ready(function () {
        // List of allowed country codes
        var allowedCountries = ["cm", "cf", "td", "gq", "ga", "cg"]; // Country codes for allowed countries

        // Initialize intlTelInput on the input field with id "phone"
        var phoneInput = window.intlTelInput(document.querySelector("#phone"), {
            separateDialCode: true,
            initialCountry: "cm", // Set Cameroon as the default selected country
            onlyCountries: allowedCountries, // Restrict the dropdown to the allowed countries
            utilsScript: "{{ url('/public/assets/front/js/utils.js') }}" // Correct path to your utils.js
        });

        // Update the hidden input field with the selected country code
        $('#phone').on('countrychange', function (e, countryData) {
            $('#countryCode').val('+' + countryData.dialCode);
        });

        // Trigger the countrychange event on page load to set initial country code
        $('#countryCode').val('+' + phoneInput.getSelectedCountryData().dialCode);
    });
</script>
<script type="text/javascript">
    $(document).ready(function () {

        $("#clearButton").click(function () {
            $("#createUserForm")[0].reset();
        });


        $('.swap_option').change(function () {
            var selectedValue = $(this).val();

            $('.swapto, .swap_fields, .swap_bda').hide();
            $('#phone, #phoneInput').removeAttr('name');

            if (selectedValue == "swap_to_swap") {

                $('input[name="first_name"]').attr('disabled', 'disabled');
                $('input[name="name"]').attr('disabled', 'disabled');

                $('.swapto,.lastRemove').show();
                $('.swap_fields, .swap_bda').hide();

                $(".senderAddress").hide();
                $(".senderIdType").hide();
                $(".senderIdNumber").hide();
                $(".senderDob").hide();


                $('#phone').attr('name', 'phone');
                $('#phone').removeAttr('disabled');

                $('#phoneInput').removeAttr('name');
                $('#phoneInput').attr('disabled', 'disabled');

                $('input[name="newBeneficiary"]').attr('disabled', 'disabled');
                $('input[name="iban"]').attr('disabled', 'disabled');
                $('input[name="reason"]').attr('disabled', 'disabled');
                $('input[name="rupees"]').attr('disabled', 'disabled');


                $('input[name="africamount"]').attr('disabled', 'disabled');
                $('input[name="recipientMsisdn"]').attr('disabled', 'disabled');
                $('select[name="recipientCountry"]').attr('disabled', 'disabled');
                $('select[name="walletManager"]').attr('disabled', 'disabled');
                $('input[name="recipientSurname"]').attr('disabled', 'disabled');
                $('input[name="recipientName"]').attr('disabled', 'disabled');
                $('select[name="senderCountry"]').attr('disabled', 'disabled');
                $('input[name="senderMsisdn"]').attr('disabled', 'disabled');
                $('input[name="senderName"]').attr('disabled', 'disabled');
                $('input[name="senderSurname"]').attr('disabled', 'disabled');
                $('input[name="senderAddress"]').attr('disabled', 'disabled');
                $('input[name="senderDob"]').attr('disabled', 'disabled');
                $('select[name="senderIdType"]').attr('disabled', 'disabled');
                $('input[name="senderIdNumber"]').attr('disabled', 'disabled');


            } else if (selectedValue == "swap_to_gimac") {

                $('input[name="first_name"]').attr('disabled', false);
                $('input[name="name"]').attr('disabled', false);
                $('input[name="phone"]').attr('disabled', false);
                $('.swap_fields,.lastRemove').show();
                $('.swapto, .swap_bda').hide();

                $(".senderAddress").hide();
                $(".senderIdType").hide();
                $(".senderIdNumber").hide();
                $(".senderDob").hide();


                $('#phoneInput').attr('name', 'phone');
                $('#phone').removeAttr('name');
                $('#phoneInput').removeAttr('disabled');
                $('#phone').attr('disabled', 'disabled');


                $('input[name="newBeneficiary"]').attr('disabled', 'disabled');
                $('input[name="iban"]').attr('disabled', 'disabled');
                $('input[name="reason"]').attr('disabled', 'disabled');
                $('input[name="rupees"]').attr('disabled', 'disabled');


                $('input[name="africamount"]').attr('disabled', 'disabled');
                $('input[name="recipientMsisdn"]').attr('disabled', 'disabled');
                $('select[name="recipientCountry"]').attr('disabled', 'disabled');
                $('select[name="walletManager"]').attr('disabled', 'disabled');
                $('input[name="recipientSurname"]').attr('disabled', 'disabled');
                $('input[name="recipientName"]').attr('disabled', 'disabled');
                $('select[name="senderCountry"]').attr('disabled', 'disabled');
                $('input[name="senderMsisdn"]').attr('disabled', 'disabled');
                $('input[name="senderName"]').attr('disabled', 'disabled');
                $('input[name="senderSurname"]').attr('disabled', 'disabled');
                $('input[name="senderAddress"]').attr('disabled', 'disabled');
                $('input[name="senderDob"]').attr('disabled', 'disabled');
                $('select[name="senderIdType"]').attr('disabled', 'disabled');
                $('input[name="senderIdNumber"]').attr('disabled', 'disabled');


            } else if (selectedValue == "swap_to_onafriq") {
                $('input[name="first_name"]').attr('disabled', 'disabled');
                $('input[name="name"]').attr('disabled', 'disabled');

                $('.swap_fields,.lastRemove').hide();


                $('.swapto, .swap_bda').hide();
                $('#phoneInput').attr('disabled', 'disabled');
                $('#phone').attr('disabled', 'disabled');
                $('input[name="amount"]').attr('disabled', 'disabled');

                $('input[name="newBeneficiary"]').attr('disabled', 'disabled');
                $('input[name="iban"]').attr('disabled', 'disabled');
                $('input[name="reason"]').attr('disabled', 'disabled');
                $('input[name="rupees"]').attr('disabled', 'disabled'); 0


                $('input[name="africamount"]').removeAttr('disabled');
                $('input[name="recipientMsisdn"]').removeAttr('disabled');
                $('select[name="recipientCountry"]').removeAttr('disabled');
                $('input[name="recipientSurname"]').removeAttr('disabled');
                $('input[name="recipientName"]').removeAttr('disabled');
                $('select[name="senderCountry"]').removeAttr('disabled');
                $('select[name="walletManager"]').removeAttr('disabled');
                $('input[name="senderMsisdn"]').removeAttr('disabled');
                $('input[name="senderName"]').removeAttr('disabled');
                $('input[name="senderSurname"]').removeAttr('disabled');

                $(".senderAddress").show();
                $(".senderIdType").show();
                $(".senderIdNumber").show();
                $(".senderDob").show();

            } else {

                $('#phoneInput').attr('disabled', 'disabled');
                $('#phone').attr('disabled', 'disabled');
                $('input[name="newBeneficiary"]').removeAttr('disabled');
                $('input[name="iban"]').removeAttr('disabled');
                $('input[name="reason"]').removeAttr('disabled');
                $('input[name="rupees"]').removeAttr('disabled');

                $('input[name="africamount"]').attr('disabled', 'disabled');
                $('input[name="recipientMsisdn"]').attr('disabled', 'disabled');
                $('select[name="recipientCountry"]').attr('disabled', 'disabled');
                $('select[name="walletManager"]').attr('disabled', 'disabled');
                $('input[name="recipientSurname"]').attr('disabled', 'disabled');
                $('input[name="recipientName"]').attr('disabled', 'disabled');
                $('select[name="senderCountry"]').attr('disabled', 'disabled');
                $('input[name="senderMsisdn"]').attr('disabled', 'disabled');
                $('input[name="senderName"]').attr('disabled', 'disabled');
                $('input[name="senderSurname"]').attr('disabled', 'disabled');
                $('input[name="senderAddress"]').attr('disabled', 'disabled');
                $('input[name="senderDob"]').attr('disabled', 'disabled');
                $('select[name="senderIdType"]').attr('disabled', 'disabled');
                $('input[name="senderIdNumber"]').attr('disabled', 'disabled');

                $('.swap_bda').show();
                $('.swapto, .swap_fields,.lastRemove').hide();
                $('input[name="amount"]').attr('disabled', 'disabled');

                $(".senderAddress").hide();
                $(".senderIdType").hide();
                $(".senderIdNumber").hide();
                $(".senderDob").hide();

            }
        });


        $('.country_list').click(function () {
            var selectedValue = $(this).data('cid');
            $('#country_id').val(selectedValue);
            $.ajax({
                url: '{{HTTP_PATH}}/getWalletmanagerList',  // Replace with your Laravel route or controller endpoint
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",  // Include CSRF token
                    country_id: selectedValue  // Include the selected country ID
                },
                success: function (response) {
                    $('#updateWalletManager').html(response);
                },
            })
        });

        $.validator.addMethod("decimal", function (value, element) {
            // Allow both integers and decimals
            return /^\d+(\.\d{1,2})?$/.test(value);
        }, "{{__('message.Please enter a valid numeric value for the amount.')}}");

        $("#createUserForm").validate({
            rules: {
                "first_name": "required",
                "name": "required",
                "country_id": "required",
                "wallet_manager_id": "required",
                "phone": {
                    required: true,
                    digits: true,
                    minlength: 9
                },
                "amount": {
                    required: true,
                    decimal: true  // Use custom 'decimal' rule
                }
            },
            messages: {
                "first_name": "{{__('message.Enter first name')}}",
                "name": "",
                "country_id": "Select your country",
                "wallet_manager_id": "Select wallet manager",
                "newBeneficiary": "{{__('message.This field is required.')}}",
                "iban":{
                    required: "{{__('message.This field is required.')}}",
                    minlength: "{{__('message.Please enter at least 24 characters.')}}",
                    maxlength: "{{__('message.Please enter at most 30 characters.')}}"
                },
                "required":"{{__('message.This field is required.')}}",
                "rupees": "{{__('message.This field is required.')}}",
                "recipientCountry": "{{__('message.This field is required.')}}",
                "walletManager": "{{__('message.This field is required.')}}",
                "recipientMsisdn": "{{__('message.This field is required.')}}",
                "recipientName": "{{__('message.This field is required.')}}",
                "recipientSurname": "{{__('message.This field is required.')}}",
                "africamount": "{{__('message.This field is required.')}}",
                "senderCountry": "{{__('message.This field is required.')}}",
                "senderMsisdn": "{{__('message.This field is required.')}}",
                "senderName": "{{__('message.This field is required.')}}",
                "senderSurname": "{{__('message.This field is required.')}}",
                "senderAddress": "{{__('message.This field is required.')}}",
                "senderIdType": "{{__('message.This field is required.')}}",
                "senderIdNumber": "{{__('message.This field is required.')}}",
                "phone": {
                    required: "{{__('message.Enter Tel number')}}",
                    digits: "{{__('message.Please enter only numeric values for the phone number')}}",
                    minlength: "{{__('message.Please enter at least 9 number.')}}",
                },
                "amount": {
                    required: "{{__('message.Enter amount')}}",
                    decimal: "{{__('message.Please enter a valid numeric value for the amount.')}}"
                }
            },
        });
    });

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

</script>

<script>
    document.getElementById('phoneInput').addEventListener('keypress', function (event) {
        // Allow only numeric characters (0-9)
        if (event.which < 48 || event.which > 57) {
            event.preventDefault();
        }
    });
</script>

<script type="text/javascript">
    function countryDropdown(selector) {
        var Selected = $(selector);
        var Drop = $(selector + '-drop');
        var DropItem = Drop.find('li');

        // Toggle dropdown visibility when clicking on the selected element
        Selected.click(function (e) {
            e.stopPropagation(); // Prevent event bubbling
            Selected.toggleClass('open');
            Drop.toggle();
        });

        // Handle item selection within the dropdown
        DropItem.click(function (e) {
            e.stopPropagation(); // Prevent event bubbling
            Selected.removeClass('open');
            Drop.hide();

            var item = $(this);
            Selected.html(item.html());
        });

        // Close dropdown when clicking outside of it
        $(document).click(function (e) {
            if (!$(e.target).closest(selector).length) {
                Selected.removeClass('open');
                Drop.hide();
            }
        });
    }

    countryDropdown('#country');
</script>


<script>
    // Attach click event listener to country list items
    document.querySelectorAll('.country li').forEach(function (element) {
        element.addEventListener('click', function () {
            var countryCode = this.getAttribute('data-code');
            var countryFlag = this.querySelector('img').getAttribute('src');
            var countryName = this.getAttribute('data-name');

            // Update selected country code, flag, and hidden input field
            document.getElementById('selectedCountryCode').textContent = countryCode;
            document.getElementById('selectedCountryFlag').setAttribute('src', countryFlag);
            document.getElementById('countryCodeInput').value = countryCode; // Fill hidden input field with country code
            document.getElementById('country').classList.remove('country');
            document.getElementById('countrybox').classList.remove('country');
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        // Retrieve the stored language
        var lang = sessionStorage.getItem('lang');

        // Retrieve the stored selected option
        var selectedOption = sessionStorage.getItem('selected_option');


        var selectedValue = $('input[name="option"]:checked').val();
        updateFormFields(selectedValue);

        // Set the stored selected option
        /*if (selectedOption) {
            $('input[name="option"][value="' + selectedOption + '"]').prop('checked', true);
            updateFormFields(selectedOption); // Update form fields based on selected option
        }*/


        $('.change-language').on('click', function () {
            var newLang = $(this).data('lang'); // Assuming language change is triggered by an element with class 'change-language'
            changeLanguage(newLang);
        });

        $('.swap_option').change(function () {
            var selectedValue = $(this).val();
            updateFormFields(selectedValue);
        });
    });

    function updateFormFields(selectedValue) {
        if (selectedValue === "swap_to_swap") {
            setTimeout(() => {
                $('.senderAddress').hide();
                $('.senderDob').hide();
                $('.senderIdType').hide();
                $('.senderIdNumber').hide();
            }, 1000);
            $('.swapto').show();
            $('.swap_fields').hide();
            $('.swap_bda').hide();
            $('.swap_onafriq').hide();
            $('#phone').attr('name', 'phone');
            $('#phoneInput').removeAttr('name');
            $('input[name="newBeneficiary"]').attr('disabled', 'disabled');
            $('input[name="iban"]').attr('disabled', 'disabled');
            $('input[name="reason"]').attr('disabled', 'disabled');
            $('input[name="rupees"]').attr('disabled', 'disabled');

            $('input[name="africamount"]').attr('disabled', 'disabled');
            $('input[name="recipientMsisdn"]').attr('disabled', 'disabled');
            $('select[name="recipientCountry"]').attr('disabled', 'disabled');
            $('select[name="walletManager"]').attr('disabled', 'disabled');
            $('input[name="recipientSurname"]').attr('disabled', 'disabled');
            $('input[name="recipientName"]').attr('disabled', 'disabled');
            $('select[name="senderCountry"]').attr('disabled', 'disabled');
            $('input[name="senderMsisdn"]').attr('disabled', 'disabled');
            $('input[name="senderName"]').attr('disabled', 'disabled');
            $('input[name="senderSurname"]').attr('disabled', 'disabled');
            $('input[name="senderAddress"]').attr('disabled', 'disabled');
            $('input[name="senderDob"]').attr('disabled', 'disabled');
            $('select[name="senderIdType"]').attr('disabled', 'disabled');
            $('input[name="senderIdNumber"]').attr('disabled', 'disabled');


            $('input[name="first_name"]').attr('disabled', 'disabled');
            $('input[name="name"]').attr('disabled', 'disabled');


        } else if (selectedValue === "swap_to_gimac") {
            setTimeout(() => {
                $('.senderAddress').hide();
                $('.senderDob').hide();
                $('.senderIdType').hide();
                $('.senderIdNumber').hide();
            }, 1000);
            $('.swapto').hide();
            $('.swap_bda').hide();
            $('.swap_fields').show();
            $('.swap_onafriq').hide();
            $('#phoneInput').attr('name', 'phone');
            $('#phone').removeAttr('name');
            $('input[name="newBeneficiary"]').attr('disabled', 'disabled');
            $('input[name="iban"]').attr('disabled', 'disabled');
            $('input[name="reason"]').attr('disabled', 'disabled');
            $('input[name="rupees"]').attr('disabled', 'disabled');

            $('input[name="africamount"]').attr('disabled', 'disabled');
            $('input[name="recipientMsisdn"]').attr('disabled', 'disabled');
            $('select[name="recipientCountry"]').attr('disabled', 'disabled');
            $('select[name="walletManager"]').attr('disabled', 'disabled');
            $('input[name="recipientSurname"]').attr('disabled', 'disabled');
            $('input[name="recipientName"]').attr('disabled', 'disabled');
            $('select[name="senderCountry"]').attr('disabled', 'disabled');
            $('input[name="senderMsisdn"]').attr('disabled', 'disabled');
            $('input[name="senderName"]').attr('disabled', 'disabled');
            $('input[name="senderSurname"]').attr('disabled', 'disabled');
            $('input[name="senderAddress"]').attr('disabled', 'disabled');
            $('input[name="senderDob"]').attr('disabled', 'disabled');
            $('select[name="senderIdType"]').attr('disabled', 'disabled');
            $('input[name="senderIdNumber"]').attr('disabled', 'disabled');


        } else if (selectedValue === "swap_to_bda") {
            setTimeout(() => {
                $('.senderAddress').hide();
                $('.senderDob').hide();
                $('.senderIdType').hide();
                $('.senderIdNumber').hide();
            }, 1000);
            $('input[name="first_name"]').attr('disabled', 'disabled');
            $('input[name="name"]').attr('disabled', 'disabled');

            $('.swap_bda').show();
            $('.swapto').hide();
            $('.swap_fields').hide();
            $('.swap_onafriq').hide();

        } else if (selectedValue === "swap_to_onafriq") {
            $('input[name="first_name"]').attr('disabled', 'disabled');
            $('input[name="name"]').attr('disabled', 'disabled');

            $('input[name="newBeneficiary"]').attr('disabled', 'disabled');
            $('input[name="iban"]').attr('disabled', 'disabled');
            $('input[name="reason"]').attr('disabled', 'disabled');
            $('input[name="rupees"]').attr('disabled', 'disabled');
            $('.swap_onafriq').show();
            $('.swap_bda').hide();
            $('.swapto').hide();
            $('.swap_fields').hide();
        } else {

        }
    }


</script>

<div class="modal fade upload-modal beneficiaryModel" id="transList" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered excel_trnas_list_model">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-header">
                    {{__('message.Beneficiary List')}}
                </div>
            </div>

            <div class="modal-body">
                <table id="exampleTableExpend" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{__('message.Select')}}</th>
                            <th>{{__('message.First Name')}}</th>
                            <th>{{__('message.Name')}}</th>
                            <th>{{__('message.Country')}}</th>
                            <th>{{__('message.Country Code')}}</th>
                            <th>{{__('message.Telephone')}}</th>
                            <th>{{__('message.Wallet Manager')}}</th>
                            <th>{{__('message.Created_at')}}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="login-btn">
                <button type="button" id="confirmSelection" class="btn btn-primaryx">{{__('message.Confirm')}}</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Handle the modal show event for beneficiary selection
        $('#transList').on('show.bs.modal', function (event) {
            var triggerButton = $(event.relatedTarget);
            var url = triggerButton.data('url');

            // Destroy previous DataTable instance if it exists
            if ($.fn.DataTable.isDataTable('#exampleTableExpend')) {
                $('#exampleTableExpend').DataTable().destroy();
            }

            // Initialize DataTable
            $('#exampleTableExpend').DataTable({
                processing: false,
                bFilter: false,
                searching: false,
                serverSide: true,
                lengthChange: false,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{HTTP_PATH}}/beneficary",
                    type: "GET",
                    data: { url: url }
                },
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            return '<input type="radio" name="beneficiary" value="' + data + '" class="selection-input">';
                        }
                    },
                    { data: 'first_name' },
                    { data: 'name' },
                    { data: 'country' },
                    { data: 'country_code' },
                    { data: 'telephone' },
                    { data: 'walletManager' },
                    { data: 'created_at' }
                ],
                language: {
                    paginate: {
                        previous: "{{ __('message.previous') }}",
                        next: "{{ __('message.next') }}"
                    },

                    info: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}",
                    infoEmpty: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}", // Custom text for empty tables
                    // lengthMenu: "{{ __('message.show') }} _MENU_ {{ __('message.entries') }}", // "Show X entries" text
                    // search: "{{ __('message.search') }}", // Label for the search box
                    zeroRecords: "{{ __('message.No data available in table') }}" // Custom text for no matching records
                },

            });
        });



        // Handle beneficiary selection confirmation
        $('#confirmSelection').on('click', function () {
            var selectedBeneficiary = $('input[name="beneficiary"]:checked').val();

            if (selectedBeneficiary) {
                $.ajax({
                    url: "{{HTTP_PATH}}/fill-form-data/" + selectedBeneficiary,
                    type: "POST",
                    data: {
                        id: selectedBeneficiary,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        if (response.success) {
                            var data = response.data;
                            $('#transList').modal('hide');

                            // Auto-select country based on data.country_code or data.country_id
                            var selectedCountry = $('#country-drop li').filter(function () {
                                return $(this).data('cid') == data.country_id || $(this).data('code') == data.country_code;
                            }).first();

                            var countryCode = data.country_code || ''; // Use country_code from data or empty string
                            var phoneNumber = data.telephone ? data.telephone : '';

                            // If no country code in data, prompt the user or set a default
                            if (!countryCode && selectedCountry.length) {
                                countryCode = selectedCountry.data('code');
                            }

                            if (phoneNumber) {
                                $('#phoneInput').val(phoneNumber);
                                $('input[name="first_name"]').val(data.first_name || '');
                                $('input[name="name"]').val(data.name || '');
                                $('#updateWalletManager select').val(data.walletManagerId || '');
                            }

                            $('#phone').val(phoneNumber);
                            $('.iti__selected-dial-code').html('+' + countryCode);

                            // Set country-related information if a country is found
                            if (selectedCountry.length) {
                                var countryName = selectedCountry.data('name');
                                var countryFlag = selectedCountry.find('img').attr('src');

                                // Update UI elements with the selected country's information
                                $('#selectedCountryCode').text(countryCode);
                                $('#selectedCountryFlag').attr('src', countryFlag).attr('alt', countryName);
                                $('#countryCodeInput').val(countryCode);
                                $('#country').html('<img src="' + countryFlag + '" alt="' + countryName + '" /> ' + countryName); // Display flag and name
                                $('#country_id').val(data.country);
                                $('#country-drop').hide();
                            }

                            // Set phone number and display dial code and flag
                            

                        } else {
                            alert(response.message || 'Error processing selection');
                        }
                    },
                    error: function () {
                        console.log('Error processing selection');
                    }
                });
            } else {
                alert("Please select a beneficiary.");
            }
        });

    });

</script>
<script>
    document.getElementById('clearButton').addEventListener('click', function () {
        location.reload(); // Reloads the current page
    });
</script>
@endsection