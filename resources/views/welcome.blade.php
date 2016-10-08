<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>larave jwt demo</title>
    <script src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
</head>

<body>

</body>
<script>
    window.onload = function() {
        login();
    }
    window.utoken = "";
    function login() {
        $.ajax({
            url: "/api/login",
            dataType: "json",
            type: "POST",
            data: {"email":"20@qq.com","password":"123456"},
            success: function(data) {
                console.log(data.result);
                window.utoken = data.result;
            }

        });
    }
    function register(){
        var num = Math.ceil(Math.random()*10) + Math.ceil(Math.random()*10) + Math.ceil(Math.random()*10);
        var emal = num + "@qq.com";
        var name = num + "-name";
        var json = {"name":"","email":emal,"password":"123456"};
        json.email = emal;
        json.name = name;
        $.ajax({
            url: "/api/register",
            dataType: "json",
            type: "POST",
            data: json,
            success: function(data) {
                 console.log(data.result)
            }

        });

    }

    function getInfo(){
        var json = {"token":"20@qq.com","name":"20-name"};
        json.token = window.utoken;
        $.ajax({
            url: "/api/get_user_details",
            dataType: "json",
            type: "POST",
            data: json,
            success: function(data) {
                console.log(data.result)
            }

        });
    }
</script>

</html>