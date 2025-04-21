<?php

// Combined includes from all files
include './classes/Operator.class.php';
include './classes/Donor.class.php';
include './classes/Admin.class.php';
require_once './classes/AjaxRequest.class.php';
require_once './classes/User.class.php';

// Operator Ajax Request Class
class operatorAjaxRequest extends AjaxRequest
{
    public $actions = array(
        "is_operator" => "isOperator",
        "is_irregular_operator" => "isIrregularOperator",
        "get_warehouses" => "getWarehouses",
        "get_donors" => "getDonors",
        "delete_deliver" => "deleteDeliver",
        "change_deliver" => "changeDeliver",
        "add_warehouse" => "addWarehouse",
        "re_sort_all_orders" => "reSortAllOrders",
        "get_metric_list" => "getMetricList",
        "save_metric_values" => "saveMetricValues",
        "save_irregular_needs" => "saveIrregularNeeds",
    );

    public function isOperator() {
        if($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $result = $operator->isOperator();
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Отказано в доступе. Пользователь не является оператором";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function isIrregularOperator() {
        if($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $result = $operator->isIrregularOperator();
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Отказано в доступе. Пользователь не является оператором нерегулярных поставок";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function getWarehouses() {
        if($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $result = $operator->getWarehouses();
                if($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка получения списка складов";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function getDonors() {
        if($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $result = $operator->getDonors();
                if($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка получения списка доноров";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function deleteDeliver() {
        if($this->checkMethod()) {
            $deliverid = $this->getRequestParam('deliverid');
            try {
                $operator = new Sets\Operator();
                $result = $operator->deleteDeliver($deliverid);
                if($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка удаления доставки";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function changeDeliver() {
        if($this->checkMethod()) {
            $list = json_decode($this->getRequestParam('list'));
            $deliverid = $this->getRequestParam('deliverid');
            $date = $this->getRequestParam('date');
            try {
                $operator = new Sets\Operator();
                $result = $operator->changeDeliver($list, $deliverid, $date);
                if($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка изменения доставки";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function addWarehouse() {
        if($this->checkMethod()) {
            $name = $this->getRequestParam('name');
            try {
                $operator = new Sets\Operator();
                $result = $operator->addWarehouse($name);
                if($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка добавления Поставщика";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function reSortAllOrders() {
        $operator = new Sets\Operator();
        $date = $this->getRequestParam('date');
        $result = $operator->reSortAllOrders($date);
        if($result) {
            $this->status = "ok";
            $this->setResponse("response", $result);
        } else {
            $this->status = "no";
            $this->message = "Ошибка пересортировки доставок";
        }
    }

    public function getMetricList() {
        if ($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $result = $operator->getMetricList();
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка получения списка метрик";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function saveMetricValues() {
        if ($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $metrics = json_decode($this->getRequestParam('metrics'))->value;
                $result = $operator->saveMetricValues($metrics);
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка сохранения метрик";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function saveIrregularNeeds() {
        if ($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $needs = json_decode($this->getRequestParam('needs'))->value;
                $result = $operator->saveIrregularNeeds($needs);
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка сохранения потребностей нерегулярных поставок";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }
}

// Donor Ajax Request Class
class donorAjaxRequest extends AjaxRequest
{
    public $actions = array(
        "send_to_base" => "sendToBase",
        "get_list_for_approve" => "getListForApprove",
        "form_deliver" => "formDeliver",
        "get_delivers_list" => "getDeliversList",
        "get_remains" => "getRemains",
        "get_calendar" => "getCalendar",
        "get_remain_for_day" => "getRemainForDay",
        "match_deliver" => "matchDeliver",
        "get_real_remains" => "getRealRemains",
        "save_real_remains" => "saveRealRemains",
        "get_done_sets" => "getDoneSets",
        "save_done_sets" => "saveDoneSets",
        "get_irregular_needs" => "getIrregularNeeds",
        "save_irregular_deliver" => "saveIrregularDeliver",
        "test" => "test"
    );

    public function sendToBase() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $form = $this->getRequestParam("form");
            $fullname = $this->getRequestParam("fullname");
            $shortname = $this->getRequestParam("shortname");
            $adress = $this->getRequestParam("adress");
            $mail = $this->getRequestParam("mail");
            $passport = $this->getRequestParam("passport");
            $phone = $this->getRequestParam("phone");
            $email = $this->getRequestParam("email");
            $inn = $this->getRequestParam("inn");
            $kpp = $this->getRequestParam("kpp");
            $ogrn = $this->getRequestParam("ogrn");
            $okpo = $this->getRequestParam("okpo");
            $okved = $this->getRequestParam("okved");
            $bank = $this->getRequestParam("bank");
            $account = $this->getRequestParam("account");
            $bik = $this->getRequestParam("bik");
            $headFio = $this->getRequestParam("headFio");
            $headPhone = $this->getRequestParam("headPhone");
            $headEmail = $this->getRequestParam("headEmail");
            $contactFio = $this->getRequestParam("contactFio");
            $contactPhone = $this->getRequestParam("contactPhone");
            $contactEmail = $this->getRequestParam("contactEmail");
            try {
                $result = $donor->sendToBase($form, $headFio, $headPhone, $headEmail, $contactFio, $contactPhone, $contactEmail,
                    $fullname, $shortname, $adress, $mail, $passport, $phone, $email, $inn, $kpp, $ogrn, $okpo, $okved, $bank, $account, $bik);
                if($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка вставки благотворителя в базу";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function getListForApprove() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $result = $donor->getListForApprove();
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения списка благотворителей";
            }
        }
    }

    public function formDeliver() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $list_deliver = $this->getRequestParam("list_deliver");
            $list = json_decode($list_deliver);
            $comment = $this->getRequestParam("comment");
            $warehouse = $this->getRequestParam("warehouse");
            $userid = $this->getRequestParam("userid");
            $date = $this->getRequestParam("date");
            $result = $donor->formDeliver($list, $comment, $warehouse, $userid, $date);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка формирования списка доставки";
            }
        }
    }

    public function getDeliversList() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $date = $this->getRequestParam('date');
            $result = $donor->getDeliversList($date);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения списка доставок";
            }
        }
    }

    public function getRemains() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $date = $this->getRequestParam("date");
            $result = $donor->getRemains($date);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения списка необходимого";
            }
        }
    }

    public function getCalendar() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $type = $this->getRequestParam("type");
            $date = $this->getRequestParam("date");
            $result = $donor->getCalendar($type, $date);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения календаря";
            }
        }
    }

    public function getRemainForDay() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $type = $this->getRequestParam("type");
            $date = $this->getRequestParam("date");
            $result = $donor->getRemainForDay($type, $date);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения потребностей дня";
            }
        }
    }

    public function matchDeliver() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $type = $this->getRequestParam('type');
            $quantity = $this->getRequestParam('quantity');
            $date = $this->getRequestParam('date');
            $result = $donor->matchDeliver($type, $quantity, $date);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка подбора доставки";
            }
        }
    }

    public function getRealRemains() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $result = $donor->getRealRemains();
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения остатков";
            }
        }
    }

    public function saveRealRemains() {
        if ($this->checkMethod()) {
            try {
                $donor = new Sets\Donor();
                $remains = json_decode($this->getRequestParam('remains'))->value;
                $result = $donor->saveRealRemains($remains);
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка сохранения реальных остатков";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function getDoneSets() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $result = $donor->getDoneSets();
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения готовых наборов";
            }
        }
    }

    public function saveDoneSets() {
        if ($this->checkMethod()) {
            try {
                $donor = new Sets\Donor();
                $product = $this->getRequestParam('product');
                $gigiena = $this->getRequestParam('gigiena');
                $result = $donor->saveDoneSets($product, $gigiena);
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка сохранения готовых наборов";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function getIrregularNeeds() {
        if($this->checkMethod()) {
            $type = $this->getRequestParam('type');
            $donor = new Sets\Donor();
            $result = $donor->getIrregularNeeds($type);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения списка нерегулярных поставок";
            }
        }
    }

    public function saveIrregularDeliver() {
        if($this->checkMethod()) {
            $delivers = json_decode($this->getRequestParam('delivers'))->value;
            $contact = $this->getRequestParam('contact');
            $date = $this->getRequestParam('date');
            $type = $this->getRequestParam('type');
            $donor = new Sets\Donor();
            $result = $donor->saveIrregularDeliver($delivers, $contact, $date, $type);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка сохранения списка нерегулярных поставок";
            }
        }
    }

    public function test() {
        $donor = new Sets\Donor();
        $result = $donor->test();
        if($result) {
            $this->status = "ok";
            $this->setResponse("response", $result);
        } else {
            $this->status = "no";
            $this->message = "Ошибка формирования списка доставки";
        }
    }
}

// Admin Ajax Request Class
class adminAjaxRequest extends AjaxRequest
{
    public $actions = array(
        "is_admin" => "isAdmin",
        "add_metric" => "addMetric",
        "get_metrics_dashboard" => "getMetricsDashboard",
    );

    public function isAdmin()
    {
        if ($this->checkMethod()) {
            try {
                $admin = new Sets\Admin();
                $result = $admin->isAdmin();
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Отказано в доступе. Пользователь не является администратором";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function addMetric()
    {
        if ($this->checkMethod()) {
            try {
                $admin = new Sets\Admin();
                $name = $this->getRequestParam('name');
                $red = $this->getRequestParam('red');
                $orange = $this->getRequestParam('orange');
                $yellow = $this->getRequestParam('yellow');
                $target = $this->getRequestParam('target');
                $result = $admin->addMetric($name, $red, $orange, $yellow, $target);
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка добавления метрики";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }

    public function getMetricsDashboard()
    {
        if ($this->checkMethod()) {
            try {
                $admin = new Sets\Admin();
                $result = $admin->getMetricsDashboard();
                if ($result) {
                    $this->status = "ok";
                    $this->setResponse("response", $result);
                } else {
                    $this->status = "no";
                    $this->message = "Ошибка получения доски метрик";
                }
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
        }
    }
}

// User Ajax Request Class
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
    }

    public function logout()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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
        $this->phone = $this->getRequestParam("phone");
    }

    public function register()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

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

        $this->message = sprintf("Hello, %s! Thank you for registration.", $this->email);
        $this->setResponse("redirect", "/");
        $this->status = "ok";
    }

    public function resetToken()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $email = $this->getRequestParam("email");
        $user = new Sets\User();

        try {
            $result = $user->generateToken($email, 'PasswordResetTokens');
            if($result) {
                $this->status = "ok";
                $this->message = "Проверьте почту, в ближайшее время Вам придет письмо для смены пароля";
            } else {
                $this->status = "no";
                $this->message = "Пользователь с email:" . $email . " не найден";
            }
        } catch (\Exception $e) {
            $this->setFieldError("email: ", $e->getMessage());
            return;
        }
    }

    public function verifyToken()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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
            if($result) {
                $this->status = "ok";
                $this->message = "Проверьте почту, в ближайшее время Вам придет письмо для подтверждения почты";
            } else {
                $this->status = "no";
                $this->message = "Пользователь с email:" . $email . " не найден";
            }
        } catch (\Exception $e) {
            $this->setFieldError("email: ", $e->getMessage());
            return;
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
            if($result) {
                $this->status = "ok";
                $this->message = "Пароль изменен успешно";
                $this->setResponse("redirect", "/login");
            } else {
                $this->status = "no";
                $this->message = "Что-то пошло не так, попробуйте еще раз позже";
            }
        } catch (\Exception $e) {
            $this->setFieldError("token: ", $e->getMessage());
            return;
        }
    }

    public function verifyEmail() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
        $token = $this->getRequestParam("token");

        try {
            $result = $user->handleToken("EmailVerifyTokens", $token);
            if($result) {
                $this->status = "ok";
                $this->message = "Почта успешно подтверждена";
            } else {
                $this->status = "no";
                $this->message = "Что-то пошло не так, попробуйте еще раз позже";
            }
        } catch (\Exception $e) {
            $this->setFieldError("email: ", $e->getMessage());
            return;
        }
    }

    public function authCheck() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
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
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $userid = $this->getRequestParam('userid');
        $user = new Sets\User();
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
            http_response_code(405);
            header("Allow: POST");
            $this->setFieldError("main", "Method Not Allowed");
            return;
        }

        $user = new Sets\User();
        try {
            $result = $user->uploadImage();
            if($result) {
                $this->status = "ok";
                $this->message = $result;
            } else {
                $this->status = "err";
                $this->message = "Произошла ошибка загрузки файла";
            }
        } catch (\Exception $e) {
            $this->setFieldError("main", $e->getMessage());
            return;
        }
    }

    public function test() {
        $user = new Sets\User();
        $result = $user->test();
        if($result) {
            $this->status = "ok";
            $this->setResponse("response", $result);
        } else {
            $this->status = "no";
            $this->message = "Ошибка формирования списка доставки";
        }
    }
}

// API Routing Logic
if (!empty($_COOKIE['sid'])) {
    session_id($_COOKIE['sid']);
}
session_start();

$api = array(
    "user" => "UserAjaxRequest",
    "donor" => "donorAjaxRequest",
    "operator" => "operatorAjaxRequest",
    "admin" => "adminAjaxRequest",
);

$apiName = $api[$_REQUEST['api']];
$ajaxRequest = new $apiName($_REQUEST);
$ajaxRequest->showResponse();