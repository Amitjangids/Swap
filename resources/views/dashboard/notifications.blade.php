@extends('layouts.home')
@section('content')
<section class="notification-section">
       <div class="container">
           <div class="heading-parent">
               <h2><a href="{{HTTP_PATH}}"><img src="{{PUBLIC_PATH}}/assets/front/images/back-icon.svg" alt="image"></a>{{__('message.Notification')}}</h2>
           </div>
           <div class="message-box-wrapper" id="paginated-data-container">
              
           </div>
       </div>
   </section>

<script type="text/javascript">

let page = 1;
function fetchData() {
    $.ajax({
                url: "{{ url('/get-notification-list') }}?page="+page,
                type: "GET",
                success: function (response) {
                appendDataToContainer(response.data);
                page++;
                },
    });
}

function appendDataToContainer(data) {
    const container = document.getElementById('paginated-data-container');
    // Append your data to the container (you might need to adjust this based on your data structure)
    const lang = "{{ $lang }}";
    const htmlString = data.map(item => {
    // Convert the created_at string to a Date object
    const createdAtDate = new Date(item.created_at);

    // Format date and time components
    const timeOptions = {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    };

    const dateOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    };

    // Format date and time
    const formattedTime = createdAtDate.toLocaleString('en-US', timeOptions);
    const formattedDate = createdAtDate.toLocaleString('en-US', dateOptions);

    // Create the HTML string
    return `
        <div class="message-parent">
          
            <div class="message-content-parent">
                <p>${lang === 'fr' ? item.notif_body_fr : item.notif_body}</p>
                <div class="msg-timing">
                    <span>${formattedTime}</span>
                    <span>${formattedDate}</span>
                </div>
            </div>
        </div>`;
}).join('');

    container.innerHTML += htmlString.slice(0, -1); // Remove the trailing comma
}


// Initial load
fetchData();

window.onscroll = function() {
if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight) {
        fetchData();
    }
};
</script>

@endsection


<!-- <figure><img src="{{PUBLIC_PATH}}/assets/front/images/incoming-msg.png" alt="image"></figure> -->