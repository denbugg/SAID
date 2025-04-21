<?php

include './classes/Donor.class.php';
include './classes/AjaxRequest.class.php';
require_once './classes/User.class.php';

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
        //"delete_deliver" => "deleteDeliver",
        //"re_sort_all_orders" => "reSortAllOrders",
        "match_deliver" => "matchDeliver",
        "get_real_remains" => "getRealRemains",
        "save_real_remains" => "saveRealRemains",
        "get_done_sets" => "getDoneSets",
        "save_done_sets" => "saveDoneSets",
        // -----Поставки по запросу:------
        "get_irregular_needs" =>"getIrregularNeeds",
        "save_irregular_deliver" => "saveIrregularDeliver",
        //"change_deliver" => "changeDeliver",
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
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка вставки благотворителя в базу";
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
            $result=$donor->formDeliver($list, $comment, $warehouse, $userid, $date);
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

    public function deleteDeliver() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $deliverid = $this->getRequestParam('deliverid');
            $result = $donor->deleteDeliver($deliverid);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка удаления доставки";
            }
        }
    }

    public function reSortAllOrders() {
        //if($this->checkMethod()) {
        $donor = new Sets\Donor();
        $date = $this->getRequestParam('date');
        $result = $donor->reSortAllOrders($date);
        if($result) {
            $this->status = "ok";
            $this->setResponse("response", $result);
        } else {
            $this->status = "no";
            $this->message = "Ошибка пересортировки доставок";
        }
        //}
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

    public function changeDeliver() {
        if($this->checkMethod()) {
            $donor = new Sets\Donor();
            $list =  json_decode($this->getRequestParam('list'));
            $deliverid = $this->getRequestParam('deliverid');
            $date = $this->getRequestParam('date');
            $result = $donor->changeDeliver($list, $deliverid, $date);
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка изменения доставки";
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

    public function getdoneSets() {
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
                $operator = new Sets\Donor();
                $product = $this->getRequestParam('product');
                $gigiena = $this->getRequestParam('gigiena');
                $result = $operator->saveDoneSets($product, $gigiena);

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
                $this->message = "Ошибка получения списка нерегулярных поставко";
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
            $result=$donor->test();
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка формирования списка доставки";
            }

    }

}
