<?php


namespace Sets;
use PDO;
require_once 'User.class.php';


class Operator extends User
{
    protected $needs;

    public function __construct()
    {
        parent::__construct();
        $userid = $this->isAuthorized();

        if (!$userid) {
            throw new \Exception("Пользователь не авторизован", 1);
        }

        $query = "select * from users where userid = :userid limit 1";
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
        $this->role = $row['role'];
    }

    public function isOperator() {
        if($this->role<=3) {
            return true;
        } else {
            return false;
        }
    }

    public function isIrregularOperator() {
        if($this->role==5 ) {
            $userid = $this->isAuthorized();

            if (!$userid) {
                throw new \Exception("Пользователь не авторизован", 1);
            }

            $query = "select * from irregular_admins where userid = :userid limit 1";
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
            return $row['section'];
        } else if($this->role==1) {
            return 'super';
        } else {
            return false;
        }
    }

    public function getWarehouses() {
        if($this->isOperator()) {
            $query = "select * from warehouses where 1";
            $sth = $this->db->prepare($query);
            $sth->execute(
                array(
                    ":userid" => $this->userid
                )
            );
            $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
            if (!$result) {
                return false;
            }
            return $result;
        } else {
            throw new \Exception("Пользователь не является оператором", 1);
        }
    }

    public function getDonors() {
        if($this->isOperator()) {
            $query = "select * from donors where `form`='склад' order by `shortname` asc";
            $sth = $this->db->prepare($query);
            $sth->execute(
                array(
                    ":userid" => $this->userid
                )
            );
            $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
            if (!$result) {
                return false;
            }
            return $result;
        } else {
            throw new \Exception("Пользователь не является оператором", 1);
        }
    }

    public function deleteDeliver($deliverid) {
        if($this->isOperator()) {
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
        } else {
            throw new \Exception("Пользователь не является оператором", 1);
        }
    }

    protected function reSortOrders($value) {
        if(empty($this->needs)) {
            $this->getNeeds();
        }
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
        if(empty($this->needs)) {
            $this->getNeeds();
        }
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
        if(empty($this->needs)) {
            $this->getNeeds();
        }
        foreach ($this->needs as $key => $value){
            $values = array(
                "values" => $key,
                "date" => $date,
            );
            $result = $this->reSortOrders($values);
        }
        return $result;
    }

    public function changeDeliver($list, $deliverid, $date) {
        if($this->isOperator()) {
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
            foreach ($list->value as $type) {
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
            if (!$result) {
                return false;
            }
            $result = $this->reSortAllOrders($date);
            return true;
        }
    }

    public function addWarehouse($shortname) {
        if($this->isOperator()) {
            $str = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            do {
                $userid = substr(str_shuffle($str), 0, 28);
            } while ($this->checkUniqueUserid($userid));
            $query = "INSERT INTO `donors`(`form`,`shortname`,`userid`) 
                VALUES ('Склад',:shortname, :userid)";
            $sth = $this->db->prepare($query);
            $result = $sth->execute(
                array(
                    ":shortname" => $shortname,
                    ":userid" => $userid
                )
            );
            return $result;
        } else {
            throw new \Exception("Пользователь не является оператором", 1);
        }
    }

    protected function getNeeds() {
        $query = "SELECT `type`, `need` FROM `needs` UNION ALL (SELECT `type`, `need` FROM `gigiena_needs`);";
        $sth = $this->db->prepare($query);
        //$sth->bindParam(':of', $limit, PDO::PARAM_INT);
        $sth->execute(
            array(

            )
        );
        $this->needs = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getMetricList()
    {
        if($this->isOperator()) {
            $metric = new Metric();
            $result = $metric->getList();
            if (!$result) {
                return false;
            }
            return $result;
        } else {
            throw new \Exception("Пользователь не является оператором", 1);
        }
    }

    public function saveMetricValues($metrics)
    {
        if($this->isOperator()) {
            $metric = new Metric();
            $result = $metric->saveMetricValues($metrics);
            if (!$result) {
                return false;
            }
            return $result;
        } else {
            throw new \Exception("Пользователь не является оператором", 1);
        }
    }

    public function saveIrregularNeeds($needs)
    {
        if($this->isIrregularOperator()) {
            $values = str_repeat('?,', count($needs[0]) - 1) . '?';
            $query = "INSERT INTO irregular_needs (value, need) VALUES " .
                // repeat the (?,?) sequence for each row
                str_repeat("($values),", count($needs) - 1) . "($values)" . " ON DUPLICATE KEY UPDATE 
                value=VALUES(value), need=VALUES(need)";
            $sth = $this->db->prepare($query);
            $result=$sth->execute(array_merge(...$needs));
            if (!$result) {
                return false;
            }
            return $result;
        } else {
            throw new \Exception("Пользователь не является оператором нерегулярных поставок", 11);
        }
    }

}