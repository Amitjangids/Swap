<!DOCTYPE html>
<html lang="en">
<head>
  <title>Pay By Check Users Management</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
  <h2>User's Master</h2>
  <p>Verify User:</p>            
  <table class="table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Country</th>
        <th>KYC Done</th>
        <th>Verified</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
	  @foreach($uprofile as $key => $data)  
      <tr>
        <td>{{$data->name}}</td>
        <td>{{$data->phone}}</td>
        <td>{{$data->email}}</td>
        <td>{{$data->country}}</td>
        <td>{{$data->is_kyc_done}}</td>
        <td>{{$data->is_verify}}</td>
        <td><a href="{{url('updateStatus/'.$data->id)}}">Verify User</td>
      </tr>
	  @endforeach
    </tbody>
  </table>
</div>

</body>
</html>
