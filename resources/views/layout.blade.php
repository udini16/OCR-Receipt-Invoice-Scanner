<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>OCR</title>
    <style>
        body{
            padding: 40px 200px;
        }
        .container{
            border: 1px solid grey;
            padding: 20px;
            text-align: center;
        }
        button{
            background: lightgreen;
        }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
    <div style="text-align: center; margin-top: 20px">
        Copyright {{ date('d/m/Y') }}, Udini
    </div>
</body>
</html>
