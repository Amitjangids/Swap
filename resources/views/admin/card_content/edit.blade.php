@extends('layouts.admin')
@section('content')
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
            <h1>Edit Card Content</h1>
            <ol class="breadcrumb">
                <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="{{URL::to('admin/banners')}}"><i class="fa fa-picture-o"></i> <span>Manage Card
                            Content</span></a></li>
                <li class="active"> Edit Card Content</li>
            </ol>
        </section>
        <section class="content">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">&nbsp;</h3>
                </div>
                <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
                {{Form::model($recordInfo, ['method' => 'post', 'id' => 'adminForm', 'enctype' => "multipart/form-data"]) }}
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
                                    @foreach ($recordInfo->description as $index => $item)
                                    <div class="row removeDes">
                                        <div class="col-sm-11">
                                            <input name="description[]" type="text" class="form-control" value="{{ $item }}"
                                            placeholder="Enter content">
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn btn-danger removeBtn"><i class="fa fa-minus"></i></button>
                                        </div>
                                    </div></br>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-success mt-2" onclick="addMore()" style="background:green"><i class="fa fa-plus"></i></button>

                            </div>
                        </div>

                        <div class="box-footer">
                            <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                            {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                            <a href="{{ URL::to('admin/cardcontents')}}" title="Cancel"
                                class="btn btn-default canlcel_le">Cancel</a>
                        </div>
                    </div>
                </div>
                {{ Form::close()}}
            </div>
        </section>
    </div>
@endsection
<script>
function addMore() {
    const html = `<div class="row removeDes">
            <div class="col-sm-11">
                <input name="description[]" type="text" class="form-control" placeholder="Enter content">
            </div>
            <div class="col-sm-1">
            <button type="button" class="btn btn-danger removeBtn"><i class="fa fa-minus"></i></button>
            </div></div></br>`;
    document.getElementById('listInputs').insertAdjacentHTML('beforeend', html);
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('removeBtn')) {
        e.target.closest('.removeDes').remove();
    }
});
</script>