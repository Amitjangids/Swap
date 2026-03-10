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
<div class="admin_loader" id="loaderID">{{HTML::image("public/img/website_load.svg", '')}}</div>
@if(!$allrecords->isEmpty())
<div class="panel-body marginzero">
    <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div>
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="manage_sec">
                <div class="topn_left">Used/Purchased Scratch Cards List</div>
                <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                    <div class="panel-heading" style="align-items:center;">
                        {{$allrecords->appends(Request::except('_token'))->render()}}
                    </div>
                </div>   
            </div>   

            <div class="transaction_info">
                <div class="topn_left_btsec">
                    <div class="payment_info">
                        <span class="pay_head">Total Used Card Value</span>
                        <span class="pay_body">{{CURR}} {{number_format($total['unused_value'],2)}}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <!-- <th style="width:5%">#</th> -->
                        <th class="sorting_paging">@sortablelink('card_number', 'Card Number')</th>
                        <th class="sorting_paging">@sortablelink('real_value', 'Card Value')</th>
                        <th class="sorting_paging">@sortablelink('User.name', 'Used By')</th>
                        <th class="sorting_paging">@sortablelink('Purchased.name', 'Purchased By Agent')</th>
                        <th class="sorting_paging">@sortablelink('updated_at', 'Used Date')</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allrecords as $allrecord)
                    <tr>
                        <!-- <th style="width:5%"><input type="checkbox" onclick="javascript:isAllSelect(this.form);" name="chkRecordId[]" value="{{$allrecord->id}}" /></th> -->
                        <td data-title="Card Number">{{$allrecord->card_number}}</td>
                        <td data-title="Card Value">{{CURR}} {{$allrecord->real_value}}</td>
                        <td data-title="Used By">@if(isset($allrecord->User->name)){{$allrecord->User->name}} ({{$allrecord->User->phone}})@else {{'N/A'}} @endif</td>
                        <td data-title="Purchased By Agent">@if(isset($allrecord->Purchased->name)){{$allrecord->Purchased->name}} ({{$allrecord->Purchased->phone}})@else {{'N/A'}} @endif</td>

                        <td data-title="Date">{{$allrecord->updated_at->format('M d, Y h:i A')}}</td>
                        <td data-title="Action">
                            <div id="loderstatus{{$allrecord->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>                            

                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">


                                    <li><a href="#info{!! $allrecord->id !!}" title="View Card Detail" class="" rel='facebox'><i class="fa fa-eye"></i>View Card Detail</a></li>
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
            <legend class="head_pop">{!! $allrecord->card_number !!}</legend>
            <div class="drt">
                <div class="admin_pop"><span>Card Number: </span>  <label>{!! $allrecord->card_number !!}</label></div>
                <div class="admin_pop"><span>Card Value: </span>  <label>{{CURR}} {!! $allrecord->card_value !!}</label></div>
                <div class="admin_pop"><span>Used By Username: </span>  <label>{{isset($allrecord->User->name)?$allrecord->User->name:'N/A'}}</label></div>
                <div class="admin_pop"><span>User Phone: </span>  <label>{{isset($allrecord->User->name)?$allrecord->User->phone:'N/A'}}</label></div>
                <div class="admin_pop"><span>Purchased By User: </span>  <label>{{isset($allrecord->Purchased->name)?$allrecord->Purchased->name:'N/A'}}</label></div>
        </fieldset>
    </div>
</div>
@endforeach
@endif