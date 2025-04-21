<?php

require_once './classes/AjaxRequest.class.php';
require_once './classes/User.class.php';

class UserAjaxRequest extends AjaxRequest
{
    public $actions = array(
        "login" => "login",
        "logout" => "logout",
        "register" => "register",
        "auth_check" => "authCheck",
        "reset_pass" => "resetPass",
        "verify_email" => "verifyEmail",
        "reset_token" => "resetToken",
        "verify_token" => "verifyToken",
        "check_token" => "checkToken",
        "verify_check" => "verifyCheck",
        "get_info" => "getInfo",
        "get_info_from_id" => "getInfoFromId",
        "set_info" => "setInfo",
        "upload_image" => "uploadImage",
        "test" => "test"
    );

    protected $email;
    protected $password;
    protected $phone;

    public function login()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }
        setcookie("sid", "", 0, "/", "xn--c1asahew.xn--p1ai");

        $email = $this->getRequestParam("email");
        $password = $this->getRequestParam("password");
        $remember = !!$this->getRequestParam("remember-me");

        $user = new Sets\User();
        $auth_result = $user->authorize($email, $password, true);

        if (!$auth_result) {
            $this->setFieldError("password", "Неправильный e-mail или пароль");
            return;
        }

        $this->status = "ok";
        $this->setResponse("redirect", ".");
        $this->message = sprintf("Hello, %s! Access granted.", $email);
        //$this->message = $auth_result;
    }

    public function logout()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        setcookie("sid", "", 0, "/", "xn--c1asahew.xn--p1ai");

        $user = new Sets\User();
        $user->logout();

        $this->setResponse("redirect", ".");
        $this->status = "ok";
        $this->message = "Успешный Выход";

    }

    public function getUserParams() {
        $this->email = $this->getRequestParam("email");
        //$this->password = $this->getRequestParam("password");
        $this->phone = $this->getRequestParam("phone");
    }

    public function register()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        //setcookie("sid", "", 0, "/", "xn--c1asahew.xn--p1ai");
        //sleep(180);

        $this->getUserParams();
        $user = new Sets\User();

        try {
            $str = '0123456789';
            $this->password = substr(str_shuffle($str), 0, 8);
            $new_user_id = $user->create($this->email, $this->password, $this->phone);
        } catch (\Exception $e) {
            $this->setFieldError("username", $e->getMessage());
            return;
        }
        //Авторизация не требуется, только создание юзера после модерации
        //$auth_result = $user->authorize($this->email, $this->password, true);

        // Верификация email (в данной реализации не используется)
        /*try {
            $result = $user->generateToken($this->email, 'EmailVerifyTokens');
        } catch (\Exception $e) {
            $this->setFieldError("email: ", $e->getMessage());
            return;
        }*/

        $this->message = sprintf("Hello, %s! Thank you for registration.", $this->email);
        //$this->message = $auth_result;
        $this->setResponse("redirect", "/");
        $this->status = "ok";
    }

    public function resetToken()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $email = $this->getRequestParam("email");

        $user = new Sets\User();

        try {
            $result = $user->generateToken($email, 'PasswordResetTokens');
        } catch (\Exception $e) {
            $this->setFieldError("email: ", $e->getMessage());
            return;
        }

        if($result) {
            $this->status = "ok";
            $this->message = "Проверьте почту, в ближайшее время Вам придет письмо для смены пароля";
        } else {
            $this->status = "no";
            $this->message = "Пользователь с email:" . $email . " не найден";
        }
    }

    public function verifyToken()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $userid = $this->getRequestParam("userid");
        $user = new Sets\User();
        $email = $user->getUser(NULL, $userid);

        try {
            $result = $user->generateToken($email, 'EmailVerifyTokens');
        } catch (\Exception $e) {
            $this->setFieldError("email: ", $e->getMessage());
            return;
        }

        if($result) {
            $this->status = "ok";
            $this->message = "Проверьте почту, в ближайшее время Вам придет письмо для подтверждения почты";
        } else {
            $this->status = "no";
            $this->message = "Пользователь с email:" . $email . " не найден";
        }

    }

    public function checkToken() {

        $user = new Sets\User();

        $basenames = array(
            'pass' => 'PasswordResetTokens',
            'mail' => 'EmailVerifyTokens',
        );

        $token = $this->getRequestParam("token");
        $basename = $basenames[$this->getRequestParam("basename")];
        $result = $user->checkToken($token, $basename);

        if($result) {
            $this->status = "ok";
            $this->message = "";
        } else {
            $this->status = "denied";
            $this->message = "Ссылка просрочена или не найдена";
        }

    }

    public function resetPass() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
        $token = $this->getRequestParam("token");
        $password = $this->getRequestParam("password");

        try {
            $result = $user->handleToken("PasswordResetTokens", $token, $password);
        } catch (\Exception $e) {
            $this->setFieldError("token: ", $e->getMessage());
            return;
        }

        if($result) {
            $this->status = "ok";
            $this->message = "Пароль изменен успешно";
            $this->setResponse("redirect", "/login");
        } else {
            $this->status = "no";
            $this->message = "Что-то пошло не так, попробуйте еще раз позже";
        }
    }

    public function verifyEmail() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
        $token = $this->getRequestParam("token");

        try {
            $result = $user->handleToken("EmailVerifyTokens", $token);
        } catch (\Exception $e) {
            $this->setFieldError("email: ", $e->getMessage());
            return;
        }

        if($result) {
            $this->status = "ok";
            $this->message = "Почта успешно подтверждена";
        } else {
            $this->status = "no";
            $this->message = "Что-то пошло не так, попробуйте еще раз позже";
        }
    }

    public function authCheck() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
        //print_r ($user);
        $result = $user->isAuthorized();
        if($result) {
            $this->setResponse("redirect", ".");
            $this->status = "ok";
            $this->message = $result;
        } else {
            $this->status = "no";
            $this->message = "Пройдите авторизацию";
        }
    }

    public function verifyCheck() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $userid = $this->getRequestParam('userid');

        $user = new Sets\User();
        //print_r ($user);
        $result = $user->isVerified($userid);
        if($result==1) {
            $this->status = "ok";
            $this->message = $result;
        } else {
            $this->status = "no";
            $this->message = "Для продолжения работы необходимо подтвердить почту";
        }
    }

    public function getInfo() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $userid = $this->getRequestParam("userid");
        $user = new Sets\User();
        $result = $user->getInfo($userid);
        if($result) {
            $this->status = "ok";
            $this->setResponse("response", $result);
        } else {
            $this->status = "no";
            $this->message = "Произошла ошибка";
        }

    }

    public function getInfoFromId() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $id = $this->getRequestParam("id");

        $user = new Sets\User();
        $result = $user->getInfoFromId($id);
        if($result) {
            $this->status = "ok";
            $this->setResponse("response", $result);
        } else {
            $this->status = "no";
            $this->message = "Произошла ошибка";
        }

    }

    public function setInfo(){
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
        $dict = $this->getRequestParam("request_dict");
        $result = $user->setInfo($dict);

        if($result) {
            $this->status = "ok";
            $this->message = $result;
        } else {
            $this->status = "error";
            $this->message = "Произошла ошибка обновления";
        }

    }

    public function uploadImage() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            // Method Not Allowed
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
        try {
            $result = $user->uploadImage();
        } catch (\Exception $e) {
            $this->setFieldError("main", $e->getMessage());
            return;
        }
        if($result) {
            $this->status = "ok";
            $this->message = $result;
        } else {
            $this->status = "err";
            $this->message = "Произошла ошибка загрузки файла";
        }

    }

    public function test() {

        $user = new Sets\User();
        $result=$user->test();
        if($result) {
            $this->status = "ok";
            $this->setResponse("response", $result);
        } else {
            $this->status = "no";
            $this->message = "Ошибка формирования списка доставки";
        }

    }

}