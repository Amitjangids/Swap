@extends('layouts.admin')
@section('content')
<script type="text/javascript">
    $(document).ready(function () {
        $("#adminForm").validate();
    });
</script>
<style type="text/css">
    .contentSet {
        padding: 15px;
        margin-right: auto;
        margin-left: auto;
        padding-left: 15px;
        padding-right: 15px;
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #6E6E6E;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: green;
    }

    input:focus + .slider {
        box-shadow: 0 0 0 4px rgba(21, 156, 228, 0.7);
        outline: none;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

</style>
<div class="content-wrapper">
    <section class="content-header">
        <h1>Change Merchant Setting</h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
            <li><a href="{{URL::to('admin/merchants')}}"><i class="fa fa-users"></i> <span>Manage Merchant Users</span></a></li>
            <li class="active">Change Merchant Setting</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">&nbsp;</h3>
            </div>
            <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
            {{Form::model($userInfo, ['method' => 'post', 'id' => 'adminForm', 'class' => 'form form-signin']) }}
            <div class="form-horizontal">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-6 control-label pull-left">Do you want to apply the refund transaction Fee charges to user? <span class="require"></span></label>
                        <div class="col-sm-6  pull-left">
                            <div class="contentSet">
                                <label class="switch">
                                    <?php
                                    $checked = '';
                                    if ($userInfo->trans_pay_by == 'User') {
                                        $checked = 'checked=checked';
                                    }
                                    ?>
                                    <input type="checkbox" <?php echo $checked; ?> name="trans_pay_by" value="0">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-6 control-label  pull-left">Do you want to apply the shop payment transaction Fee charges to user? <span class="require"></span></label>
                        <div class="col-sm-6  pull-left">
                            <div class="contentSet">
                                <label class="switch">
                                    <?php
                                    $checked = '';
                                    if ($userInfo->shopping_trans_pay_by == 'User') {
                                        $checked = 'checked=checked';
                                    }
                                    ?>
                                    <input type="checkbox" <?php echo $checked; ?> name="shopping_trans_pay_by" value="0">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-6 control-label  pull-left">Do you want to apply the merchant deposit transaction Fee charges to user? <span class="require"></span></label>
                        <div class="col-sm-6  pull-left">
                            <div class="contentSet">
                                <label class="switch">
                                    <?php
                                    $checked = '';
                                    if ($userInfo->deposit_trans_pay_by == 'User') {
                                        $checked = 'checked=checked';
                                    }
                                    ?>
                                    <input type="checkbox" <?php echo $checked; ?> name="deposit_trans_pay_by" value="0">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>

             <div class="form-group">
                        <label class="col-sm-6 control-label  pull-left">Do you want to apply the merchant withdrawal transaction Fee charges to user? <span class="require"></span></label>
                        <div class="col-sm-6  pull-left">
                            <div class="contentSet">
                                <label class="switch">
                                    <?php
                                    $checked = '';
                                    if ($userInfo->newwithdrawal_trans_pay_by == 'User') {
                                        $checked = 'checked=checked';
                                    }
                                    ?>
                                    <input type="checkbox" <?php echo $checked; ?> name="newwithdrawal_trans_pay_by" value="0">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                        <a href="{{URL::to('admin/merchants')}}" class="btn btn-default canlcel_le">Cancel</a>
                    </div>
                </div>
            </div>
            {{ Form::close()}}
        </div>
    </section>
</div>
@endsection