<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
</head>
<body>
    <h1> All Users<h1>
        <ul>
            @foreach($users as $user)
            <li> {{$user['email']}}</li>
            <li> {{$user['password']}}</li>
            <li> {{$user['role']}}</li>
            @endforeach
        </ul>
</body>
</html>