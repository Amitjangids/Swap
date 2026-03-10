<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>KYC Uploaded Successfully</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .thank-you-box {
      max-width: 500px;
      margin: 80px auto;
      padding: 40px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      text-align: center;
    }
    .checkmark {
      font-size: 60px;
      color: hsl(0, 61%, 41%);
    }
  </style>
</head>
<body>

  <div class="thank-you-box">
    <div class="checkmark mb-3">
      ❌
    </div>
    <h3 class="mb-3">{{$error}}</h3>
    
  </div>

</body>
</html>
