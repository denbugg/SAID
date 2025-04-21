<?php


namespace Sets;
use PDO;

require_once 'Action.class.php';
require_once 'User.class.php';

class Donor extends Action
{

    protected $needs;

    public function sendToBase($form, $headFio, $headPhone, $headEmail, $contactFio, $contactPhone, $contactEmail, $fullname,
                               $shortname, $adress, $mail, $passport, $phone, $email, $inn, $kpp, $ogrn, $okpo, $okved, $bank, $account, $bik)
    {
        $user = new User();
        $user_exists = $user->getUser($contactEmail);

        if ($user_exists) {
            throw new \Exception("Такой пользователь уже существует: " . $contactEmail, 1);
        }

        $AllowFileExtension = array('pdf', 'PDF');
        if($_FILES['articles']) {
            $articles = $this->uploadFile($AllowFileExtension, 'articles');
        } else {
            $articles = null;
        }
        if($_FILES['decision']) {
            $decision = $this->uploadFile($AllowFileExtension, 'decision');
        } else {
            $decision = null;
        }
        if($_FILES['egrul']) {
            $egrul = $this->uploadFile($AllowFileExtension, 'egrul');
        } else {
            $egrul = null;
        }
        $headFio = ($headFio === '') ? null : $headFio;
        $headPhone = ($headPhone === '') ? null : $headPhone;
        $headEmail = ($headEmail === '') ? null : $headEmail;
        $fullname = ($fullname === '') ? null : $fullname;
        $shortname = ($shortname === '') ? null : $shortname;
        $adress = ($adress === '') ? null : $adress;
        $passport = ($passport === '') ? null : $passport;
        $passport = ($passport === 'undefined') ? null : $passport;
        $phone = ($phone === '') ? null : $phone;
        $email = ($email === '') ? null : $email;
        $inn = ($inn === '') ? null : $inn;
        $kpp = ($kpp === '') ? null : $kpp;
        $ogrn = ($ogrn === '') ? null : $ogrn;
        $okpo = ($okpo === '') ? null : $okpo;
        $okved = ($okved === '') ? null : $okved;
        $bank = ($bank === '') ? null : $bank;
        $account = ($account === '') ? null : $account;
        $bik = ($bik === '') ? null : $bik;
        $query = "INSERT INTO `donors` (`form`, `headFio`, `headPhone`, `headEmail`, `contactFio`, 
            `contactPhone`, `contactEmail`, `articles`, `decision`, `egrul`, `fullname`, `shortname`, `adress`, `mail`, 
            `passport`,`phone`,`email`,`inn`,`kpp`,`ogrn`, `okpo`, `okved`, `bank`, `account`, `bik`) 
            VALUES (:form, :headFio, :headPhone, :headEmail, :contactFio, :contactPhone, :contactEmail, 
            :articles, :decision, :egrul, :fullname, :shortname, :adress, :mail, :passport, :phone, :email, :inn,
            :kpp, :ogrn, :okpo, :okved, :bank, :account, :bik);";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $result = $sth->execute(
            array(
                ":form" => $form,
                ":headFio" => $headFio,
                ":headPhone" => $headPhone,
                ":headEmail" => $headEmail,
                ":contactFio" => $contactFio,
                ":contactPhone" => $contactPhone,
                ":contactEmail" => $contactEmail,
                ":articles" => $articles,
                ":decision" => $decision,
                ":egrul" => $egrul,
                ":fullname" => $fullname,
                ":shortname" => $shortname,
                ":adress" => $adress,
                ":mail" => $mail,
                ":passport" => $passport,
                ":phone" => $phone,
                ":email" => $email,
                ":inn" => $inn,
                ":kpp" => $kpp,
                ":ogrn" => $ogrn,
                ":okpo" => $okpo,
                ":okved" => $okved,
                ":bank" => $bank,
                ":account" => $account,
                ":bik" => $bik,
            )
        );
        if (!$result) {
            return false;
        }
        return $result;
    }

    public function getListForApprove() {
        $query = "SELECT * FROM `donors` WHERE `userid` is NULL";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(

            )
        );
        $result = $sth->fetchAll();
        if (!$result) {
            return false;
        }
        return $result;
    }

    public function formDeliver($list, $comment, $warehouse, $userid=null, $date) {
        $this->getNeeds();
        $comment = ($comment === '') ? null : $comment;
        $warehouse = ($warehouse === '') ? null : $warehouse;
        $warehouse = ($warehouse === 'undefined') ? null : $warehouse;
        $userid = ($userid === '') ? null : $userid;
        $userid = ($userid === 'undefined') ? null : $userid;
        $query = "SELECT concat(`values`,`date`) as `values_date`, sum(`quantity`) as `sum_quantity` FROM `orders` WHERE 1 GROUP BY `date`, `values`;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(

            )
        );
        $bd_list = $sth->fetchAll(PDO::FETCH_KEY_PAIR);

        if(!$userid || is_null($userid)) {
            $user = new User();
            $userid = $user->isAuthorized();
        }

        if($userid) {
            $query = "INSERT INTO `delivers` (`userid`, `comment`, `warehouse_id`, `date`) VALUES (:userid, :comment, :warehouse, :date);";
            $sth = $this->db->prepare($query);
            //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
            $result = $sth->execute(
                array(
                    ":userid" => $userid,
                    ":comment" => $comment,
                    ":warehouse" => $warehouse,
                    ":date" => $date
                )
            );
            $deliver_id = $this->db->lastInsertId();
            if (!$deliver_id) {
                return false;
            }
            foreach ($list->value as $date) {
                foreach($date[1]->value as $type) {
                    //print_r($type);
                    foreach($type[1]->value as $type_list) {
                        $day = date_create($date[0]);
                        $day_str = date_format($day,'Y-m-d');
                        $quantity = $type_list[1];
                        $need_quantity = $this->needs[$type_list[0]];
                        do {
                            if (array_key_exists($type_list[0] . $day_str, $bd_list)) {
                                $has_quantity = $bd_list[$type_list[0] . $day_str];
                            } else {
                                $has_quantity = 0;
                            }
                            if($has_quantity<$need_quantity) {
                                $query = "INSERT INTO `orders` (`type`, `values`, `quantity`, `date`, `deliverid`) 
                                    VALUES (:type, :values, :quantity, :date, :deliverid);";
                                $sth = $this->db->prepare($query);
                                //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
                                if($quantity>=$need_quantity - $has_quantity) {
                                    $quantity_to_base = $need_quantity - $has_quantity;
                                } else {
                                    //$quantity_to_base = $quantity - $has_quantity;
                                    $quantity_to_base = $quantity;
                                }
                                $result = $sth->execute(
                                    array(
                                        ":type" => $type[0],
                                        ":values" => $type_list[0],
                                        ":quantity" => $quantity_to_base,
                                        ":date" => $day_str,
                                        ":deliverid" => $deliver_id,
                                    )
                                );
                                if($result) {
                                    $quantity = $quantity - $quantity_to_base;
                                }
                            }
                            $day = date_add($day, date_interval_create_from_date_string("1 day"));
                            $day_str = date_format($day,'Y-m-d');

                        } while ($quantity > 0);
                        if (!$result) {
                            return false;
                        }
                    }
                }
            }
            if($result) {
                return true;
            }
        } else {
            return false;
        }
    }

    public function getDeliversList($date) {
        if($date=='false') {
            $query = "select `orders`.`date`,`values`,`deliverid`, `quantity`, `type`, `shortname`, `contactFio`,`delivers`.`date` as `deliverdate` from delivers left join orders on delivers.id=orders.deliverid left join donors 
            using (userid) WHERE 1 order by delivers.id asc, orders.date asc;";
            $sth = $this->db->prepare($query);
            //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
            $sth->execute(
                array(

                )
            );
        } else {
            $query = "select `orders`.`date`,`values`,`deliverid`, `quantity`, `type`, `shortname`, `contactFio`,`delivers`.`date` as `deliverdate` from delivers left join orders on delivers.id=orders.deliverid left join donors 
            using (userid) WHERE `orders`.`date`=:date order by delivers.id asc, orders.date asc;";
            $sth = $this->db->prepare($query);
            //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
            $sth->execute(
                array(
                    ":date" => $date,
                )
            );
        }
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        if (!$result) {
            return false;
        }
        /*echo json_encode($result);
        die();*/
        return json_encode($result);
    }

    public function getRemains($date) {
        $day = date_create($date);
        $day_str = date_format($day,'Y-m-d');
        $week = date_add($day, date_interval_create_from_date_string("7 days"));
        $week_str = date_format($week,'Y-m-d');
        //$query = "SELECT *, sum(`quantity`) as `sum_quantity` FROM `orders` WHERE `date`>=:day AND `date`<:week GROUP BY `values`;";
        $query = "SELECT *, `need`*7-IF(`sum_quantity` IS NULL,0,`sum_quantity`) as `week_need` FROM (SELECT *, 'product' 
            as `need_type` FROM `needs` UNION ALL SELECT *, 'gigiena' as `need_type` FROM `gigiena_needs`) t2 
            LEFT JOIN (SELECT `orders`.`values`, sum(`quantity`) as `sum_quantity` FROM `orders` WHERE `date`>=:day 
            AND `date`<:week GROUP BY `values`) t1 ON `t1`.`values`=`t2`.`type`";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":day" => $day_str,
                ":week" => $week_str,
            )
        );
        $result = $sth->fetchAll();
        if (!$result) {
            return false;
        }
        return $result;
    }

    public function getCalendar($type, $date) {
        //$today = date('Y-m-d');
        $today = date($date);
        $begin_day = date_create($today);
        $end_day = date_create($today);
        $end_day = $end_day->modify( '+18 days' );
        $interval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($begin_day, $interval ,$end_day);
        $query_dates = "";
        $i=1;
        foreach($daterange as $date){
            if($i==1) {
                $query_dates .= "select '". $date->format('Y-m-d') . "' as `dates` union all ";
            } else if($i==18) {
                $query_dates .= "select '". $date->format('Y-m-d') . "'";
            } else {
                $query_dates .= "select '". $date->format('Y-m-d') . "' union all ";
            }
            $i++;
        }
        switch ($type) {
            case "product":
                /*$query = "select `dates`, sum(`remain`) as `sum_remain` from (select `dates`,`needs`.`type`, `needs`.`need`,
                    `orders`.`quantity`, `orders`.`values`, `orders`.`date`, sum(`orders`.`quantity`) as `sum_quantity`, 
                    (`needs`.`need`-if(sum(`orders`.`quantity`) IS NULL,0,sum(`orders`.`quantity`))) as `remain` from (" . $query_dates .
                    ") t join `needs` on 1 left join `orders` on `needs`.`type`=`orders`.`values` AND `dates`=`orders`.`date` 
                    group by `needs`.`type`,`dates`) t2 group by `dates`;";*/
                $query = "select `dates`, max(`remain_coeff`) as `max_remain_coeff`
                    from (select *, `remain`/`need` as `remain_coeff` from (select `dates`,`needs`.`type`, `needs`.`need`, 
                    `orders`.`quantity`, `orders`.`values`, `orders`.`date`, sum(`orders`.`quantity`) as `sum_quantity`, 
                    (`needs`.`need`-if(sum(`orders`.`quantity`) IS NULL,0,sum(`orders`.`quantity`))) as `remain` from 
                    (" . $query_dates . ") t join `needs` on 1 left join `orders` on `needs`.`type`=`orders`.`values` 
                    AND `dates`=`orders`.`date` group by `needs`.`type`,`dates`) t2) t3 group by `dates`;";
                break;
            case "gigiena":
                /*$query = "select `dates`, sum(`remain`) as `sum_remain` from (select `dates`,`gigiena_needs`.`type`, `gigiena_needs`.`need`,
                    `orders`.`quantity`, `orders`.`values`, `orders`.`date`, sum(`orders`.`quantity`) as `sum_quantity`, 
                    (`gigiena_needs`.`need`-if(sum(`orders`.`quantity`) IS NULL,0,sum(`orders`.`quantity`))) as `remain` from (" . $query_dates .
                    ") t join `gigiena_needs` on 1 left join `orders` on `gigiena_needs`.`type`=`orders`.`values` AND `dates`=`orders`.`date` 
                    group by `gigiena_needs`.`type`,`dates`) t2 group by `dates`;";*/
                $query = "select `dates`, max(`remain_coeff`) as `max_remain_coeff`
                    from (select *, `remain`/`need` as `remain_coeff` from (select `dates`,`gigiena_needs`.`type`, `gigiena_needs`.`need`, 
                    `orders`.`quantity`, `orders`.`values`, `orders`.`date`, sum(`orders`.`quantity`) as `sum_quantity`, 
                    (`gigiena_needs`.`need`-if(sum(`orders`.`quantity`) IS NULL,0,sum(`orders`.`quantity`))) as `remain` from 
                    (" . $query_dates . ") t join `gigiena_needs` on 1 left join `orders` on `gigiena_needs`.`type`=`orders`.`values` 
                    AND `dates`=`orders`.`date` group by `gigiena_needs`.`type`,`dates`) t2) t3 group by `dates`;";
                break;
        }
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(

            )
        );
        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!$result) {
            return false;
        }
        return $result;
    }

    public function getRemainForDay($type, $date) {
        switch ($type) {
            case "product":
                $query = "select `type`, `remain` from (select `dates`,`needs`.`type`, `needs`.`need`, 
            `orders`.`quantity`, `orders`.`values`, `orders`.`date`, sum(`orders`.`quantity`) as `sum_quantity`, 
            (`needs`.`need`-if(sum(`orders`.`quantity`) IS NULL,0,sum(`orders`.`quantity`))) as `remain` from 
            (select :date as `dates`) t join `needs` on 1 left join `orders` on `needs`.`type`=`orders`.`values` 
            AND `dates`=`orders`.`date` group by `needs`.`type`,`dates`) t2;";
                break;
            case "gigiena":
                $query = "select `type`, `remain` from (select `dates`,`gigiena_needs`.`type`, `gigiena_needs`.`need`, 
            `orders`.`quantity`, `orders`.`values`, `orders`.`date`, sum(`orders`.`quantity`) as `sum_quantity`, 
            (`gigiena_needs`.`need`-if(sum(`orders`.`quantity`) IS NULL,0,sum(`orders`.`quantity`))) as `remain` from 
            (select :date as `dates`) t join `gigiena_needs` on 1 left join `orders` on `gigiena_needs`.`type`=`orders`.`values` 
            AND `dates`=`orders`.`date` group by `gigiena_needs`.`type`,`dates`) t2;";
                break;
        }
        /*$query = "select `type`, `remain` from (select `dates`,`needs`.`type`, `needs`.`need`,
            `orders`.`quantity`, `orders`.`values`, `orders`.`date`, sum(`orders`.`quantity`) as `sum_quantity`, 
            (`needs`.`need`-if(sum(`orders`.`quantity`) IS NULL,0,sum(`orders`.`quantity`))) as `remain` from 
            (select :date as `dates`) t join `needs` on 1 left join `orders` on `needs`.`type`=`orders`.`values` 
            AND `dates`=`orders`.`date` group by `needs`.`type`,`dates`) t2;";*/
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":date" => $date
            )
        );
        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!$result) {
            return false;
        }
        return $result;
    }

    public function deleteDeliver($deliverid) {
        $query = "SELECT `orders`.`type`, `orders`.`values`, `orders`.`quantity`, `orders`.`deliverid`, `delivers`.`date` 
            FROM `orders` LEFT JOIN `delivers` ON `delivers`.`id`=`orders`.`deliverid` WHERE `deliverid`=:deliverid group by `values`;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":deliverid" => $deliverid,
            )
        );
        $values = $sth->fetchAll();
        if (!$values) {
            return false;
        }
        $query = "DELETE FROM `orders` WHERE `deliverid`=:deliverid;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":deliverid" => $deliverid,
            )
        );
        $result = $sth->rowCount();
        if (!$result) {
            return false;
        }
        $query = "DELETE FROM `delivers` WHERE `id`=:deliverid;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":deliverid" => $deliverid,
            )
        );
        $result = $sth->rowCount();
        if (!$result) {
            return false;
        }
        foreach ($values as $value) {
            $result = $this->reSortOrders($value);
        }
        return true;
    }

    protected function reSortOrders($value) {
        $query = "SELECT `orders`.`type`, `orders`.`values`, `delivers`.`date`, SUM(`quantity`) as `sum_quantity`, `orders`.`deliverid` FROM 
            `orders` LEFT JOIN `delivers` ON `delivers`.`id`=`orders`.`deliverid` WHERE `values`=:value AND `delivers`.`date`>=:date GROUP BY `deliverid`;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":value" => $value['values'],
                ":date" => $value['date']
            )
        );
        $values = $sth->fetchAll();
        if (!$values) {
            return false;
        }
        $query = "DELETE FROM `orders` WHERE `values`=:value AND `date`>=:date";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":value" => $value['values'],
                ":date" => $value['date']
            )
        );
        foreach ($values as $row) {
            $result = $this->addOrder($row['values'], $row['sum_quantity'], $row['date'], $row['deliverid'], $row['type']);
        }
        return $result;
    }

    protected function addOrder($value, $quantity, $date, $deliverid, $type) {
        $query = "SELECT concat(`values`,`date`) as `values_date`, sum(`quantity`) as `sum_quantity` FROM `orders` 
            WHERE `date`>=:date AND `values`=:values GROUP BY `date`";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":date" => $date,
                ":values" => $value
            )
        );
        $bd_list = $sth->fetchAll(PDO::FETCH_KEY_PAIR);

        $day = date_create($date);
        $day_str = date_format($day,'Y-m-d');
        $need_quantity = $this->needs[$value];
        do {
            if (array_key_exists($value . $day_str, $bd_list)) {
                $has_quantity = $bd_list[$value . $day_str];
            } else {
                $has_quantity = 0;
            }
            if($has_quantity<$need_quantity) {
                $query = "INSERT INTO `orders` (`type`, `values`, `quantity`, `date`, `deliverid`) 
                                    VALUES (:type, :values, :quantity, :date, :deliverid);";
                $sth = $this->db->prepare($query);
                //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
                if($quantity>=$need_quantity - $has_quantity) {
                    $quantity_to_base = $need_quantity - $has_quantity;
                } else {
                    //$quantity_to_base = $quantity - $has_quantity;
                    $quantity_to_base = $quantity;
                }
                $result = $sth->execute(
                    array(
                        ":type" => $type,
                        ":values" => $value,
                        ":quantity" => $quantity_to_base,
                        ":date" => $day_str,
                        ":deliverid" => $deliverid,
                    )
                );
                if($result) {
                    $quantity = $quantity - $quantity_to_base;
                }
            }
            $day = date_add($day, date_interval_create_from_date_string("1 day"));
            $day_str = date_format($day,'Y-m-d');
        } while ($quantity > 0);
        if (!$result) {
            return false;
        }
        return true;
    }

    public function reSortAllOrders($date) {
        foreach ($this->needs as $key => $value){
            $values = array(
                "values" => $key,
                "date" => $date,
            );
            $result = $this->reSortOrders($values);
        }
        return true;
    }

    public function matchDeliver($type, $quantity, $date) {
        $day = date_create($date);
        $day_str = date_format($day,'Y-m-d');
        $week = date_add($day, date_interval_create_from_date_string("7 days"));
        $week_str = date_format($week,'Y-m-d');
        $deliver_list = array();
        $query = "SELECT * FROM (SELECT `t1`.*, truncate((`need`*7 - `sum_quantity`)/(`need`*7),1) as `week_needs_coeff`, 
            `need`, `in_pallet`, `cost`, (`need`*7 - `sum_quantity`) as `week_needs` FROM 
            (SELECT `orders`.*, sum(`quantity`) as `sum_quantity` FROM `orders` WHERE `date`>=:day AND `date`<:week
             GROUP BY `values`) t1 LEFT JOIN `needs` on `needs`.`type`=`t1`.`values`) t2 where `week_needs_coeff` 
             IS NOT NULL order by `week_needs_coeff` DESC, `week_needs` DESC, `cost` DESC;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":day" => $day_str,
                ":week" => $week_str,
            )
        );
        $result = $sth->fetchAll();
        if (!$result) {
            return false;
        }
        switch ($type) {
            case "cost":
                foreach ($result as $item) {
                    $week_needs= $item['week_needs'];
                    $cost = $item['cost'];
                    if(($week_needs * $cost) < $quantity) {         // суммы достаточно для покрытия недельной потребности
                        $deliver_list[$item['values']] = $week_needs;
                        $quantity = $quantity - $week_needs * $cost;
                    } else {  // суммы достаточно только для части недельной потребности, остальные позиции можно не проверять
                        $deliver_list[$item['values']] = floor($quantity / $cost);
                        return $deliver_list;
                    }
                }
                break;
            case "pallet":
                foreach ($result as $item) {
                    $week_needs= $item['week_needs'];
                    $in_pallet = $item['in_pallet'];
                    if(ceil($week_needs / $in_pallet) < $quantity) {         // кол-ва паллет достаточно для покрытия недельной потребности
                        $deliver_list[$item['values']] = $week_needs;
                        $quantity = $quantity - ceil($week_needs / $in_pallet);
                    } else {  // кол-ва паллет достаточно только для части недельной потребности, остальные позиции можно не проверять
                        $deliver_list[$item['values']] = floor($quantity * $in_pallet);
                        return $deliver_list;
                    }
                }
                break;
        }
        return $deliver_list;
    }

    public function changeDeliver($list, $deliverid, $date) {
        $query = "DELETE FROM `orders` WHERE `deliverid`=:deliverid;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":deliverid" => $deliverid,
            )
        );
        $result = $sth->rowCount();
        if (!$result) {
            return false;
        }
        foreach($list->value as $type) {
            // $type[0] - тип набора
            //print_r($type);
            foreach ($type[1]->value as $value) {
                // $value - массив, где 0 - наименование товара, 1 - количество
                if ($value[1]) { // если ненулевое значение -> добавляем в базу
                    $query = "INSERT INTO `orders` (`type`, `values`, `quantity`, `date`, `deliverid`)
                                    VALUES (:type, :values, :quantity, :date, :deliverid);";
                    $sth = $this->db->prepare($query);
                    $result = $sth->execute(
                        array(
                            ":type" => $type[0],
                            ":values" => $value[0],
                            ":quantity" => $value[1],
                            ":date" => $date,
                            ":deliverid" => $deliverid,
                        )
                    );
                }
            }
        }
        if(!$result) {
            return false;
        }
        $result = $this->reSortAllOrders($date);
        return true;
    }

    public function getNeeds() {
        $query = "SELECT `type`, `need` FROM `needs` UNION ALL (SELECT `type`, `need` FROM `gigiena_needs`);";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(

            )
        );
        $this->needs = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getRealRemains() {
        $query = "SELECT `type`, `remain` FROM `bogdan`";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(

            )
        );
        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
        return $result;
    }

    public function saveRealRemains($remains) {
        $values = str_repeat('?,', count($remains[0]) - 1) . '?';
        $query = "INSERT INTO bogdan (type, remain) VALUES " .
            // repeat the (?,?) sequence for each row
            str_repeat("($values),", count($remains) - 1) . "($values)" . " ON DUPLICATE KEY UPDATE 
            remain=VALUES(remain)";
        $sth = $this->db->prepare($query);
        $sth->execute(array_merge(...$remains));
        return true;
    }

    public function getDoneSets() {
        $query = "SELECT `type`, `qty` FROM `bogdan_done_sets`";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(

            )
        );
        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
        return $result;
    }

    public function saveDoneSets($product, $gigiena) {
        $query = "UPDATE `bogdan_done_sets` t1 JOIN `bogdan_done_sets` t2 ON t1.type = 'product' AND t2.type = 'gigiena'
            SET t1.qty = :product, t2.qty = :gigiena;";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":product" => $product,
                ":gigiena" => $gigiena,
            )
        );
        return true;
    }

    public function getIrregularNeeds($type) {
        $query = "SELECT *, `need`-`in_deliver` as `total_need` FROM `irregular_needs` WHERE type=:type";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(
                ":type" => $type,
            )
        );
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function saveIrregularDeliver($delivers, $contact, $date, $type) {
        $query = "INSERT INTO `irregular_delivers` (`contact`, `date`) VALUES (:contact, :date);";
        $sth = $this->db->prepare($query);
        $sth->execute(
            array(
                ":contact" => $contact,
                ":date" => $date,
            )
        );
        $id = $this->db->lastInsertId();
        if($id) {
            $deliver_orders = $delivers;
            foreach($deliver_orders as &$value) {
                $value[2] = $type;
                $value[3] = $id;
            }
            $values = str_repeat('?,', count($deliver_orders[0]) - 1) . '?';
            $query = "INSERT INTO irregular_orders (value, qty, type, deliverid) VALUES " .
                // repeat the (?,?) sequence for each row
                str_repeat("($values),", count($deliver_orders) - 1) . "($values)";
            $sth = $this->db->prepare($query);
            $result=$sth->execute(array_merge(...$deliver_orders));
            if($result) {
                $values = str_repeat('?,', count($delivers[0]) - 1) . '?';
                $query = "INSERT INTO irregular_needs (value, in_deliver) VALUES " .
                    // repeat the (?,?) sequence for each row
                    str_repeat("($values),", count($delivers) - 1) . "($values)" . " ON DUPLICATE KEY UPDATE 
                value=VALUES(value), in_deliver=in_deliver + VALUES(in_deliver)";
                $sth = $this->db->prepare($query);
                $result=$sth->execute(array_merge(...$delivers));
                return $result;
            } else {
                return false;
            }
        }
    }

    public function test()
    {
        $subject = "Успешно пройдена верификация благотворителя";
        $template = "event";
        $message = "<p>Здравствуйте!</p><p>Ваша заявка успешно прошла модерацию. Для доступа в закрытую часть используйте
           этот адрес электронный почты в качестве логина</p><p>Ваш пароль: 111 </p>";

        $mail = new Mail('5830650@mail.ru', $subject, $message, $template);
        $mail->pushMailPhpMailer();
        return true;
    }

}