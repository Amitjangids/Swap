@extends('layouts.admin')
@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>View Help Ticket</h1>
            <ol class="breadcrumb">
                <li><a href="{{URL::to('admin/admins/dashboard')}}"><i class="fa fa-dashboard"></i>
                        <span>Dashboard</span></a></li>
                <li><a href="{{URL::to('admin/help-ticket')}}"><i class="fa fa-user-secret"></i> <span>Manage Help
                            Ticket</span></a></li>
                <li class="active"> View Help Ticket</li>
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
                            <label class="col-sm-2 control-label">Username<span class="require"></span></label>
                            <div class="col-sm-10">
                                {{Form::text('name', $recordInfo->User->name ?? '', ['class' => 'form-control', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])}}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Category<span class="require"></span></label>
                            <div class="col-sm-10">
                                {{Form::text('categoryId', $recordInfo->HelpCat->name ?? '', ['class' => 'form-control', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])}}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Ticket ID<span class="require"></span></label>
                            <div class="col-sm-10">
                                {{Form::text('ticketId', null, ['class' => 'form-control', 'placeholder' => 'TicketID', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])}}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Description<span class="require"></span></label>
                            <div class="col-sm-10">
                                {{Form::text('description', null, ['class' => 'form-control', 'autocomplete' => 'off',$recordInfo->status == 'Resolved' ? 'disabled' : null])}}
                            </div>
                        </div>



                        <div class="form-group">
                            <label class="col-sm-2 control-label">Status<span class="require"></span></label>
                            <div class="col-sm-10">
                                <select name="status" class="form-control" @if (($recordInfo->status == 'Resolved')) disabled
                                @endif>
                                    <option value="Pending" <?php echo isset($recordInfo->status) && $recordInfo->status == "Pending" ? "selected" : ""; ?>>Pending</option>
                                    <option value="Resolved" <?php echo isset($recordInfo->status) && $recordInfo->status == "Resolved" ? "selected" : ""; ?>>Resolved</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Comment<span class="require"></span></label>
                            <div class="col-sm-10">
                                {{ Form::textarea('comment', null, [
        'class' => 'form-control',
        'autocomplete' => 'off',
        $recordInfo->status == 'Resolved' ? 'disabled' : null
    ]) }}

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Image<span class="require"></span></label>
                            <div class="col-sm-10">
                                @if (!empty($recordInfo->imagePath))
                                    @php
                                        $filePath = asset('public/uploads/help_tickets/' . $recordInfo->imagePath);
                                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                    @endphp

                                    @if (in_array($extension, ['jpg', 'jpeg', 'png']))
                                        <img src="{{ $filePath }}" alt="Uploaded Image" style="max-height: 150px;">
                                    @elseif (in_array($extension, ['pdf', 'doc', 'docx']))
                                        <a href="{{ $filePath }}" target="_blank" class="btn btn-info">
                                            View Document
                                        </a>
                                    @else
                                        <p>No preview available</p>
                                    @endif
                                @else
                                    <p>No file uploaded</p>
                                @endif
                            </div>
                        </div>
                        @if($recordInfo->status != 'Resolved')
                            <div class="box-footer">
                                <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                                {{Form::submit('Submit', ['class' => 'btn btn-info'])}}
                                <a href="{{ URL::to('admin/help-ticket')}}" title="Cancel"
                                    class="btn btn-default canlcel_le">Cancel</a>
                            </div>
                        @endif
                    </div>
                </div>
                {{ Form::close()}}
            </div>
        </section>
@endsection