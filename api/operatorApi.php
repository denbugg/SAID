<?php

include './classes/Operator.class.php';
require_once './classes/AjaxRequest.class.php';
require_once './classes/User.class.php';

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
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения списка складов";
            }
        }
    }

    public function getDonors() {
        if($this->checkMethod()) {
            try {
                $operator = new Sets\Operator();
                $result = $operator->getDonors();
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка получения списка доноров";
            }
        }
    }

    public function deleteDeliver() {
        if($this->checkMethod()) {
            $deliverid = $this->getRequestParam('deliverid');
            try {
                $operator = new Sets\Operator();
                $result = $operator->deleteDeliver($deliverid);
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка удаления доставки";
            }
        }
    }

    public function changeDeliver() {
        if($this->checkMethod()) {
            $list =  json_decode($this->getRequestParam('list'));
            $deliverid = $this->getRequestParam('deliverid');
            $date = $this->getRequestParam('date');
            try {
                $operator = new Sets\Operator();
                $result = $operator->changeDeliver($list, $deliverid, $date);
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка изменения доставки";
            }
        }
    }

    public function addWarehouse() {
        if($this->checkMethod()) {
            $name = $this->getRequestParam('name');
            try {
                $operator = new Sets\Operator();
                $result = $operator->addWarehouse($name);
            } catch (\Exception $e) {
                $this->setFieldError("main", $e->getMessage());
                return;
            }
            if($result) {
                $this->status = "ok";
                $this->setResponse("response", $result);
            } else {
                $this->status = "no";
                $this->message = "Ошибка добавления Поставщика";
            }
        }
    }

    public function reSortAllOrders() {
        //if($this->checkMethod()) {
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
        //}
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