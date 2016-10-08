# Laravel 5.3 jwt


## 通过 Laravel 安装工具

```s
composer global require "laravel/installer"

laravel new jwt-laravel5-3
```

## 安装  JWT 扩展

```s
composer require tymon/jwt-auth
```

之后打开 `config/app.php` 文件添加 `service`  `provider` 和  `aliase`


config/app.php

```php
'providers' => [
    ....
    Tymon\JWTAuth\Providers\JWTAuthServiceProvider::class,
],
'aliases' => [
    ....
    'JWTAuth' => Tymon\JWTAuth\Facades\JWTAuth::class
],
```


OK，现在来发布 `JWT` 的配置文件，比如令牌到期时间配置等

```
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\JWTAuthServiceProvider"
```

最后一步需要生成 `JWT Key`

```
php artisan jwt:generate
```

##  创建API路由

### 创建中间件 `cors`

```
php artisan make:middleware CORS
```

进入 `app/Http/Middleware` ，编辑 `CORS.php`


`handle` 内容

```php
// return $next($request);
header('Access-Control-Allow-Origin: *');

$headers = [
    'Access-Control-Allow-Methods'=> 'POST, GET, OPTIONS, PUT, DELETE',
    'Access-Control-Allow-Headers'=> 'Content-Type, X-Auth-Token, Origin'
];

if($request->getMethod() == "OPTIONS") {
    return Response::make('OK', 200, $headers);
};

$response = $next($request);
foreach($headers as $key => $value)
    $response->header($key, $value);
return $response;
```


在 `app/Http/Kernel.php` 注册中间件

```php
namespace App\Http;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
class Kernel extends HttpKernel
{
    ...
    ...
    protected $routeMiddleware = [
        ...
        'cors' => \App\Http\Middleware\CORS::class,
    ];
}
```

有了这个中间件我们就解决了跨域问题。接下来回到路由


`routes/web.php` 

```php
Route::group(['middleware' => ['api','cors'],'prefix' => 'api'], function () {
    Route::post('register', 'ApiController@register');     // 注册
    Route::post('login', 'ApiController@login');           // 登陆
    Route::group(['middleware' => 'jwt.auth'], function () {
        Route::post('get_user_details', 'APIController@get_user_details');  // 获取用户详情
    });
})
```

建议：过滤掉路由 `api/*` 下的 `csrf_token` ，方便测试开发

上面的 `jwt-auth` 中间件现在还是无效的，接着创建这个 `middleware`

```
php artisan make:middleware authJWT
```

编辑这个 `authJWT.php`

app/Http/Middleware/authJWT.php

```php
namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
class authJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        try {
            // 如果用户登陆后的所有请求没有jwt的token抛出异常
            $user = JWTAuth::toUser($request->input('token')); 
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['error'=>'Token 无效']);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['error'=>'Token 已过期']);
            }else{
                return response()->json(['error'=>'出错了']);
            }
        }
        return $next($request);
    }
}

```


接着注册该中间件

app/Http/Kernel.php

```php
namespace App\Http;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
class Kernel extends HttpKernel
{
    ...
    ...
    protected $routeMiddleware = [
        ...
        'jwt.auth' => \App\Http\Middleware\authJWT::class,
    ];
}
```


然后，我们创建控制器管理所有的请求


```
php artisan make:controller Apicontroller
```

编辑: app/Http/Controllers/ApiController.php

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class Apicontroller extends Controller
{   
    // 注册
    public function register(Request $requset)
    {
        $input = $requset->all();
        $input['password'] = Hash::make($input['password']);
        User::create($input);
        return response()->json(['result'=>true]);
    }

    // 登陆
    public function login(Request $request)
    {
        $input = $request->all();
        if (!$token = JWTAuth::attempt($input)) {
            return response()->json(['result' => '邮箱或密码错误.']);
        }
        return response()->json(['result' => $token]);
    }

    // 获取用户信息
    public function get_user_details(Request $request)
    {
        $input = $request->all();
        $user = JWTAuth::toUser($input['token']);
        return response()->json(['result' => $user]);
    }
}

```

`users` 数据库迁移 
```
php artisan migrate
```

到此,编码完成，下面就测试一下吧！

为了方便开发 编辑 `package.json`

`scripts` 加入如下 
```
"web": "php artisan serve --host=0.0.0.0  --port=8000",
```

执行 `npm run web` 浏览器打开网页 


编辑 `welcome.blade.php`

```
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
    window.authAoken = "";
    function login() {
        $.ajax({
            url: "/api/login",
            dataType: "json",
            type: "POST",
            data: {"email":"20@qq.com","password":"123456"},
            success: function(data) {
                console.log(data.result);
                window.authAoken = data.result;
            }

        });
    }
    function register(){
        var json = {"name":"zan","email":"20@qq.com","password":"123456"};
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
        var json = {"token":"20@qq.com","name":"zan"};
        json.token = window.authAoken;
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
```

到此 jwt 基础完成

## License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
