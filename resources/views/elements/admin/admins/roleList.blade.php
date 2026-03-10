{{ HTML::script('public/assets/js/facebox.js')}}
{{ HTML::style('public/assets/css/facebox.css')}}
@php
use App\Http\Controllers\Admin\AdminsController;
@endphp
@php
use App\Permission;
@endphp
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
@if(!$roles->isEmpty())
<div class="panel-body marginzero">
   <!-- <div class="ersu_message">@include('elements.admin.errorSuccessMessage')</div> -->
    {{ Form::open(array('method' => 'post', 'id' => 'actionFrom')) }}
    <section id="no-more-tables" class="lstng-section">
        <div class="topn">
            <div class="topn_left">Department List</div>
            <div class="topn_rightd ddpagingshorting" id="pagingLinks" align="right">
                <div class="panel-heading" style="align-items:center;">
                   
                </div>
            </div>                
        </div>
        <div class="tbl-resp-listing">
            <table class="table table-bordered table-striped table-condensed cf">
                <thead class="cf ddpagingshorting">
                    <tr>
                        <th style="width:5%">Sno</th>
                        <th class="sorting_paging">Department name</th>
                        <th class="sorting_paging">Permission</th>
                        <th class="sorting_paging">Date</th>
                        <th class="action_dvv"> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $key=>$role)
					@php
					$permissions = getPermissionByRoleId($role->id);
					@endphp

                 
                    <tr>
                        <th style="width:3%">{{$key+1}}</th>
                        <td style="width:20%" data-title="Department Name">{{$role->role_name}}</td>
                        <td style="width:55%" data-title="Permission">
                            @php
                            $formattedPermissions = implode(', ', array_map(function($permission) {
                                return ucwords(trim($permission));
                            }, explode(',', str_replace('-', ' ', $permissions))));
                            echo $formattedPermissions;
                            @endphp
                        </td>
                        <td style="width:17%" data-title="Date">
						@php
						 $date = date_create($role->created_at);
						 $createdAt = date_format($date,'M d, Y h:i A');
						@endphp
						{{$createdAt}}
						</td>
                        <td style="width:5%" data-title="Action">
                            <div id="loderstatus{{$role->id}}" class="right_action_lo">{{HTML::image("public/img/loading.gif", '')}}</div>
                            
                            @php
                                $roles1 = AdminsController::getRoles(Session::get('adminid'));   
                            @endphp
                        
                    
                            <?php $permissions = DB::table('permissions')->where('role_id',$roles1)->pluck('permission_name')->toArray();?>
                            @if(in_array('edit-department',$permissions))
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-list"></i>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li><a href="{{ URL::to( 'admin/admins/edit-department/'.$role->id)}}" title="Edit Role" class=""><i class="fa fa-pencil"></i>Edit Department</a></li>
                                </ul>
                            </div>
                            @endif
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