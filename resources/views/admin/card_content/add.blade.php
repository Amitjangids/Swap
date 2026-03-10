@extends('layouts.admin')
@section('content')
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            $.validator.addMethod("alphanumeric", function (value, element) {
                return this.optional(element) || /^[\w.]+$/i.test(value);
            }, "Only letters, numbers and underscore allowed.");
            $.validator.addMethod("passworreq", function (input) {
                var reg = /[0-9]/; //at least one number
                var reg2 = /[a-z]/; //at least one small character
                var reg3 = /[A-Z]/; //at least one capital character
                //var reg4 = /[\W_]/; //at least one special character
                return reg.test(input) && reg2.test(input) && reg3.test(input);
            }, "Password must be a combination of Numbers, Uppercase & Lowercase Letters.");

            $("#adminForm").validate();

        });
    </script>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>Add Banner</h1>
            <ol class="breadcrumb">
                <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="{{URL::to('admin/cardcontents')}}"><i class="fa fa-picture-o"></i> <span>Manage Card
                            Content</span></a></li>
                <li class="active"> Add Card Content</li>
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
                            <label class="col-sm-2 control-label">Title <span class="require">*</span></label>
                            <div class="col-sm-10">
                                {{Form::text('title', null, ['class' => 'form-control required', 'placeholder' => 'Title', 'autocomplete' => 'off'])}}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Description <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <div id="listInputs">
                                        <input name="description[]" type="text" class="form-control" placeholder="Enter item">
                                        <button type="button" class="btn btn-danger removeBtn">Remove</button>
                                </div>
                                <button type="button" class="btn btn-primary mt-2" onclick="addMore()">Add More</button>
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

    </div>
    <script>
        function addMore() {
            const html = `
                <input name="description[]" type="text" class="form-control" placeholder="Enter item">
                <button type="button" class="btn btn-danger removeBtn">Remove</button>`;
            document.getElementById('listInputs').insertAdjacentHTML('beforeend', html);
        }

        // Remove input when clicking the Remove button
        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('removeBtn')) {
                e.target.closest('.input-group').remove();
            }
        });
    </script>
@endsection