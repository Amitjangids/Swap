<?php use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
?>
{{ HTML::script('public/assets/js/facebox.js') }}
{{ HTML::style('public/assets/css/facebox.css') }}
@php
    use App\Http\Controllers\Admin\AdminsController;
    use App\Http\Controllers\Admin\UsersController;
@endphp
@php
    use App\Permission;
@endphp
<script type="text/javascript">
    $(document).ready(function($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
        });

        $('.dropdown-menu a').on('click', function(event) {
            $(this).parent().parent().parent().toggleClass('open');
        });
    });
</script>
<div class="admin_loader" id="loaderID">{{ HTML::image('public/img/website_load.svg', '') }}</div>
@if (!$allrecords->isEmpty())
    <div class="panel-body marginzero">
        <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
        {{ Form::open(['method' => 'post', 'id' => 'actionFrom']) }}
        <input type="hidden" name="page" value="{{ $page }}">
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="manage_sec">
                    <div class="topn_left">Total Register Users List</div>
                    <div class="topn_rightd ddpagingshorting paggng-txt" id="pagingLinks" align="right">
                        <div class="topn_righ">
                            Showing {{ $allrecords->count() }} of {{ $allrecords->total() }} record(s).
                        </div>
                        <div class="panel-heading" style="align-items:center;">
                            {{ $allrecords->appends(Request::except('_token'))->render() }}
                        </div>
                    </div>
                </div>
                <!-- <div class="transaction_info">
                <div class="topn_left_btsec">
                    <div class="payment_info">
                        <span class="pay_head">Total Wallet Balance</span>
                        <span class="pay_body">₣ {{ number_format($total['wallet_balance'], 2) }}</span>
                    </div>
                </div>
            </div> -->
            </div>
            <div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ddpagingshorting">
                        <tr>
                            <th style="width:5%">#</th>
                            <th class="sorting_paging">@sortablelink('user_type', 'User Type')</th>
                            <th class="sorting_paging">@sortablelink('name', 'Name')</th>
                            <th class="sorting_paging">@sortablelink('is_verify', 'Merchant Name')</th>
                            <th class="sorting_paging">@sortablelink('isBulkUser', 'Merchant Type')</th>
                            <th class="sorting_paging">@sortablelink('email', 'Email Address')</th>
                            <th class="sorting_paging">@sortablelink('phone', 'Phone')</th>

                            <th class="sorting_paging">@sortablelink('is_verify', 'Status')</th>

                            <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                            <th class="action_dvv"> Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($allrecords as $allrecord)
                            <tr>
                                <th style="width:5%">
                                    <input type="checkbox" onclick="javascript:isAllSelect(this.form);"
                                        name="chkRecordId[]" value="{{ $allrecord->id }}" />
                                </th>
                                <td data-title="User Type">{{ $allrecord->user_type }}</td>
                                <td data-title="Full Name">{{ $allrecord->name }}</td>
                                <td data-title="Full Name">
                                    <?php
                                    $merchantName = UsersController::getMerchantName($allrecord->parent_id);
                                    echo $merchantName->name;
                                    ?>
                                </td>
                                <td data-title="User Type" id="user_type-{{ $allrecord->slug }}">
                                    {{ ($merchantName->isBulkUser) == 1 ? 'Bulk' : 'Swap' }} Merchant
                                </td>
                                <td data-title="Email Address">{{ $allrecord->email ? $allrecord->email : 'N/A' }}</td>
                                <td data-title="Contact Number">{{ $allrecord->phone }}</td>

                                <td data-title="Status" id="verify_{{ $allrecord->slug }}">
                                    @if ($allrecord->is_verify == 1)
                                        Activated
                                    @else
                                        Deactivated
                                    @endif
                                </td>
                                <td data-title="Date">{{ $allrecord->created_at->format('M d, Y h:i A') }}</td>
                                <td data-title="Action">
                                    <div id="loderstatus{{ $allrecord->id }}" class="right_action_lo">
                                        {{ HTML::image('public/img/loading.gif', '') }}</div>


                                    <div class="btn-group">
                                        <button class="btn btn-primary dropdown-toggle" type="button"
                                            data-toggle="dropdown">
                                            <i class="fa fa-list"></i>
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu pull-right">
                                            <li class="right_acdc" id="status{{ $allrecord->id }}">
                                                @if ($allrecord->is_verify == '1')
                                                    <a href="{{ URL::to('admin/users/deactivate/' . $allrecord->slug) }}"
                                                        title="Deactivate" class="deactivate"><i
                                                            class="fa fa-check"></i>Deactivate</a>
                                                @else
                                                    <a href="{{ URL::to('admin/users/activate/' . $allrecord->slug) }}"
                                                        title="Activate" class="activate"><i
                                                            class="fa fa-ban"></i>Activate</a>
                                                @endif
                                            </li>
                                            @php
                                                $roles = AdminsController::getRoles(Session::get('adminid'));
                                            @endphp


                                            <?php $permissions = DB::table('permissions')->where('role_id', $roles)->pluck('permission_name')->toArray(); ?>

                                            @if (in_array('all', $permissions))
                                                <li><a href="{{ URL::to('admin/users/edit-all/' . $allrecord->slug) }}"
                                                        title="Edit" class=""><i class="fa fa-pencil"></i>Edit
                                                        User</a></li>
                                                <!--  <li><a href="{{ URL::to('admin/users/delete/' . $allrecord->slug) }}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li> -->
                                            @endif
                                            <li>
                                                <a href="#info{!! $allrecord->id !!}" title="View User Detail"
                                                    class="" rel='facebox'><i class="fa fa-eye"></i>
                                                    View User Detail
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="search_frm">
                    <button type="button" name="chkRecordId" onclick="checkAll(true);" class="btn btn-info">Select
                        All</button>
                    <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect
                        All</button>
                    <?php
                    $accountStatus = [
                        'Activate' => 'Activate User',
                        'Deactivate' => 'Deactivate User',
                        // 'Delete' => "Delete",
                    ];
                    ?>
                    <div class="list_sel">
                        {{ Form::select('action', $accountStatus, null, ['class' => 'small form-control', 'placeholder' => 'Action for selected record', 'id' => 'action']) }}
                    </div>
                    <button type="submit" class="small btn btn-success btn-cons btn-info"
                        onclick="return ajaxActionFunction();" id="submit_action">OK</button>
                </div>
            </div>
        </section>
        {{ Form::close() }}
    </div>
    </div>
@else
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
@endif

@if (!$allrecords->isEmpty())
    @foreach ($allrecords as $allrecord)
        <div id="info{!! $allrecord->id !!}" style="display: none;">
            <div class="nzwh-wrapper">
                <fieldset class="nzwh">
                    <legend class="head_pop">{!! $allrecord->name !!}</legend>
                    <div class="drt">
                        <div class="admin_pop"><span>User Type: </span> <label>
                                @isset($allrecord->user_type)
                                    {{ $allrecord->user_type }}
                                @endisset
                            </label></div>
                        <div class="admin_pop"><span>Full Name: </span> <label>{!! $allrecord->name !!}</label></div>

                        <div class="admin_pop"><span>Email Address: </span>
                            <label>{{ $allrecord->email ? $allrecord->email : 'N/A' }}</label>
                        </div>
                        <div class="admin_pop"><span>Phone Number: </span> <label>{!! $allrecord->phone !!}</label></div>

                        <!-- <div class="admin_pop"><span>City: </span>  <label>{!! $allrecord->City ? $allrecord->City->name_en : 'N/A' !!}</label></div>
                <div class="admin_pop"><span>Area: </span>  <label>{{ $allrecord->Area ? $allrecord->Area->name : 'N/A' }}</label></div> -->


                    </div>
                </fieldset>
            </div>
        </div>
    @endforeach
@endif
