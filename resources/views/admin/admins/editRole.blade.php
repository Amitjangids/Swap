@extends('layouts.admin')
@section('content')
    <script type="text/javascript">
        $(document).ready(function() {
            $("#adminForm").validate({
                rules: {
                    'permission[]': {
                        required: true,
                        minlength: 1
                    }
                },
                messages: {
                    'permission[]': "Please select at least one permission."
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "permission[]") {
                        error.appendTo($("#errorContainer"));
                    } else {
                        error.insertAfter(element);
                    }
                }
            });
        });
    </script>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>Edit Role</h1>
            <ol class="breadcrumb">
                <li><a href="{{ URL::to('admin/admins/dashboard') }}"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="{{ URL::to('admin/admins/department') }}"><i class="fa fa-user"></i> <span>Manage
                            Roles</span></a></li>
                <li class="active"> Edit Role</li>
            </ol>
        </section>
        <section class="content">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">&nbsp;</h3>
                </div>
                <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
                {{ Form::model($role, ['method' => 'post', 'id' => 'adminForm', 'enctype' => 'multipart/form-data']) }}
                <div class="form-horizontal">
                    <div class="box-body">

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Role Name <span class="require">*</span></label>
                            <div class="col-sm-10">
                                {{ Form::text('role_name', null, ['class' => 'form-control required', 'placeholder' => 'Role Name', 'autocomplete' => 'off']) }}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Permissions <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <label class="checkbox-inline"><input <?php if (in_array('dashboard', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="dashboard">Dashboard</label>
                                <label class="checkbox-inline"><input <?php if (in_array('change-username', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="change-username">Change User Name</label>
                                <label class="checkbox-inline"><input <?php if (in_array('change-password', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="change-password">Change Password</label>
                                <label class="checkbox-inline"><input <?php if (in_array('department', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="department">Configure Department</label>
                                <label class="checkbox-inline"><input <?php if (in_array('add-department', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="add-department">Add Department</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-department', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-department">Edit Department</label>
                                <label class="checkbox-inline"><input <?php if (in_array('transactions-limit', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="transactions-limit">Configure Trans. Limit</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-transaction-limit', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-transaction-limit">Edit Trans. Limit</label>
                                <label class="checkbox-inline"><input <?php if (in_array('customer-transaction-limit', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="customer-transaction-limit">User Transactions
                                    Limit</label>
                                <label class="checkbox-inline"><input <?php if (in_array('merchant-transaction-limit', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="merchant-transaction-limit">Merchant Transactions
                                    Limit</label>
                                <label class="checkbox-inline"><input <?php if (in_array('agent-transaction-limit', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="agent-transaction-limit">Agent Transactions Limit</label>
                                <label class="checkbox-inline"><input <?php if (in_array('users', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="users">Users List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('add-users', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="add-users">Add Users</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-users', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-users">Edit Users</label>
                                <label class="checkbox-inline"><input <?php if (in_array('importuser', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="importuser"> Import User Details</label>
                                <label class="checkbox-inline"><input <?php if (in_array('agents', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="agents">Agent Users List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('add-agents', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="add-agents">Add Agent Users</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-agents', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-agents">Edit Agent Users</label>
                                <label class="checkbox-inline"><input <?php if (in_array('merchants', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="merchants">Merchant Users List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('add-merchants', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="add-merchants">Add Merchant User</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-merchants', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-merchants">Edit Merchant Users</label>
                                <label class="checkbox-inline"><input <?php if (in_array('all', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="all">Total Register Users List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('homeFeatures', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="homeFeatures">Enable/Disable Feature</label>
                                <label class="checkbox-inline"><input <?php if (in_array('loginusers', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="loginusers">Logged In Users Report List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('payclient', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="payclient">Pay Client</label>
                                <label class="checkbox-inline"><input <?php if (in_array('kycdetail', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="kycdetail">View Kyc Details</label>
                                <label class="checkbox-inline"><input <?php if (in_array('transactionHistory', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="transactionHistory">Transaction History</label>

                                <label class="checkbox-inline"><input <?php if (in_array('subadmins', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="subadmins">Sub Admins List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('add-subadmins', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="add-subadmins">Add Sub Admins</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-subadmins', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-subadmins">Edit Sub Admins</label>
                                <!-- <label class="checkbox-inline"><input <?php if (in_array('delete-subadmin', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox" name="permission[]" value="delete-subadmin">Delete Sub Admins</label> -->
                                <label class="checkbox-inline"><input <?php if (in_array('banners', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="banners">Banners List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('add-banners', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="add-banners"> Add Banner</label>

                                <label class="checkbox-inline"><input <?php if (in_array('edit-banners', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-banners">Edit Banner</label>
                                <!-- <label class="checkbox-inline"><input <?php if (in_array('delete-banners', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox" name="permission[]" value="delete-banners">Delete Banner</label> -->
                                <label class="checkbox-inline"><input <?php if (in_array('transactionfees', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="transactionfees">Transaction Fees List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('addConfiguration', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="addConfiguration">Add Fee Configuration</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-transactionfees', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-transactionfees"> Edit Transaction Fee</label>
                                <label class="checkbox-inline"><input <?php if (in_array('transactions', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="transactions">Transactions List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('earning', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="earning">Balance Management List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('pages', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="pages">Pages List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('edit-pages', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="edit-pages">Edit Pages </label>
                                <label class="checkbox-inline"><input <?php if (in_array('gemic-transation', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="gemic-transation">Gimac Transactions List</label>
                                <label class="checkbox-inline"><input <?php if (in_array('remitec-transactions', $permissions)) {
                                    echo 'checked';
                                } ?> type="checkbox"
                                        name="permission[]" value="remitec-transactions">Remitec Transactions List</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="swaptoswap-transation" <?php if (in_array('swaptoswap-transation', $permissions)) {
                                            echo 'checked';
                                        } ?>>Swap To Swap Transactions
                                    List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="amountSlab" <?php if (in_array('amountSlab', $permissions)) {
                                            echo 'checked';
                                        } ?>>Amount Slab List</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="addSlab" <?php if (in_array('addSlab', $permissions)) {
                                            echo 'checked';
                                        } ?>>Add Slab</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="company-list" <?php if (in_array('company-list', $permissions)) {
                                            echo 'checked';
                                        } ?>>Company List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-company" <?php if (in_array('add-company', $permissions)) {
                                            echo 'checked';
                                        } ?>> Add Company</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-company" <?php if (in_array('edit-company', $permissions)) {
                                            echo 'checked';
                                        } ?>> Edit Company</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="reports" <?php if (in_array('reports', $permissions)) {
                                            echo 'checked';
                                        } ?>> Agent Report</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="remove-agent" <?php if (in_array('remove-agent', $permissions)) {
                                            echo 'checked';
                                        } ?>> Remove Agent</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="pay-company" <?php if (in_array('pay-company', $permissions)) {
                                            echo 'checked';
                                        } ?>> Pay Company</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="company-transaction-history" <?php if (in_array('company-transaction-history', $permissions)) {
                                            echo 'checked';
                                        } ?>> Company Transaction History</label>

                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="bulk-payment-merchants" @if (in_array('bulk-payment-merchants', $permissions)) checked @endif>
                                    Bulk Payment Merchant List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="add-bulk-payment-merchants" @if (in_array('add-bulk-payment-merchants', $permissions)) checked @endif>
                                    Add Bulk Payment Merchant
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="onafriq-transactions" @if (in_array('onafriq-transactions', $permissions)) checked @endif>
                                    ONAFRIQ Transactions List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="bda-transactions" @if (in_array('bda-transactions', $permissions)) checked @endif>
                                    BDA Transactions List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="cardcontents" @if (in_array('cardcontents', $permissions)) checked @endif>
                                    Card Content List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="add-card-content" @if (in_array('add-card-content', $permissions)) checked @endif>
                                    Add Card Content
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="edit-card-content" @if (in_array('edit-card-content', $permissions)) checked @endif>
                                    Edit Card Content
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="driver-list" @if (in_array('driver-list', $permissions)) checked @endif>
                                    Driver List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="view-activation-card" @if (in_array('view-activation-card', $permissions)) checked @endif>
                                    View Activation Card
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="add-driver" @if (in_array('add-driver', $permissions)) checked @endif>
                                    Add Driver
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="edit-driver" @if (in_array('edit-driver', $permissions)) checked @endif>
                                    Edit Driver
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="delete-driver" @if (in_array('delete-driver', $permissions)) checked @endif>
                                    Delete Driver
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="help-ticket" @if (in_array('help-ticket', $permissions)) checked @endif>
                                    Help Ticket
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="edit-helpticket" @if (in_array('edit-helpticket', $permissions)) checked @endif>
                                    Update Help Ticket
                                </label>
                                <div id="errorContainer"></div>
                            </div>
                        </div>

                        <div class="box-footer">
                            <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                            {{ Form::submit('Submit', ['class' => 'btn btn-info']) }}
                            {{ Form::reset('Reset', ['class' => 'btn btn-default canlcel_le']) }}
                        </div>
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </section>
    @endsection
