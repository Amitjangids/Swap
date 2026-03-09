@extends('layouts.home')
@section('content')
<style>
    .swap_fields {
        display: none
    }

    /* Basic styles for the modal */
    .modal1 {
        display: flex;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    .modal-content1 {
        background-color: white;
        padding: 100px;
        border-radius: 5px;
        text-align: center;
    }

    .bulk-form-wrapper div#duplicateWarning .modal1 .modal-content1 {
        padding: 50px;
        border-radius: 13px;
    }

    .bulk-form-wrapper div#duplicateWarning .modal1 .modal-content1 h3 {
        color: #000;
        font-weight: 500;
        line-height: 1.4;
        margin: 0 0 5px;
    }

    .bulk-form-wrapper div#duplicateWarning .modal1 .modal-content1 p {
        color: #000;
        font-weight: 400;
        line-height: 1.6;
        margin: 0;
    }

    .duplicate-modal-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 30px 0 0;
    }

    .duplicate-modal-btn button {
        padding: 12px 15px;
        border-radius: 7px;
        text-align: center;
        min-width: 90px;
        margin: 0 5px;
        font-size: 16px;
        border: 1px solid transparent;
        transition: 0.4s;
        -webkit-transition: 0.4s;
    }

    .duplicate-modal-btn button#confirmYes {
        background: #000;
        color: #fff;
        border-color: transparent;
    }

    .duplicate-modal-btn button#confirmYes:hover {
        background: transparent;
        color: #000;
        border-color: #000;
    }

    .duplicate-modal-btn button#confirmNo {
        background: #4b2e6f;
        color: #fff;
        border-color: #4b2e6f;
    }

    .duplicate-modal-btn button#confirmNo:hover {
        color: #4b2e6f;
        background: transparent;
    }
</style>

<section class="banner-section password-section">
    <div class="container">
        <div class="heading-parent same-heading-wrapper">
            <h2>{{__('message.Bulk Transactions')}}</h2>
        </div>
        {{ Form::open(array('method' => 'post','route' => 'bulk.transfer', 'id' => 'fileUploadForm', 'class' => 'form form-signin','enctype'=>'multipart/form-data')) }}

        @csrf
        <input type="hidden" id="selectedOption" name="selected_option" value="">
        <div class="user-password-wrapper">
            <div class="col-lg-12">
                <div class="single-modal-content login-from-parent">
                    <!-- Start New Html -->
                    <div class="from-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio1" value="swap_to_swap" checked>
                            <label class="form-check-label" for="inlineRadio1">{{__('message.Swap To Swap')}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio2" value="swap_to_gimac">
                            <label class="form-check-label" for="inlineRadio2">{{__('message.Swap To CEMAC Wallet')}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio3" value="swap_to_bda" >
                            <label class="form-check-label" for="inlineRadio3">{{__('message.Swap to UEMOA Bank')}}</label>
                        </div>
                        <?php

                        //if ($_SERVER['HTTP_X_FORWARDED_FOR'] == "183.83.53.65") { ?>


                            <div class="form-check form-check-inline">
                                <input class="form-check-input swap_option" type="radio" name="option" id="inlineRadio4" value="swap_to_onafriq">
                                <label class="form-check-label" for="inlineRadio4">{{__('message.Swap to UEMOA Wallet')}}</label>
                            </div>

                        <?php //} ?>
                    </div>
                    <!-- End Html -->

                    <div class="form-group">
                        <div class="download-submitter-btn">
                            <label>{{__('message.File Upload')}}</label>
                            <a href="{{PUBLIC_PATH}}/assets/front/SwapToSwap.xlsx" class="btn btn-primaryx swapS"><i class="fa fa-plus" aria-hidden="true"></i> {{__('message.Download Sample File')}}</a>
                            <a href="{{PUBLIC_PATH}}/assets/front/SwapToGimac.xlsx" class="btn btn-primaryx swapG"><i class="fa fa-plus" aria-hidden="true"></i> {{__('message.Download Sample File')}}</a>
                            <a href="{{PUBLIC_PATH}}/assets/front/SwapToBda.xlsx" class="btn btn-primaryx swapB"><i class="fa fa-plus" aria-hidden="true"></i> {{__('message.Download Sample File')}}</a>
                            <a href="{{PUBLIC_PATH}}/assets/front/SwapToOnafriq.xlsx" class="btn btn-primaryx swapO"><i class="fa fa-plus" aria-hidden="true"></i> {{__('message.Download Sample File')}}</a>
                        </div>
                        <div class="file-upload-box">
                            <h5>{{__('message.Select from library')}}</h5>
                            <div class="file-upload-btn">
                                <div class="imageWrapper">
                                    <img class="image" src="">
                                </div>
                                <button class="file-upload">
                                    <input type="file" id="excelFile" name="excel_file" class="file-input" required accept=".xlsx, .xls">
                                    <p id="fileName"> <img src="{{PUBLIC_PATH}}/assets/front/images/select-file-img.png" alt="image"></p>
                                    
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{__('message.Remarks (for reviewer)')}}</label>
                        <div class="custom-date-box">
                            <textarea class="form-control" name="remarks" maxlength="160"></textarea>
                            <small id="charCount">{{__('message.160 characters remaining')}}</small>
                        </div>
                    </div>
                    <!-- Warning Modal -->
                    <div id="duplicateWarning" style="display: none;">
                        <div class="modal1">
                            <div class="modal-content1">
                                <h3>{{__('message.Duplicate Warning')}}</h3>
                                <div class="duplicate-modal-btn">
                                    <button id="confirmYes">{{__('message.Yes')}}</button>
                                    <button id="confirmNo">{{__('message.No')}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="login-btn">
                        <button type="button" id="clearButton" class="btn btn-secondary">{{__('message.Clear')}}</button>
                        <button type="submit" id="submitBtn" class="btn btn-primaryx">{{__('message.Submit')}}</button>
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
    $(document).ready(function() {
        var selectedValue = $('input[name="option"]:checked').val();
        updateFormFields(selectedValue);

        $('.swap_option').change(function() {
            var selectedValue = $(this).val();
            updateFormFields(selectedValue);
        });

        // Custom validation method for file extension
        $.validator.addMethod("excelExtension", function(value, element) {
            var fileInput = element;
            var file = fileInput.files[0];

            if (file) {
                var fileName = file.name;
                var fileExtension = fileName.split('.').pop().toLowerCase();

                if (fileExtension === 'xlsx' || fileExtension === 'xls') {
                    // Show the file name
                    $('#fileName').text(fileName);
                    return true;
                } else {
                    // Clear the displayed file name
                    $('#fileName').text('');
                    return false;
                }
            }

            $('#fileName').text('');
            return true;
        }, "<?php echo __('message.Please select a valid .xlsx or .xls file.'); ?>");

        // Initialize jQuery Validation
        $("#fileUploadForm").validate({
            rules: {
                excel_file: {
                    required: true,
                    excelExtension: true
                }
            },
            messages: {
                excel_file: {
                    required: "<?php echo __('message.Please select a file.'); ?>",
                    excelExtension: "<?php echo __('message.Please select a valid .xlsx or .xls file.'); ?>"
                }
            },
        });

        // File input change event to validate on change
        $("#excelFile").change(function() {
            $("#fileUploadForm").valid();
            $('#submitBtn').prop('disabled', false);
        });

        // Clear button functionality
        $("#clearButton").click(function() {
            // Reset the form elements
            $("#fileUploadForm")[0].reset();
            // Reset the validation state
            $("#fileUploadForm").validate().resetForm();
            // Clear the displayed file name
            $('#fileName').text('');
            $('#submitBtn').prop('disabled', false);
        });

        // Form submission logic
        document.getElementById('fileUploadForm').onsubmit = function(event) {
            document.getElementById('submitBtn').disabled = true;
            event.preventDefault(); // Prevent default form submission

            if (!$("#fileUploadForm").valid()) {
                return; // Stop if the form is not valid
            }

            document.getElementById('submitBtn').disabled = true;
            let formData = new FormData(this);

            // Send the file to the server to check for duplicates
            fetch('{{ route("bulk.check") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.hasDuplicates) {

                        document.getElementById('duplicateWarning').style.display = 'flex';

                        document.getElementById('confirmYes').onclick = function() {

                            document.getElementById('duplicateWarning').style.display = 'none';
                            // Proceed with upload
                            document.getElementById('fileUploadForm').onsubmit = null; // Remove the onsubmit handler
                            document.getElementById('loader-wrapper').style.display = 'block';
                            document.getElementById('fileUploadForm').submit();



                        };

                        document.getElementById('confirmNo').onclick = function() {
                            window.location.href = "";
                            $('#submitBtn').prop('disabled', false);
                        };
                    } else {
                        document.getElementById('fileUploadForm').onsubmit = null;
                        document.getElementById('loader-wrapper').style.display = 'block';
                        document.getElementById('fileUploadForm').submit();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    $('#submitBtn').prop('disabled', false);
                });
        };
    });

    const textarea = document.querySelector('textarea[name="remarks"]');
    const charCount = document.getElementById('charCount');

    textarea.addEventListener('input', function() {
        const remaining = 160 - textarea.value.length;
        charCount.textContent = `${remaining} {{__('message.characters remaining')}}`;
    });
</script>
<script type="text/javascript">
    function updateFormFields(selectedValue) {
        
        if (selectedValue === "swap_to_swap") {
            $('.swapS').show();
            $('.swapG').hide();
            $('.swapB').hide();
            $('.swapO').hide();
            $('#fileUploadForm').attr('action', '{{HTTP_PATH}}/bulk-transfer');
        } else if (selectedValue === "swap_to_gimac") {
            $('.swapS').hide();
            $('.swapG').show();
            $('.swapB').hide();
            $('.swapO').hide();
            $('#fileUploadForm').attr('action', '{{HTTP_PATH}}/bulk-transfer-gimac');
        } else if (selectedValue === "swap_to_bda") {
            $('.swapS').hide();
            $('.swapG').hide();
            $('.swapB').show();
            $('.swapO').hide();
            $('#fileUploadForm').attr('action', '{{HTTP_PATH}}/bulk-transfer-bda');
        } else if (selectedValue === "swap_to_onafriq") {
            $('.swapS').hide();
            $('.swapG').hide();
            $('.swapB').hide();
            $('.swapO').show();
            $('#fileUploadForm').attr('action', '{{HTTP_PATH}}/bulk-transfer-onafriq');
        }
    }
</script>

@endsection