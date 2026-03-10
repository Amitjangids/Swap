@extends('layouts.home')
@section('content')
<style>
    .swap_fields { display: none; }
    .iti--allow-dropdown .iti__flag-container,
    .iti--separate-dial-code .iti__flag-container {
        height: 60px;
    }
</style>

<section class="banner-section password-section">
    <div class="container">
        <div class="heading-parent same-heading-wrapper">
            <h2>BDA Payment</h2>
        </div>
        {{ Form::open(['method' => 'post', 'id' => 'createUserForm', 'class' => 'form form-signin']) }}
        
        <div class="user-password-wrapper">
            <div class="col-lg-12">
                <div class="login-from-parent">
                    <div class="from-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio2" value="swap_to_gimac">
                            <label class="form-check-label" for="inlineRadio2">BDA Payment</label>
                        </div>
                    </div>

                    <!-- <div class="input-box-parent from-group swap_fields">
                        <label>Id Type:</label>
                        <div class="custom-option-field">
                            <select class="form-control required" name="id_type" id="id_type">
                                <option value="" disabled selected>Select Id Type</option>
                                <option value="rsa_id_card">Republic of South Africa National Identity Card</option>
                                <option value="rsa_license">South African Driver's License</option>
                                <option value="rsa_passport">South African Passport</option>
                                <option value="rsa_tic">Temporary Identity Certificate (TIC)</option>
                            </select>
                            <span><img src="{{PUBLIC_PATH}}/assets/front/images/select-arrow.png" alt="image"></span>
                        </div>
                    </div> -->

                    <div class="input-box-parent from-group swap_fields">
                        <label>Product:</label>
                        <input type="text" class="form-control required" name="product" placeholder="Enter product" autocomplete="off">
                    </div>

                    <input type="hidden" name="country_id" id="country_id">
                    <div class="input-box-parent from-group">
                        <label>Iban:</label>
                        <input type="text" class="form-control required" name="iban" placeholder="Enter iban" autocomplete="off">
                    </div>

                    <div class="input-box-parent from-group">
                        <label>Partner Reference:</label>
                        <input type="text" class="form-control required" name="partnerreference" placeholder="Enter partnerreference" autocomplete="off">
                    </div>

                    <div class="input-box-parent from-group">
                        <label>Reason:</label>
                        <input type="textarea" class="form-control required" name="reason" placeholder="Enter reason" autocomplete="off">
                    </div>

                    <!-- <div class="input-box-parent from-group swap_fields">
                        <label>Receiver phone no:</label>
                    
                        
                        <div class="input-box-parent">
                            <input type="tel" class="required" id="phoneInput" placeholder="{{__('message.Enter Tel number')}}" pattern="[0-9]*" minlength="9" maxlength="9" required>
                          
                            <input type="hidden" name="countryCode" id="countryCode">
                        </div>
               
                    </div> -->

                <link media="all" type="text/css" rel="stylesheet" href="{{PUBLIC_PATH}}/assets/front/css/intlTelInput.css">
                <script src="{{PUBLIC_PATH}}/assets/front/js/intlTelInput.js"></script>

                    <!-- <div class="input-box-parent from-group swap_fields">
                        <label for="phone">Receiver phone no:</label>
                        <input id="phone" class="form-control required" placeholder="Enter phone number" autocomplete="off" pattern="[0-9]*" minlength="9" maxlength="9" name="phone" required type="tel" style="padding-left: 99px;">
                        <input type="hidden" name="countryCode" id="countryCode">
                    </div> -->

                    <div class="input-box-parent from-group">
                        <label>Amount :</label>
                        <input type="text" class="form-control required" name="amount" placeholder="Enter Amount" autocomplete="off" onkeypress="return validateFloatKeyPress(this,event);" minlength="1" maxlength="6">
                    </div>

                    <div class="login-btn">
                    <button type="button" id="clearButton" class="btn btn-secondary">Clear</button>
                    <button type="submit" class="btn btn-primaryx">INITIATE</button>
                    </div>
                </div>
            </div>
        </div>
        {{ Form::close() }}
    </div>
</section>

<link media="all" type="text/css" rel="stylesheet" href="{{PUBLIC_PATH}}/assets/front/css/intlTelInput.css">
<script src="{{PUBLIC_PATH}}/assets/front/js/intlTelInput.js"></script>

<script>
$(document).ready(function() {
    // List of allowed country codes
    var allowedCountries = ["cm", "cf", "td", "gq", "ga", "cg"]; // Country codes for allowed countries

    // Initialize intlTelInput on the input field with id "phone"
    var phoneInput = window.intlTelInput(document.querySelector("#phoneInput"), {
        separateDialCode: true,
        initialCountry: "cm", // Set Cameroon as the default selected country
        onlyCountries: allowedCountries, // Restrict the dropdown to the allowed countries
        utilsScript: "{{ asset('path/to/utils.js') }}" // Correct path to your utils.js
    });

    // Function to update the hidden country code field
    function updateCountryCode() {
        var selectedCountryData = phoneInput.getSelectedCountryData(); // Get selected country data
        var selectedCountryCode = selectedCountryData.dialCode; // Get the dial code
        $('#countryCode').val('+' + selectedCountryCode); // Set the hidden input field with the country code
    }

    // Update the hidden input field with the selected country code on country change
    $('#phone').on('countrychange', function() {
        updateCountryCode();
    });

    // Set the initial country code when the page loads
    updateCountryCode();

    // Function to handle form reset
    $("#clearButton").click(function() {
        $("#createUserForm")[0].reset();
        updateCountryCode(); // Reset the country code
    });

    // Show or hide fields based on the selected radio button option
    $('.swap_option').change(function() {
        if ($(this).val() === 'swap_to_gimac') {
            $('.swap_fields').show(); // Show the fields
        } else {
            $('.swap_fields').hide(); // Hide the fields
        }
    });

    // Trigger to show fields if phone number is valid
    $('#phone').on('input', function() {
        if ($(this).val().length >= 9) {
            $('.swap_fields').show(); // Show the fields when phone number is valid
        } else {
            $('.swap_fields').hide(); // Hide the fields if phone number is not valid
        }
    });

    // Validate float input for the amount
    function validateFloatKeyPress(el, evt) {
        var charCode = (evt.which) ? evt.which : event.keyCode;
        var number = el.value.split('.');
        if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        // Just one dot
        if (number.length > 1 && charCode == 46) {
            return false;
        }
        // Get the caret position
        var caratPos = getSelectionStart(el);
        var dotPos = el.value.indexOf(".");
        if (caratPos > dotPos && dotPos > -1 && (number[1].length > 1)) {
            return false;
        }
        return true;
    }

    // Attach the float validation function to the amount input
    $('input[name="amount"]').on('keypress', function(event) {
        return validateFloatKeyPress(this, event);
    });
});

// Helper function to get the caret position
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

$("#createUserForm").validate({
        rules: {
            "product":"required",
            "iban":"required",
            "partnerreference":"required",
            "reason":"required",
            "amount": {
                    required: true,
                    decimal: true  // Use custom 'decimal' rule
            }
        },
        messages: {
            "product": "Enter product name",
            "iban": "Enter iban",
            "partnerreference": " Enter  partner reference",
            "reason": "Enter reason",
            "amount": {
                    required: "Enter amount",
                    decimal: "Please enter a valid numeric value for the amount."
            }
        },
    });
    



$(document).ready(function () {
    // Default to "Swap to GIMAC" form on page load
    var selectedOption = sessionStorage.getItem('selected_option') || 'swap_to_gimac'; // Set to 'swap_to_gimac' if not set
    $('input[name="option"][value="' + selectedOption + '"]').prop('checked', true);

    // Update form fields based on the selected option
    updateFormFields(selectedOption);

    // Handle option change
    $('.swap_option').change(function () {
        var selectedValue = $(this).val();
        updateFormFields(selectedValue);

        // Store selected option in session storage
        sessionStorage.setItem('selected_option', selectedValue);
    });

    // Function to show/hide fields based on the selected option
    function updateFormFields(selectedValue) {
        if (selectedValue === "swap_to_swap") {
            $('.swapto').show();
            $('.swap_fields').hide();
            $('#phone').attr('name', 'phone');
            $('#phoneInput').removeAttr('name');
        } else if (selectedValue === "swap_to_gimac") {
            $('.swapto').hide();
            $('.swap_fields').show();
            $('#phoneInput').attr('name', 'phone');
            $('#phone').removeAttr('name');
        }
    }
});

</script>
@endsection
