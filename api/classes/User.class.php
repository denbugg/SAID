<?php

namespace Sets;

require_once 'Action.class.php';
include 'Mail.class.php';

class User extends Action
{
    private $is_authorized = false;
    protected $role;
    protected $email;
    protected $password;

    public function __construct()
    {
        parent::__construct();
        $this->role = 4;
    }

    public static function isAuthorized()
    {
        if (!empty($_SESSION["userid"])) {
            return $_SESSION["userid"];
        }
        return false;
    }

    public function isVerified($userid) {

        $query = "select verifyEmail from UsersTest where userid = :userid limit 1";
        $sth = $this->db->prepare($query);
        $sth->execute(
            array(
                ":userid" => $userid
            )
        );
        $row = $sth->fetch();
        if (!$row) {
            return false;
        }
        return $row["verifyEmail"];
    }

    public function getUser($email = NULL, $userid = NULL) {
        // функция получения данных для внутренних нужд: проверка на существование пользователя
        if(is_null($email)) {
            $query = "select email from users where userid = :userid limit 1";
            $param_array = array(
                ":userid" => $userid,
            );
        } elseif(is_null($userid)) {
            $query = "select email from users where email = :email limit 1";
            $param_array = array(
                ":email" => $email,
            );
        } else {
            return false;
        }

        $sth = $this->db->prepare($query);
        $sth->execute($param_array);

        $row = $sth->fetch();
        if (!$row) {
            return false;
        }
        return $row["email"];

    }

    public function checkUniqueUserid($userid) {
        // функция проверки уникальность userid
        $query = "select userid from users where userid = :userid limit 1";
        $sth = $this->db->prepare($query);
        $sth->execute(
            array(
                ":userid" => $userid
            )
        );
        $row = $sth->fetch();
        if (!$row) {
            return false;
        }
        return $row["userID"];
    }

    public function authorize($email, $password, $remember=true)
    {
        $query = "select id, userid, email, password from users where
            email = :email limit 1";
        $sth = $this->db->prepare($query);

        $sth->execute(
            array(
                ":email" => $email,
            )
        );
        $this->user = $sth->fetch();

        if (!$this->user) {
            $this->is_authorized = false;  // юзер с указанным e-mail не найден
        } else {

            $this->userid = $this->user['userid'];

            if(password_verify($password, $this->user['password'])){
            $this->is_authorized = true;
            $this->saveSession($remember);
            } else {
                $this->is_authorized = false;
            }

        }

        return $this->is_authorized;
        //return $this->userid;
        //return $result;
        //return $hash;
    }

    public function generateToken($email, $basename) {

        $user_exists = $this->getUser($email);

        if (!$user_exists) {
            throw new \Exception("Пользователь с e-mail: " . $email . " не найден", 1);
        }

        try {
            $token = bin2hex(random_bytes(16));
        } catch (TypeError $e) {
            // Well, it's an integer, so this IS unexpected.
            die("An unexpected error has occurred");
        } catch (Error $e) {
            // This is also unexpected because 32 is a reasonable integer.
            die("An unexpected error has occurred");
        } catch (Exception $e) {
            // If you get this message, the CSPRNG failed hard.
            die("Could not generate a random string. Is our OS secure?");
        }

        $date = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $query = "insert into $basename (email, token, date)
            values (:email, :token, :date)";

        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute(
                array(
                    ':email' => $email,
                    ':token' => $token,
                    ':date' => $date,
                )
            );
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Database error: " . $e->getMessage();
            die();
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Database error %d %s", $info[1], $info[2]);
            die();
        }
        $mail = new userMailings();
        if($basename=='PasswordResetTokens') {
            $mail->mailPassToken($email, $token);
        } elseif($basename=='EmailVerifyTokens') {
            $mail->mailVerifyToken($email, $token);
        }

        return $result;

    }

    public function checkToken($token, $basename) {

        $date = date("Y-m-d H:i:s");

        $query = "select email from $basename where token=:token and date>:date";

        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute(
                array(
                    ':token' => $token,
                    ':date' => $date,
                )
            );
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Database error: " . $e->getMessage();
            die();
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Database error %d %s", $info[1], $info[2]);
            die();
        }

        $row = $sth->fetch();
        if (!$row) {
            return false;
        }
        return $row["email"];

    }

    public function handleToken($basename, $token, $password=null) {
        $email = $this->checkToken($token, $basename);

        if (!$email) {
            throw new \Exception("Токен не найден или просрочен", 1);
        }

        if($basename=='PasswordResetTokens') {
            $query = "update UsersTest set password=:password where email=:email";
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $param_array = array(
                ':email' => $email,
                ':password' => $hash,
            );
        } elseif($basename=='EmailVerifyTokens') {
            $query = "update UsersTest set verifyEmail=1 where email=:email";
            $param_array = array(
                ':email' => $email,
            );
        }

        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute($param_array);
            $this->db->commit();

        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Database error: " . $e->getMessage();
            die();
        }

        if ($result) {

            $query = "delete from $basename where token=:token";
            $sth = $this->db->prepare($query);

            try {
                $this->db->beginTransaction();
                $result = $sth->execute(
                    array(
                        ':token' => $token,
                    )
                );
                $this->db->commit();
            } catch (\PDOException $e) {
                $this->db->rollback();
                echo "Database error: " . $e->getMessage();
                die();
            }
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Database error %d %s", $info[1], $info[2]);
            die();
        }

        $mail = new userMailings();

        if($basename=='PasswordResetTokens') {
            $mail->mailPassReset($email);
        } elseif($basename=='MailVerifyTokens') {
            $mail->mailVerifyEmail($email);
        }

        return $result;
    }

    /*public function resetPass($token, $password) {

        $email = $this->checkToken($token, "PasswordResetTokens");

        if (!$email) {
            throw new \Exception("Токен не найден или просрочен", 1);
        }

        $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, 1);

        $query = "update UsersTest set password=:password where email=:email";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute(
                array(
                    ':email' => $email,
                    ':password' => $hash,
                )
            );
            $this->db->commit();

        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Database error: " . $e->getMessage();
            die();
        }

        if ($result) {

            $query = "delete from `PasswordResetTokens` where token=:token";
            $sth = $this->db->prepare($query);

            try {
                $this->db->beginTransaction();
                $result = $sth->execute(
                    array(
                        ':token' => $token,
                    )
                );
                $this->db->commit();
            } catch (\PDOException $e) {
                $this->db->rollback();
                echo "Database error: " . $e->getMessage();
                die();
            }
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Database error %d %s", $info[1], $info[2]);
            die();
        }

        $this->mailVerifyEmail($email);
        return $result;
    }

    public function verifyEmail($token) {

        $email = $this->checkToken($token, "MailVerifyTokens");

        if (!$email) {
            throw new \Exception("Токен не найден или просрочен", 1);
        }

        $query = "update UsersTest set verify_email=1 where email=:email";

        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute(
                array(
                    ':email' => $email,
                )
            );
            $this->db->commit();

        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Database error: " . $e->getMessage();
            die();
        }

        if ($result) {

            $query = "delete from `VerifyEmailTokens` where token=:token";
            $sth = $this->db->prepare($query);

            try {
                $this->db->beginTransaction();
                $result = $sth->execute(
                    array(
                        ':token' => $token,
                    )
                );
                $this->db->commit();
            } catch (\PDOException $e) {
                $this->db->rollback();
                echo "Database error: " . $e->getMessage();
                die();
            }
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Database error %d %s", $info[1], $info[2]);
            die();
        }

        $this->mailPassReset($email);
        return $result;
    }*/

    public function logout()
    {
        if (!empty($_SESSION["userid"])) {
            unset($_SESSION["userid"]);
        }
    }

    public function saveSession($remember = true, $http_only = true, $days = 30)
    {
        if (!empty($_COOKIE['PHPSESSID'])) {
            // check session id in cookies
            session_id($_COOKIE['PHPSESSID']);
        }

        $_SESSION["userid"] = $this->userid;

        if ($remember) {
            // Save session id in cookies

            $sid = session_id();

            $expire = time() + $days * 24 * 3600;
            $domain = "xn--c1asahew.xn--p1ai"; // default domain
            $secure = false;
            $path = "/";

            setcookie("sid", $sid, $expire, $path, $domain, $secure, $http_only);
        }

        return $_COOKIE['PHPSESSID'];
    }

    public function create($email, $password, $phone) {
        $user_exists = $this->getUser($email);
        $this->email = $email;
        $this->password = $password;

        if ($user_exists) {
            throw new \Exception("Такой пользователь уже существует: " . $email, 1);
        }

        if ($email == "") {
            throw new \Exception("Для верификации нужно ввести e-mail контактного лица", 1);
        }

        $str = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        do {

            $userid = substr(str_shuffle($str), 0, 28);
        } while ($this->checkUniqueUserid($userid));

        $query = "insert into users (userid, email, password, phone)
            values (:userid, :email, :password, :phone)";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute(
                array(
                    ':email' => $email,
                    ':password' => $hash,
                    ':userid' => $userid,
                    ':phone' => $phone
                )
            );
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Ошибка базы данных 1010: " . $e->getMessage();
            die();
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Ошибка базы данных 1011: %d %s", $info[1], $info[2]);
            die();
        }

        $query = "update donors set userid=:userid where contactEmail=:email";
        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute(
                array(
                    ':email' => $email,
                    ':userid' => $userid
                )
            );
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Ошибка базы данных 2010: " . $e->getMessage();
            die();
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Ошибка базы данных 2011: %d %s", $info[1], $info[2]);
            die();
        }

        //Добавление данных пользователя в таблицу не требуется, так как они уже есть после верификации
        /*$query = "insert into UserInfo (userID, name, lastname, birthDate, country, region, town, coachID)
            values (:userid, :name, :lastname, :birthDate, :country, :region, :town, :coachid)";
        $sth = $this->db->prepare($query);

        try {
            $this->db->beginTransaction();
            $result = $sth->execute(
                array(
                    ':userid' => $userid,
                    ':name' => $name,
                    ':lastname' => $lastname,
                    ':birthDate' => $birthDate,
                    ':country' => $country,
                    ':region' => $region,
                    ':town' => $town,
                    ':coachid' => $coachid,
                )
            );
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Ошибка базы данных 2010: " . $e->getMessage();
            die();
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Ошибка базы данных 2011: %d %s", $info[1], $info[2]);
            die();
        }*/
        $this->userid = $userid;
        if($result) {
            $this->sendGreetingMessage();
        }
        return $result;

    }

    public function getInfo($userid = null) {
        if($userid) {
            $requestid = $userid;
        } else {
            $requestid = $this->isAuthorized();
        }
        $query = "select * from users left join donors using (userid) WHERE userid=:userid";
        $sth = $this->db->prepare($query);
        $sth->execute(
            array(
                ":userid" => $requestid
            )
        );
        $row = $sth->fetch();
        if (!$row) {
            return false;
        }
        return $row;
    }

    public function getInfoFromId($id) {
        $query = "select UserInfo.*, Coaches.*, UsersTest.role, UserInfo.id as uid, Coaches.id as cid from UserInfo 
            left join Coaches using (userid) left join UsersTest using(userid) where UserInfo.id = :id limit 1";
        $sth = $this->db->prepare($query);
        $sth->execute(
            array(
                ":id" => $id
            )
        );
        $row = $sth->fetch();
        if (!$row) {
            return false;
        }
        return $row;
    }

    public function setInfo($dict) {
        $request = json_decode($dict);
        $request_array = array();

        $query_str = "";
        foreach ($request as $key => $value) {
            $query_str .= $key . " = :" . $key . ", " ;
            $request_array[":". $key] = $value;
        }
        $query_str = substr($query_str, 0, strlen($query_str)-2);

        $query = "update UserInfo set " . $query_str . " where userid = :userid";
        $sth = $this->db->prepare($query);

        $request_array[':userid'] = $this->userid;

        try {
            $this->db->beginTransaction();
            $result = $sth->execute($request_array);
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollback();
            echo "Ошибка базы данных 2020: " . $e->getMessage();
            die();
        }

        if (!$result) {
            $info = $sth->errorInfo();
            printf("Ошибка базы данных 2021: %d %s", $info[1], $info[2]);
            //printf($query);
            die();
        }

        return $result;

    }

    function uploadImage() {
        $MaxFileSizeInBytes = 5242880;
        // Разрешение расширения файлов для загрузки
        $AllowFileExtension = array('jpg', 'png', 'jpeg');
        // Оригинальное название файла
        $FileName = $_FILES['userImage']['name'];
        $NewFileName = uniqid();
        // Полный путь до временного файла
        $TempName = $_FILES['userImage']['tmp_name'];
        // Папка где будут загружатся файлы
        $UploadDir = "../images/";
        $FileExtension = pathinfo($FileName, PATHINFO_EXTENSION);
        // Полный путь к новому файлу в папке сервера
        $NewFilePatch = $UploadDir.$NewFileName.".".$FileExtension;
        if($FileName) {
            // Проверка если расширение файла находится в массиве доступных
            if(!in_array($FileExtension, $AllowFileExtension)) {
                throw new \Exception("Файлы с расширением {$FileExtension} не допускаются", 1);
            }
            else {
                // Проверка размера файла
                if(filesize($TempName) > $MaxFileSizeInBytes) {
                    throw new \Exception ("Размер загружаемого файла превышает 5МБ", 2);
                }
                else {
                    // Проверяем права доступа на папку
                    if(!is_writable($UploadDir)) {
                        throw new \Exception("Папка ".$UploadDir." не имеет прав на запись", 3);
                    }
                    else {
                        // Копируем содержимое временного файла $TempName и создаем нового в папке сервера
                        $CopyFile = copy($TempName, $NewFilePatch);
                        if(!$CopyFile) {
                            throw new \Exception("Возникла ошибка, файл не удалось загрузить!",4);
                        }
                        else {

                            $query = "update UserInfo set image = :image where userid = :userid";
                            $sth = $this->db->prepare($query);

                            try {
                                $this->db->beginTransaction();
                                $result = $sth->execute(
                                    array(
                                        ':image' => $NewFileName.".".$FileExtension,
                                        ':userid' => $this->userid
                                    )
                                );
                                $this->db->commit();
                            } catch (\PDOException $e) {
                                $this->db->rollback();
                                echo "Ошибка базы данных 3010: " . $e->getMessage();
                                die();
                            }

                            if (!$result) {
                                $info = $sth->errorInfo();
                                printf("Ошибка базы данных 3020: %d %s", $info[1], $info[2]);
                                die();
                            } else {
                                return "Спасибо, фото успешно загружено!";
                            }
                        }
                    }
                }
            }
        }
    }

    public function sendGreetingMessage() {
        $subject = "Успешно пройдена верификация благотворителя";
        $template = "event";
        $message = "<p>Здравствуйте!</p><p>Вы успешно авторизованы в системе единого учета и распределения гуманитарной 
            помощи. Для доступа в закрытую часть используйте этот адрес электронный почты в качестве логина.</p>
            <p>Ваш пароль: ". $this->password . " </p><p>Адрес для входа в систему: https://гумпом.рф</p>
            <p>Просим Вас вводить все грузы по номенклатуре продуктового и гигиенического наборов в систему единого 
            учета и распределения гуманитарной помощи (через Календарь).</p><p>Мы понимаем, что для Вас это означает 
            определенную дополнительную нагрузку, вместе с тем - это позволит нам систематизировать данные о поставках.</p>
            <p>Систематизация данных позволит нам с одной стороны чётко планировать грузы, а с другой фиксировать все 
            случаи возникновения дефицита тех или иных товаров.</p>
            <p>Учитывая, что количество граждан, имеющих право получения помощи превышает 100 тысяч человек для нас 
            очень важно понимать, что мы полностью и своевременно обеспечиваем первичные потребности наших граждан.</p>
            <p>По всем возникающим вопросам просим Вас обращаться по телеграмму: +7 985 766 55 14 (https://t.me/ivan_petrin)</p>";

        $mail = new Mail($this->email, $subject, $message, $template);
        $mail->pushMailPhpMailer();
    }

    /*public function test() {

        // Создаем письмо

        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $yourEmail = 'no-reply@gum.center'; // ваш email на яндексе
        $password = 'Elite177elite'; // ваш пароль к яндексу или пароль приложения

        // настройки SMTP
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();

        $mail->Host       = 'mail.nic.ru';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@gum.center';
        $mail->Password   = 'Elite177elite';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // формируем письмо

        // от кого: это поле должно быть равно вашему email иначе будет ошибка
        $mail->setFrom($yourEmail, 'Gym Center');
        $mail->addReplyTo($yourEmail, 'Gym Center');

        // кому - получатель письма
        //$mail->addAddress($this->email, "");  // кому
        $mail->addAddress('6693455@mail.ru', "");  // кому
        $mail->addAddress('5830650@mail.ru', "");  // кому
        $mail->addAddress('avpetrin@yandex.ru', "");  // кому

        $mail->Subject = 'Успешно пройдена верификация благотворителя';  // тема письма

        $mail->msgHTML("<html><body>
				<h4>Успешно пройдена верификация благотворителя</h4>
				<p>Здравствуйте!</p><p>Ваша заявка успешно прошла модерацию. Для доступа в закрытую часть используйте
                этот адрес электронный почты в качестве логина</p><p>Ваш пароль: ". $this->password . " </p>
				</html></body>");

        if ($mail->send()) {
            die('Письмо отправлено!');
        } else {
            die('Ошибка: ' . $mail->ErrorInfo);
        }
    }*/

}

class userMailings
{

    public function mailPassToken($email, $token) {

        $subject = "Восстановление пароля на mypolechka.ru";
        $template = "event";
        $message = "<p>Здравствуйте!</p><p>Кто-то запросил восстановление пароля для доступа к mypolechka.ru.</p>";
        $message .= "<p>Восстановить пароль можно по ссылке:<a href='https://mypolechka.ru/reset_pass?token=";
        $message .= $token . "'>https://mypolechka.ru/reset_pass?token=" . $token . "</a></p>";
        $message .= "<p>Если Вы не запрашивали восстановление пароля - проигнорируйте данное письмо</p>";

        $mail = new Mail($email, $subject, $message, $template);
        $mail->pushMail();

    }

    public function mailPassReset($email) {
        $subject = "Успешная смена пароля на mypolechka.ru";
        $template = "event";
        $message = "<p>Здравствуйте!</p><p>Пароль для доступа к mypolechka.ru успешно изменен.</p>";

        $mail = new Mail($email, $subject, $message, $template);
        $mail->pushMail();
    }

    public function mailVerifyToken($email, $token) {

        $subject = "Подтверждение e-mail на mypolechka.ru";
        $template = "event";
        $message = "<p>Здравствуйте!</p><p>Для окончания регистрации на mypolechka.ru подтвердите e-mail";
        $message .= " пройдя по ссылке:<a href='https://mypolechka.ru/verify_email?token=";
        $message .= $token . "'>https://mypolechka.ru/verify_email?token=" . $token . "</a></p>";
        $message .= "<p>Если Вы не регистрировались на mypolechka.ru - проигнорируйте данное письмо</p>";

        $mail = new Mail($email, $subject, $message, $template);
        $mail->pushMail();

    }

    public function mailVerifyEmail($email) {
        $subject = "Успешное подтверждение e-mail на mypolechka.ru";
        $template = "event";
        $message = "<p>Здравствуйте!</p><p>Ваш e-mail успешно подтвержден.</p>";

        $mail = new Mail($email, $subject, $message, $template);
        $mail->pushMail();
    }
}