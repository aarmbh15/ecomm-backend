<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mail</title>
</head>
<body>
    <h1>Hello {{ $first_name }},</h1>
    <p>Thank you for registering with us. Your account has been successfully verified!</p>
    
    <h3>Your account details:</h3>
    <p><strong>Name:</strong> {{ $first_name }} {{$last_name}}</p>
    <p><strong>UserName:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>

    <p>We recommend that you change your password after logging in for the first time.</p>

    <p>Best Regards,<br>Our Team</p>
</body>
</html>