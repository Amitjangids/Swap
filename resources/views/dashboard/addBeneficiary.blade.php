@extends('layouts.home')
@section('content')
    <style>

.swap_fields{display:none}
.iti--allow-dropdown .iti__flag-container, .iti--separate-dial-code .iti__flag-container {
    height: 60px;
}
        section.tiles-section-wrapper.beneficiary-main-wrapper .login-btn {
            margin: 0 !important;
        }

        .beneficiary-main-form.login-from-parent {
            margin: 0 30px 70px !important;
        }
    </style>
 
    @if (session('fail_message'))
        <div class="alert alert-danger">
            {{ session('fail_message') }}
        </div>
    @endif
   <section class="banner-section password-section">
       <div class="container">
            <div class="heading-parent same-heading-wrapper">
               <h2>{{__('message.Add Beneficiary')}}</h2>
           </div>
           {{ Form::open(array('method' => 'post', 'id' => 'createUserForm', 'class' => 'form form-signin')) }}
           
           <div class="user-password-wrapper">
               <div class="col-lg-12">
                   <div class="login-from-parent">
                       <div class="from-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio1" value="swap_to_swap" checked="">
                                <label class="form-check-label" for="inlineRadio1">{{__('message.Swap To Swap')}}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio2" value="swap_to_gimac">
                                <label class="form-check-label" for="inlineRadio2">{{__('message.Swap To CEMAC Wallet')}}</label>
                            </div>
                        </div>

                       <div class="input-box-parent from-group swap_fields" style="display: none;">
                            <label>{{__('message.First Name')}}:</label>
                            <input type="text" class="form-control required" name="first_name" placeholder="{{__('message.Enter First Name')}}" autocomplete="off">
                        </div>

                        <div class="input-box-parent from-group swap_fields" style="display: none;">
                            <label>{{__('message.Name')}}:</label>
                            <input type="text" class="form-control required" name="name" placeholder="{{__('message.Enter Name')}}" autocomplete="off">
                        </div>

                        
                        <div class="input-box-parent from-group swap_fields">
                            <label>{{__('message.Country')}}</label>
                            <div class="custom-option-field">
                                <div class="country">
                                    <div id="country" class="select form-control country ">{{__('message.Select Country')}}</div>
                                    <div id="country-drop" class="dropdown">
                                        <ul>
                                        @if($lang == 'fr')
                                           <?php foreach ($country_list as $country): ?>
                                                <li  class="country_list" data-code="<?php echo $country['code']; ?>" data-name="<?php echo $country['name_fr']; ?>" data-cid="<?php echo $country['id']; ?>">
                                                    <span class="country-flag-icon"><img src="<?php echo PUBLIC_PATH . '/assets/front/images/' . $country['flag']; ?>" alt="flag-icon"></span> <?php echo $country['name_fr']; ?>
                                                </li>
                                            <?php endforeach; ?>
                                           @else
                                            <?php foreach ($country_list as $country): ?>
                                                <li  class="country_list" data-code="<?php echo $country['code']; ?>" data-name="<?php echo $country['name']; ?>" data-cid="<?php echo $country['id']; ?>">
                                                    <span class="country-flag-icon"><img src="<?php echo PUBLIC_PATH . '/assets/front/images/' . $country['flag']; ?>" alt="flag-icon"></span> <?php echo $country['name']; ?>
                                                </li>
                                            <?php endforeach; ?>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <input type ="hidden" name="country_id" id="country_id">


                        <div class="input-box-parent from-group swap_fields">
                            <label>{{__('message.Wallet Manager')}}</label>
                            <div class="custom-option-field" id="updateWalletManager">
                                <select class="form-control required" name="wallet_manager_id">
                                     <option value="">{{__('message.Select Wallet Manager')}}</option>
                                     @if($lang == 'fr')
                                    <?php foreach($wallet_manager_list as $value) {  ?>
                                    <option value="{{$value->id}}">{{$value->name_fr}}</option>
                                    <?php } ?>
                                    @else
                                    <?php foreach($wallet_manager_list as $value) {  ?>
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                    <?php } ?>
                                    @endif
                                </select>
                                <span><img src="{{PUBLIC_PATH}}/assets/front/images/select-arrow.png" alt="image"></span>
                            </div>
                        </div>


                        <div class="input-box-parent from-group swapto">
                        <label for="phone">{{__('message.Tel number')}}:</label>
                        <input id="phone" class="form-control required" placeholder="{{__('message.Enter phone number')}}" autocomplete="off" pattern="[0-9]*"  minlength="9" maxlength="9" name="phone" required="" type="tel" style="padding-left: 99px;">
               
                        <input type="hidden" name="countryCode" id="countryCode">
                        </div>
                        <div class="input-box-parent from-group swap_fields">
                            <label>{{__('message.Tel number')}}:</label>
                            <div class="login-contact form-control">
                                <div class="country-box country" id="countrybox">
                                    <img id="selectedCountryFlag" src="{{HTTP_PATH}}/public/assets/front/images/country-flag.png" alt="country flag">
                                    <span id="selectedCountryCode"></span>
                                </div>
                                <div class="input-box-parent">
                                <input type="tel" class="required"  id="phoneInput" placeholder="{{__('message.Enter Tel number')}}" pattern="[0-9]*" minlength="9" maxlength="9" required>
                                    <!-- Add hidden input field for country code -->
                                    <input type="hidden" name="country_code" id="countryCodeInput">
                                </div>  
                            </div>
                        </div>
                        <div class="login-btn">
                            <button type="button" id="clearButton" class="btn btn-secondary">{{__('message.Clear')}}</button>
                            <button type="submit" class="btn btn-primaryx">{{__('message.INITIATE')}}</button>
                        </div>
                    </div>
               </div>
           </div>
           {{ Form::close()}}
       </div>
   </section>  

   

   <link media="all" type="text/css" rel="stylesheet" href="{{PUBLIC_PATH }}/assets/front/css/intlTelInput.css">
<script src="{{PUBLIC_PATH }}/assets/front/js/intlTelInput.js"></script>

<script>
document.getElementById('phone').addEventListener('keypress', function (event) {
// Allow only numeric characters (0-9)
if (event.which < 48 || event.which > 57) {
event.preventDefault();
}
});
</script>

<script>
  $(document).ready(function() {
    // List of allowed country codes
    var allowedCountries = ["cm", "cf", "td", "gq", "ga", "cg"]; // Country codes for allowed countries

    // Initialize intlTelInput on the input field with id "phone"
    var phoneInput = window.intlTelInput(document.querySelector("#phone"), {
        separateDialCode: true,
        initialCountry: "cm", // Set Cameroon as the default selected country
        onlyCountries: allowedCountries, // Restrict the dropdown to the allowed countries
        utilsScript: "{{ asset('path/to/utils.js') }}" // Correct path to your utils.js
    });

    // Update the hidden input field with the selected country code on country change
    $('#phone').on('countrychange', function() {
        var selectedCountryCode = phoneInput.getSelectedCountryData().dialCode;
        $('#countryCode').val('+' + selectedCountryCode);
    });
   

    // Set the initial country code when the page loads
    var initialCountryCode = phoneInput.getSelectedCountryData().dialCode;
    console.log(initialCountryCode);
    $('#countryCode').val('+' + initialCountryCode);
});

  
</script>
</script>
   
   <script type="text/javascript">
    $(document).ready(function () { 

        $("#clearButton").click(function() {
            $("#createUserForm")[0].reset();
        });


    $('.swap_option').change(function(){
        var selectedValue = $(this).val(); 
        if(selectedValue=="swap_to_swap")
        {
          $('.swapto').show(); 
          $('.swap_fields').hide(); 
          $('#phone').attr('name','phone');
          $('#phoneInput').removeAttr('name','phone');

        }
        else{
          $('.swapto').hide();
          $('.swap_fields').show(); 
          $('#phoneInput').attr('name','phone');
          $('#phone').removeAttr('name','phone');
        }
    });


    $('.country_list').click(function(){
    var selectedValue = $(this).data('cid');
    $('#country_id').val(selectedValue);
    $.ajax({
        url: '{{HTTP_PATH}}/getWalletmanagerList',  // Replace with your Laravel route or controller endpoint
        type: 'POST',
        data: {
          _token: "{{ csrf_token() }}",  // Include CSRF token
          country_id: selectedValue  // Include the selected country ID
        },
        success: function(response) {
          $('#updateWalletManager').html(response);
        },
      })
    });

    $.validator.addMethod("decimal", function(value, element) {
            // Allow both integers and decimals
            return /^\d+(\.\d{1,2})?$/.test(value);
        }, "{{__('message.Please enter a valid numeric value for the amount.')}}");

    $("#createUserForm").validate({
        rules: {
            "first_name":"required",
            "name":"required",
            "country_id":"required",
            "wallet_manager_id":"required",
            "phone": {
                    required: true,
                    digits: true,
                    minlength: 9,
                    maxlength: 9
                    
            }
           
        },
        messages: {
            "first_name": "{{__('message.Enter first name')}}",
            "name": "{{__('message.Enter last name')}}",
            "country_id": "{{__('message.Select your countr')}}",
            "wallet_manager_id": "{{__('message.Select wallet manager')}}",
            "phone": {
                    required: "{{__('message.Enter a phone number')}}",
                    digits: "{{__('message.Please enter only numeric values for the phone numbe')}}",
                     minlength: "{{__('message.Please enter at least 9 number.')}}",
                     
            },
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
        Selected.click(function(e) {
            e.stopPropagation(); // Prevent event bubbling
            Selected.toggleClass('open');
            Drop.toggle();
        });

        // Handle item selection within the dropdown
        DropItem.click(function(e) {
            e.stopPropagation(); // Prevent event bubbling
            Selected.removeClass('open');
            Drop.hide();

            var item = $(this);
            Selected.html(item.html());
        });

        // Close dropdown when clicking outside of it
        $(document).click(function(e) {
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
    document.querySelectorAll('.country li').forEach(function(element) {
        element.addEventListener('click', function() {
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
  $(document).ready(function() {
    // Retrieve the stored language
    var lang = sessionStorage.getItem('lang');
    
    // Retrieve the stored selected option
    var selectedOption = sessionStorage.getItem('selected_option');
   

    // Set the stored selected option
    if (selectedOption) {
        $('input[name="option"][value="' + selectedOption + '"]').prop('checked', true);
        updateFormFields(selectedOption); // Update form fields based on selected option
    }

    // Set up language change event handler
    $('.change-language').on('click', function() {
        var newLang = $(this).data('lang'); // Assuming language change is triggered by an element with class 'change-language'
        changeLanguage(newLang);
    });

    // Handle option change
    $('.swap_option').change(function() {
        var selectedValue = $(this).val();
        updateFormFields(selectedValue);
    });
});

function changeLanguage(val) {
    var selectedOption = $('input[name="option"]:checked').val();
    sessionStorage.setItem('selected_option', selectedOption);
    sessionStorage.setItem('lang', val);

    $.ajax({
        url: "{!! HTTP_PATH !!}/lang/" + val,
        type: "GET",
        success: function(result) {
            location.reload(); // Reload the page to apply the new language
        }
    });
}

function updateFormFields(selectedValue) {
    if (selectedValue === "swap_to_swap") {
        $('.swapto').show();
        $('.swap_fields').hide();
        $('#phone').attr('name', 'phone');
        $('#phoneInput').removeAttr('name');
    } else {
        $('.swapto').hide();
        $('.swap_fields').show();
        $('#phoneInput').attr('name', 'phone');
        $('#phone').removeAttr('name');
    }
}

</script>
@endsection
