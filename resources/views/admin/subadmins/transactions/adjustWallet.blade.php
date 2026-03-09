@extends('layouts.admin')
@section('content')
{{ HTML::style('public/assets/css/chosen.min.css')}}

{{ HTML::script('public/assets/js/chosen.jquery.min.js')}}
<script>
    $(function () {
        $("#select_id").chosen({disable_search_threshold: 10});
    })
</script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#adminForm").validate();
         $.validator.addMethod("dollarsscents", function(value, element) {
        return this.optional(element) || /^\d{0,10}(\.\d{0,2})?$/i.test(value);
    }, "You can enter amount upto 10 digits with two decimal points.");
    });
    
    function getBalance(id){
        $.ajax({
            type: 'POST',
            url: "<?php echo HTTP_PATH; ?>/admin/transactions/getBalance",
            data: {'id':id, _token: '{{csrf_token()}}'},
            cache: false,
            success: function (result) {
                $('#wallet_balance').val(result);
            }
        });
    }
</script>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Adjust User Wallet Balance</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/users')}}"><i class="fa fa-cogs"></i> <span>User Management</span></a></li>
            <li class="active"> Adjust User Wallet Balance</li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{ Form::open(array('method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data")) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Select User <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <select data-placeholder="Select User" name="user_id" id="select_id" class="form-control chosen-select required" tabindex="5" onchange="getBalance(this.value);">
                                <option value="">Select User</option>
                                <?php
                                if ($users) {
                                    echo '<optgroup label="Agent">';
                                    foreach ($users['Agent'] as $key => $user) {
                                        echo '<option value="' . $key . '">' . $user . '</option>';
                                    }
                                    echo '</optgroup>';
                                    echo '<optgroup label="User">';
                                    foreach ($users['User'] as $key => $user) {
                                        echo '<option value="' . $key . '">' . $user . '</option>';
                                    }
                                    echo '</optgroup>';
                                    echo '<optgroup label="Merchant">';
                                    foreach ($users['Merchant'] as $key => $user) {
                                        echo '<option value="' . $key . '">' . $user . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Wallet Balance <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('wallet_amount', null, ['id'=>'wallet_balance','class'=>'form-control required', 'placeholder'=>'Wallet Balance', 'autocomplete' => 'off', 'disabled' => 'true'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Wallet Action <span class="require">*</span></label>
                        <div class="col-sm-10">
                            <?php $serviceType = array('Debit' => 'Debit', 'Credit' => 'Credit'); ?>                        
                            {{Form::select('service_name', $serviceType,null, ['class' => 'form-control required','placeholder' => 'Select Action'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Amount<span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('amount', null, ['class'=>'form-control required dollarsscents', 'placeholder'=>'Amount', 'autocomplete' => 'off'])}}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Reason <span class="require">*</span></label>
                        <div class="col-sm-10">
                            {{Form::text('reason', null, ['class'=>'form-control required', 'placeholder'=>'Reason', 'autocomplete' => 'off'])}}
                        </div>
                    </div>

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        {{Form::reset('Reset', ['class' => 'btn btn-default canlcel_le'])}}
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
    @endsection