<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
@php
use App\Permission;
@endphp
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <input type="hidden" name="page" value="{{$page}}">
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Companies List</div>
            <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                <div class="topn_righ">
                    Showing {{$allrecords->count()}} of {{ $allrecords->total() }} record(s).
                </div>
                <div class="panel-heading" style="align-items:center;">
                    {{$allrecords->appends(Request::except('_token'))->render()}}
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th style="width:5%">#</th>
                        <th class="sorting_paging">@sortablelink('company_name', 'Company Name')</th>
                        <th class="sorting_paging">@sortablelink('company_code', 'Company Code')</th>
                        <th class="sorting_paging">@sortablelink('username', 'Username')</th>
                        <th class="sorting_paging">@sortablelink('phone', 'phone')</th>
                        <th class="sorting_paging">@sortablelink('email', 'Company Email')</th>
                        <th class="sorting_paging">Company Address</th>
                        <th class="sorting_paging">Website</th>
                        <th class="sorting_paging">Profile</th>
                        <th class="sorting_paging">Wallet Balance</th>
                        @if(Session::get('admin_role')==1)
                        <th class="sorting_paging">@sortablelink('parent_id', 'Created By')</th>
                        @endif
                        <th class="sorting_paging">@sortablelink('edited_by', 'Edited By')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>
                        <td data-title="Full Name">{{$allrecord->company_name}}</td>
                        <td data-title="Email Address">{{$allrecord->company_code ? $allrecord->company_code : 'N/A'}}</td>
                        <td data-title="Email Address">{{$allrecord->username ? $allrecord->username : 'N/A'}}</td>
                        <td data-title="Contact Number">{{$allrecord->phone ? $allrecord->phone : 'N/A'}}</td>
                        <td data-title="Email Address">{{$allrecord->email  ? $allrecord->email:'N/A'}}</td>
                        <td data-title="Email Address">{{$allrecord->company_address  ? $allrecord->company_address : 'N/A'}}</td>
                        <td data-title="Email Address">
                        @if($allrecord->website)
                            <a href="{{ $allrecord->website }}" target="_blank">{{ $allrecord->website }}</a>
                        @else
                            N/A
                        @endif
                        </td>

                        <td data-title="Email Address">
                        @if($allrecord->profile)
                            <img src="{{ HTTP_PATH.'/public/assets/company_logo/'.$allrecord->profile }}" height="50px" width="50px"/>
                        @else
                            N/A
                        @endif
                        </td>
                        
                        <td data-title="Email Address">{{$allrecord->wallet_balance  ? $allrecord->wallet_balance : '0'}}</td>

                        @if(Session::get('admin_role')==1)
                        <td data-title="Email Address">{{ $allrecord->createdBy->username }}</td>
                        @endif
                        <td data-title="Email Address">{{ $allrecord->editedBy->username }}</td>
                        <td data-title="Email Address">{{$allrecord->status==1  ? 'Activated' : 'Deactivated'}}</td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>


                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <!-- <li class="" id="status{{$allrecord->id}}">
                                        @if($allrecord->status == '1')
                                        <a href="{{ URL::to( 'admin/updateCompanyStatus/'.$allrecord->slug.'/0')}}" title="Deactivate" class="deactivate"><i class="fa fa-ban"></i>Deactivate Company</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/updateCompanyStatus/'.$allrecord->slug.'/1')}}" title="Activate" class="activate"><i class="fa fa-check"></i>Activate Company</a>
                                        @endif
                                    </li> -->
                                    @php
                                        $roles = AdminsController::getRoles(Session::get('adminid'));   
                                    @endphp
                                    <?php $permissions = DB::table('permissions')->where('role_id',$roles)->pluck('permission_name')->toArray();?>
                                  
                                    @if(in_array('edit-company',$permissions))
                                    <li><a href="{{ URL::to( 'admin/admins/edit-company/'.$allrecord->slug)}}" title="Edit" class=""><i class="fa fa-pencil"></i>Edit Company</a></li>
                                    @endif

                                    @if(in_array('pay-company',$permissions))
                                    <li><a href="{{ URL::to( 'admin/admins/pay-company/'.$allrecord->slug)}}" title="Pay Company" class=""><i class="fa fa-money"></i>Pay Company</a></li>
                                    @endif

                                    @if(in_array('company-transaction-history',$permissions))
                                    <li><a href="{{ URL::to( 'admin/admins/company-transaction-history/'.$allrecord->slug)}}" title="Transaction History" class=""><i class="fa fa-eye"></i> Transaction History</a></li>
                                    @endif

                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>    
        </div>
    </section>
    {{ Form::close()}}
</div>         
</div> 
@else 
<div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
<div class="admin_no_record">No record found.</div>
@endif

@if(!$allrecords->isEmpty())
@foreach($allrecords as $allrecord)
<div id="info{!! $allrecord->id !!}" style="display: none;">
    <div class="nzwh-wrapper">
        <fieldset class="nzwh">
            <legend class="head_pop">Agent Details</legend>
            <div class="drt">
                <div class="admin_pop"><span>User Type: </span>  <label>@isset($allrecord->user_type) {{$allrecord->user_type}} @endisset</label></div>
                <div class="admin_pop"><span>Full Name: </span>  <label>{!! $allrecord->name !!}</label></div>
                <div class="admin_pop"><span>Wallet Balance: </span>  <label>{!! CURR.' '.$allrecord->wallet_balance !!}</label></div>
                <div class="admin_pop"><span>Email Address: </span>  <label>{{$allrecord->email?$allrecord->email:'N/A'}}</label></div>
                <div class="admin_pop"><span>Phone Number: </span>  <label>{!! $allrecord->phone !!}</label></div>
                <div class="admin_pop"><span>Date Of Birth: </span>  <label>{!! $allrecord->dob !!}</label></div>
                <!-- <div class="admin_pop"><span>City: </span>  <label>{!! $allrecord->City?$allrecord->City->name_en:'N/A' !!}</label></div>
                <div class="admin_pop"><span>Area: </span>  <label>{{$allrecord->Area?$allrecord->Area->name:'N/A'}}</label></div> -->
                <!--<div class="admin_pop"><span>Business registration number: </span>  <label><?php //echo $allrecord->national_identity_number?Crypt::decryptString($allrecord->national_identity_number):'';?></label></div>-->

                @if($allrecord->profile_image != '')
                <div class="admin_pop"><span>Profile Picture</span> <label>{{HTML::image(PROFILE_FULL_DISPLAY_PATH.$allrecord->profile_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</label></div>
                @endif

<!--                @if($allrecord->identity_image != '')
                <div class="admin_pop"><span>Picture national identity</span> <label>{{HTML::image(IDENTITY_FULL_DISPLAY_PATH.$allrecord->identity_image, SITE_TITLE,['style'=>"max-width: 200px"])}}</label></div>
                @endif-->

        </fieldset>
    </div>
</div>
@endforeach
@endif