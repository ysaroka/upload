<!DOCTYPE html>
<html>
<head>
    <title>(S)FTP upload test task</title>

    {{--Bootstrap styles--}}
    <link rel="stylesheet" href="/assets/vendor/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/vendor/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/assets/css/main.css" />
</head>
<body>
<header class="header">
    <div class="container"></div>
</header>
<div class="container">
    @yield('content')
</div>
<footer class="footer">
    <div class="container"></div>
</footer>

@section('scripts')
    {{--jQuery--}}
    <script src="/assets/vendor/js/jquery.min.js"></script>
    <script src="/assets/vendor/js/jquery.form.min.js"></script>
    <script src="/assets/vendor/js/jquery.color-2.1.2.min.js"></script>

    {{--Bootstrap JS--}}
    <script src="/assets/vendor/js/bootstrap.min.js"></script>
@show

</body>
</html>
