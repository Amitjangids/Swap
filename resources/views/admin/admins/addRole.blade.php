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
            <h1>Add Department</h1>
            <ol class="breadcrumb">
                <li><a href="{{ URL::to('admin/admins/dashboard') }}"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="{{ URL::to('admin/admins/roles') }}"><i class="fa fa-user"></i> <span>Manage
                            Department</span></a></li>
                <li class="active"> Add Department</li>
            </ol>
        </section>
        <section class="content">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">&nbsp;</h3>
                </div>
                <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
                {{ Form::open(['method' => 'post', 'id' => 'adminForm', 'enctype' => 'multipart/form-data']) }}
                <div class="form-horizontal">
                    <div class="box-body">

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Department Name <span class="require">*</span></label>
                            <div class="col-sm-10">
                                {{ Form::text('role_name', null, ['class' => 'form-control required', 'placeholder' => 'Department Name', 'autocomplete' => 'off']) }}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Permissions <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="dashboard">Dashboard</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="change-username">Change User Name</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="change-password">Change Password</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="department">Configure Department</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-department">Add Department</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-department">Edit Department</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="transactions-limit">Configure Trans. Limit</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-transaction-limit">Edit Trans. Limit</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="customer-transaction-limit">User Transactions Limit</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="merchant-transaction-limit">Merchant Transactions Limit</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="agent-transaction-limit">Agent Transactions Limit</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="users">Users List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-users">Add Users</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-users">Edit Users</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="importuser"> Import User Details</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="agents">Agent Users List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-agents">Add Agent Users</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-agents">Edit Agent Users</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="merchants">Merchant Users List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-merchants">Add Merchant User</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-merchants">Edit Merchant Users</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="all">Total Register Users List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="homeFeatures">Enable/Disable Feature</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="loginusers">Logged In Users Report List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="payclient">Pay Client</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="kycdetail">View Kyc Details</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="transactionHistory">Transaction History</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="subadmins">Sub Admins List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-subadmins">Add Sub Admins</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-subadmins">Edit Sub Admins</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="banners">Banners List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-banners"> Add Banner</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-banners">Edit Banner</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="transactionfees">Transaction Fees List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="addConfiguration">Add Fee Configuration</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-transactionfees"> Edit Transaction Fee</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="transactions">Transactions List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="earning">Balance Management List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="pages">Pages List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-pages"> Edit Pages</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="gemic-transation">Gimac Transactions List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="remitec-transactions">Remitec Transactions List</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="swaptoswap-transation">Swap To Swap Transactions List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="amountSlab">Amount Slab List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="addSlab">Add Slab</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="company-list">Company List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-company"> Add Company</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="edit-company"> Edit Company</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="reports"> Agent Report</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="remove-agent"> Remove Agent</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="pay-company"> Pay Company</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="company-transaction-history"> Company Transaction History</label>

                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="bulk-payment-merchants">Bulk Payment Merchant List</label>
                                <label class="checkbox-inline"><input type="checkbox" name="permission[]"
                                        value="add-bulk-payment-merchants">Add Bulk Payment Merchant</label>

                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="onafriq-transactions">
                                    ONAFRIQ Transactions List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="bda-transactions">
                                    BDA Transactions List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="cardcontents">
                                    Card Content List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="add-card-content">
                                    Add Card Content
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="edit-card-content">
                                    Edit Card Content
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="driver-list">
                                    Driver List
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="view-activation-card">
                                    View Activation Card
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="add-driver">
                                    Add Driver
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="edit-driver">
                                    Edit Driver
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="delete-driver">
                                    Delete Driver
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="help-ticket">
                                    Help Ticket
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="permission[]" value="edit-helpticket">
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
