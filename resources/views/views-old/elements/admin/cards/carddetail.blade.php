
{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
<script type="text/javascript">
    $(document).ready(function ($) {
        $('.close_image').hide();
        $('a[rel*=facebox]').facebox({
            closeImage: '{!! HTTP_PATH !!}/public/img/close.png'
        });

        $('.dropdown-menu a').on('click', function (event) {
            $(this).parent().parent().parent().toggleClass('open');
        });
    });
</script>
<div class="admin_loader" id="loaderID">{{HTML::image("img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Cards List</div>
                         
        </div>
        <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                <div class="panel-heading" style="align-items:center;">
                    {{$allrecords->appends(Request::except('_token'))->render()}}
                </div>
            </div>   
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th style="width:5%">#</th>
                        <th class="sorting_paging">@sortablelink('serial_number', 'Serial Code')</th>
                        <th class="sorting_paging">@sortablelink('pin_number', 'Pin Number')</th>
                        <th class="sorting_paging">@sortablelink('real_value', 'Card Value')</th>
                        <th class="sorting_paging">@sortablelink('card_value', 'Card Cost')</th>
                        <th class="sorting_paging">@sortablelink('agent_card_value', 'Card Cost For Agent')</th>
                        <th class="sorting_paging">@sortablelink('status', 'Status')</th>
                        <th class="sorting_paging">@sortablelink('used_status', 'Used/Unused')</th>
                        <th class="sorting_paging">@sortablelink('Admin.username', 'Last Updated By')</th>
                        <th class="sorting_paging">@sortablelink('created_at', 'Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th>
                        <td data-title="Serial Code">{{$allrecord->serial_number}}</td>
                        <td data-title="Pin Number">{{$allrecord->pin_number}}</td>
                        <td data-title="Card Value">{{$allrecord->currency}} {{$allrecord->real_value}}</td>
                        <td data-title="Card Cost">{{CURR}} {{$allrecord->card_value}}</td>
                        <td data-title="Card Cost For Agent">{{CURR}} {{$allrecord->agent_card_value}}</td>
                        
                        <td data-title="Status" id="status_{{$allrecord->id}}">
                            @if($allrecord->status == 1)
                            Activated
                            @else
                            Deactivated
                            @endif
                        </td>
                        <td data-title="Used/Unused Status">
                            @if($allrecord->used_status == 1)
                            Used
                            @else
                            Unused
                            @endif
                        </td>
                        <td data-title="Last Updated By">
                            {{$allrecord->Admin->username}} - {{$allrecord->Admin->id != 1?'Subadmin':'Admin'}}
                        </td>
                        <td data-title="Date">{{$allrecord->created_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>                            

                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li class="right_acdc" id="status{{$allrecord->id}}">
                                        @if($allrecord->status == '1')
                                        <a href="{{ URL::to( 'admin/cards/deactivatecarddetail/'.$allrecord->id)}}" title="Deactivate" class="deactivate"><i class="fa fa-check"></i>Deactivate Card</a>
                                        @else
                                        <a href="{{ URL::to( 'admin/cards/activatecarddetail/'.$allrecord->id)}}" title="Activate" class="activate"><i class="fa fa-ban"></i>Activate Card</a>
                                        @endif
                                    </li>
                                    <li><a href="{{ URL::to( 'admin/cards/editcarddetail/'.$cslug.'/'.$allrecord->id)}}" title="Edit" class=""><i class="fa fa-pencil"></i>Edit Card</a></li>
                                    <li><a href="{{ URL::to( 'admin/cards/deletecarddetail/'.$cslug.'/'.$allrecord->id)}}" title="Delete" class="" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fa fa-trash-o"></i>Delete</a></li>

                                    <li><a href="#info{!! $allrecord->id !!}" title="View Card Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View Card Detail</a></li>
                                    <!--<li><a href="{{ URL::to( 'admin/cards/cardoffers/'.$cslug.'/'.$allrecord->id)}}" title="Special Offer List For Agent" class=""><i class="fa fa-list"></i>Special Offer List</a></li>-->
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="search_frm">
                <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
                <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
                <?php
                $accountStatus = array(
                    'Activate' => "Activate Card",
                    'Deactivate' => "Deactivate Card",
                    'Delete' => "Delete",
                );
                ;
                ?>
                <div class="list_sel">{{Form::select('action', $accountStatus,null, ['class' => 'small form-control','placeholder' => 'Action for selected record', 'id' => 'action'])}}</div>
                <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
            </div>    
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
            <legend class="head_pop">{!! $allrecord->serial_number !!}</legend>
            <div class="drt">
                <div class="admin_pop"><span>Serial Code: </span>  <label>{!! $allrecord->serial_number !!}</label></div>
                <div class="admin_pop"><span>Pin Number: </span>  <label>{!! $allrecord->pin_number !!}</label></div>
                <div class="admin_pop"><span>Card Value: </span>  <label>{!! $allrecord->currency.' '.$allrecord->real_value !!}</label></div>
                <div class="admin_pop"><span>Card Cost: </span>  <label>{!! CURR.' '.$allrecord->card_value !!}</label></div>
                <div class="admin_pop"><span>Card Cost For Agent: </span>  <label>{!! CURR.' '.$allrecord->agent_card_value !!}</label></div>
                <div class="admin_pop"><span>Instruction: </span>  <label>{!! $allrecord->instruction !!}</label></div>


        </fieldset>
    </div>
</div>
@endforeach
@endif