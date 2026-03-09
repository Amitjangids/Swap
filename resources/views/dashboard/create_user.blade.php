@extends('layouts.home')
@section('content')
<section class="banner-section password-section">
       <div class="container">
            <div class="heading-parent">
               <h2><a href="{{HTTP_PATH}}/dashboard"><img src="{{PUBLIC_PATH}}/assets/front/images/back-icon.svg" alt="image"></a>{{__('message.Create Users')}}</h2>
           </div>
           {{ Form::open(array('method' => 'post', 'id' => 'createUserForm', 'class' => 'form form-signin')) }}
           <div class="user-password-wrapper">
               <div class="col-lg-6">
                   <div class="login-from-parent">
                        <div class="input-box-parent from-group">
                            <label>{{__('message.Select Role')}}</label>
                            <div class="custom-option-field">
                                <select class="form-control required" name="role">
                                    <option value="Submitter">{{__('message.Submitter')}}</option>
                                    <option value="Approver">{{__('message.Approver')}}</option>
                                </select>
                                <span><img src="{{PUBLIC_PATH}}/assets/front/images/select-arrow.png" alt="image"></span>
                            </div>
                        </div>

                        <div class="input-box-parent from-group">
                            <label>{{__('message.Name')}}:</label>
                            <input type="text" class="form-control required" name="name" placeholder="" autocomplete="off" maxlength="15" oninput="validateName(this)">
                            <div class="error">{{ $errors->first('name') }}</div>
                        </div>

                        <div class="input-box-parent from-group">
                            <label>{{__('message.Email Address')}}:</label>
                            <input type="email" class="form-control required" name="email" placeholder="" autocomplete="off">
                            <div class="error">{{ $errors->first('email') }}</div>
                        </div>


                        <div class="input-box-parent from-group">
                        <label>{{__('message.Phone number')}}:</label>
                        <div class="login-contact form-control newMobcss">
                            <div class="country-box">
                                <img src="{{PUBLIC_PATH}}/assets/front/images/country-flag.png" alt="image">
                                <span>+241</span>   
                            </div>
                            <div class="input-box-parent newMobcssinput">
                                <input type="tel" class="required" id="phoneInput" name="phone"  pattern="[0-9]*" minlength="9">
                            </div>
                        </div>
                    </div>

                        <div class="login-btn">
                            <button type="submit" class="btn btn-primaryx">{{__('message.INITIATE')}}</button>
                        </div>
                    </div>
               </div>
           </div>
           {{ Form::close()}}
       </div>
   </section>  


   <section class="same-section table-history pending-table">
       <div class="container">
           <div class="transition-history-wrapper">
               <h2>{{__('message.Approvals')}}</h2>
               <div class="table-responsive">
               <table id='exampleTable' class="table table-dark table-striped" width="100%">
               <thead>
                        <tr>
                           <th>{{__('message.User name')}}</th>
                           <th>{{__('message.Phone Number')}}</th>
                           <th>{{__('message.Email address')}}</th>
                           <th>{{__('message.Role')}}</th>
                           <th>{{__('message.Status')}}</th>
                           <th>{{__('message.Action')}}</th>
                       </tr>
                   </thead>
                </table>
               </div>
           </div>
       </div>
   </section>


   <!-- Modal -->
<div class="modal fade" id="delete_view" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{{__('message.Remove')}}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>{{__('message.Are you sure you want remove this user?')}}</p>
      </div>
      <div class="modal-footer">
        <div class="logout-btn">
            <a href="javascript:void(0)" class="btn btn-primaryx default" type="button" data-bs-dismiss="modal">{{__('message.Cancel')}}</a> 
            <a href="javascript:void(0)" class="btn btn-primaryx delete_user">{{__('message.Remove')}}</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
      // DataTable
	  var oTable = $('#exampleTable').DataTable({
         processing: false,
		 bFilter:false,
		 searching: false, 
         serverSide: true,
         lengthChange: false,
        //pageLength: 1,
		// order: [[5, 'desc']],
         ajax: "{{HTTP_PATH}}/get-user-list",
         columns: [
            { data: 'name'},
            { data: 'phone' },
            { data: 'email' },
            { data: 'user_type'},
            { data: 'status'},
            { data: 'action'},
         ],
		columnDefs: [ {
		targets: [5], // column index (start from 0)
        orderable: false, // set orderable false for selected columns
        }],
        language: {
                paginate: {
                    previous: "{{ __('message.previous') }}",
                    next: "{{ __('message.next') }}"
                },
            
                info: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}",
                infoEmpty: "{{ __('message.showing') }} _START_ {{ __('message.to') }} _END_ {{ __('message.of') }} _TOTAL_ {{ __('message.entries') }}", // Custom text for empty tables
                // lengthMenu: "{{ __('message.show') }} _MENU_ {{ __('message.entries') }}", // "Show X entries" text
                // search: "{{ __('message.search') }}", // Label for the search box
                zeroRecords: "{{ __('message.No data available in table') }}" // Custom text for no matching records
            },
      });
    });

    function delete_user(slug)
    {
	    $('.delete_user').attr('href','{{HTTP_PATH}}/delete-user/'+slug);
    }

</script>

<script>
    function validateName(input) {
        // Define the allowed pattern (alphanumeric characters and spaces)
        var pattern = /^[a-zA-Z\s]*$/;
        
        // Test the input value against the pattern
        if (!pattern.test(input.value)) {
            // If invalid, remove the last entered character
            input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
        }
    }
</script>


<script>
document.getElementById('phoneInput').addEventListener('input', function (e) {
    this.value = this.value.replace(/\D/g, ''); // Remove non-numeric characters
});
</script>

<script>
    document.getElementById('phoneInput').addEventListener('keypress', function (event) {
        // Allow only numeric characters (0-9)
        if (event.which < 48 || event.which > 57) {
            event.preventDefault();
        }
    });
</script>
   
<script type="text/javascript">
    $(document).ready(function () {
    $("#createUserForm").validate({
        rules: {
            "name": "required",
            "email":{
                required: true,
                email: true
            },
            "phone": {
                    required: true,
                    digits: true
            }
        },
        messages: {
            "name": "{{__('message.Enter a name')}}",
            "email": {
                required: "{{__('message.Enter an email address')}}",
                email: "{{__('message.Enter a valid email address')}}"
            },
            "phone": {
                    required: "{{__('message.Enter a phone number')}}",
                    digits: "{{__('message.Please enter only numeric values for the phone number')}}",
                    minlength: "{{__('message.Please enter at least 9 number.')}}",
            }
        },
    });
    });
</script>
@endsection