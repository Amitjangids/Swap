<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>

{{ HTML::script('public/assets/js/jquery.fancybox.js?v=2.1.5')}}
{{ HTML::style('public/assets/css/jquery.fancybox.css?v=2.1.5')}}
{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
<script type="text/javascript">

    $(document).ready(function () {
        $('.fancybox').fancybox();
    });
</script>
<style>
    span.text-danger {
        color: red !important;
    }
</style>
@if(!empty($userInfo->selfie_image) || !empty($userInfo->identity_front_image))
    <div class="panel-body marginzero">
        <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left">KYC Details List</div>
                <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">

                </div>
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th class="sorting_paging">Id Type</th>
                            <th class="sorting_paging">Selfile Image</th>
                            <th class="sorting_paging">Front Image</th>
                            @if(!empty($userInfo->identity_back_image))
                                <th class="sorting_paging">Back Image</th>
                            @endif
                            <?php    global $documents; ?>


                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <!-- @foreach ($documents as $value)
                                                    @if (is_array($value) && array_key_exists('id', $value))
                                                        @if ($value['id'] == $userInfo->national_identity_type)
                                                            <td data-title="Identity Type">{{ $value['name'] }}</td>
                                                        @endif
                                                    @endif
                                                @endforeach -->
                            <td data-title="Identity Type">{{ $userInfo->national_identity_type }}</td>
                            @if($userInfo->national_identity_type == "")
                                <td data-title="Identity Type"></td>
                            @endif

                            <td data-title="Picture Selfie Image">
                                @if($userInfo->selfie_image != '')

                                                        <a href="{{ $userInfo->selfie_image }}" title="View KYC Document"
                                                            data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                                            {{HTML::image($userInfo->selfie_image, SITE_TITLE, [
                                        'style' => 'max-width:50px;
                                                                                                                                                                                                                                                                                                                        max-height:50px;'
                                    ])}}
                                                        </a>

                                @else
                                    No Image
                                @endif

                            </td>
                            <td data-title="Picture Front Image">
                                @if($userInfo->identity_front_image != '')
                                                        <a href="{{$userInfo->identity_front_image}}" title="View KYC Document"
                                                            data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                                            {{HTML::image($userInfo->identity_front_image, SITE_TITLE, [
                                        'style' => 'max-width:50px;
                                                                                                                                                                                                                                                                                                                        max-height:50px;'
                                    ])}}
                                                        </a>
                                @else
                                    No Image
                                @endif

                            </td>
                            <td data-title="Picture Selfie Image">
                                @if($userInfo->identity_back_image != '')
                                                        <a href="{{$userInfo->identity_back_image}}" title="View KYC Document"
                                                            data-fancybox-group="gallery1" class="fancybox" target="_blank">
                                                            {{HTML::image($userInfo->identity_back_image, SITE_TITLE, [
                                        'style' => 'max-width:50px;
                                                                                                                                                                                                                                                                                                                        max-height:50px;'
                                    ])}}
                                                        </a>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="search_frm"> <?php    //echo $userInfo->is_kyc_done; ?>

                    <?php    if ($userInfo->kyc_status != "completed") { ?>
                    <a href="#" id="approveBtn" title="Approve" class="btn btn-info">Approve Kyc</a>

                    <!-- <a href="{{ URL::to('admin/users/approvekyc/' . $userInfo->slug) }}" title="Approve"
                                                        class="btn btn-info">Approve Kyc</a> -->
                    <?php    } ?>
                    <!-- <?php    if ($userInfo->kyc_status != "rejected" && $userInfo->kyc_status != "completed") { ?>
                                        <a href="{{ URL::to('admin/users/declinekyc/' . $userInfo->slug) }}" title="Reject"
                                            class="btn btn-info">Reject Kyc</a>
                                        <?php    } ?> -->

                    <a href="{{ URL::to('admin/users')}}" title="Back" class="btn btn-default canlcel_le">Back</a>
                </div>
            </div>
        </section>
        {{ Form::close()}}
    </div>
    </div>

    <div id="approveButton" style="display: none;">
        <div class="nzwh-wrapper">
            <fieldset class="nzwh">
                <legend class="head_pop">Add KYC Details</legend>
                {{ Form::model($userInfo, ['url' => url('admin/users/approvekyc/' . $userInfo->slug), 'method' => 'post', 'id' => 'adminForm', 'enctype' => 'multipart/form-data']) }}
                <div class="form-horizontal">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Identity Type <span class="require">*</span></label>
                            <div class="col-sm-10">

                                {{ Form::select(
            'idType',
            [
                '' => 'Select ID Type',
                'PASSPORT' => 'PASSPORT',
                'IDENTITY_CARD' => 'IDENTITY_CARD',
            ],
            null,
            [
                'class' => 'form-control required',
                'autocomplete' => 'off'
            ]
        ) }}

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Identity Number <span class="require">*</span></label>
                            <div class="col-sm-10">
                                {{ Form::text('idNumber', null, ['class' => 'form-control required', 'id' => 'idNumber', 'placeholder' => 'Identity Number', 'autocomplete' => 'off', 'maxlength' => 20]) }}
                            </div>
                        </div>
                        <div class="box-footer" style="margin: 0px 0px 0px 100px;">
                            {{ Form::submit('Submit', ['class' => 'btn btn-info', 'id' => 'approveSubmit']) }}
                        </div>
                    </div>
                </div>
                {{ Form::close() }}
            </fieldset>
        </div>
    </div>
@else
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
@endif


<script type="text/javascript">
$(document).ready(function () {
    let isSubmitting = false;

    // 🔤 Allow only alphanumeric + hide error when valid
    $("#idNumber").on("input", function () {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');

        // 🔄 Re-validate this field to hide error
        if ($("#adminForm").data("validator")) {
            $("#adminForm").validate().element("#idNumber");
        }
    });

    $("#approveBtn").on("click", function () {
        $("#approveButton").show();

        if (!$("#adminForm").data("validator")) {
            initAdminFormValidation();
        }
    });

    $("#adminForm").on("keydown", "input", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            $("#adminForm").submit();
        }
    });

    $("#adminForm").on("submit", function (e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        if (!$(this).valid()) {
            e.preventDefault();
            return false;
        }

        if (!confirm("Are you sure you want to approve this?")) {
            e.preventDefault();
            return false;
        }

        isSubmitting = true;
        $("#approveSubmit")
            .prop("disabled", true)
            .text("Processing...");
    });
});

function initAdminFormValidation() {
    // ✅ Add alphanumeric rule (required!)
    $.validator.addMethod("alphanumeric", function (value, element) {
        return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
    }, "Only letters and numbers allowed");

    $("#adminForm").validate({
        ignore: [],
        rules: {
            idNumber: {
                required: true,
                alphanumeric: true
            }
        },
        messages: {
            idNumber: {
                required: "Identity Number is required",
                alphanumeric: "Only letters and numbers allowed"
            }
        },
        errorElement: "span",
        errorClass: "text-danger"
    });
}
</script>


<!-- <script>
document.getElementById('approveSubmit').addEventListener('click', function () {
    this.disabled = true;
    this.value = 'Processing...';
    this.form.submit();
});
</script> -->