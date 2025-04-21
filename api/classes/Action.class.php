<?php

namespace Sets;

class Action
{
    protected $db;
    protected $userid;

    private $db_host = "c15869.mysql";
    protected $db_name = "c15869_sets";
    private $db_user = "c15869_polechka";
    private $db_pass = "BWo:6Kqc";

    public function __construct()
    {
        $this->connectDb($this->db_name, $this->db_user, $this->db_pass, $this->db_host);
    }

    public function __destruct()
    {
        $this->db = null;
    }

    public function connectdb($db_name, $db_user, $db_pass, $db_host = "localhost")
    {
        try {
            $this->db = new \pdo("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        } catch (\pdoexception $e) {
            echo "database error: " . $e->getmessage();
            die();
        }
        $this->db->query('set names utf8');

        return $this;
    }

    protected function uploadFile($AllowFileExtension, $UploadFile) {

        $MaxFileSizeInBytes = 5242880;
        // Разрешение расширения файлов для загрузки
        //$AllowFileExtension = array('jpg', 'png', 'jpeg');
        // Оригинальное название файла
        $FileName = $_FILES[$UploadFile]['name'];
        $NewFileName = uniqid('', true);
        // Полный путь до временного файла
        $TempName = $_FILES[$UploadFile]['tmp_name'];
        // Папка где будут загружатся файлы
        $UploadDir = "../files/";
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
                            return $NewFileName.".".$FileExtension;
                        }
                    }
                }
            }
        }
    }

}