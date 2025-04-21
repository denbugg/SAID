<?php

include './classes/Admin.class.php';
require_once './classes/AjaxRequest.class.php';

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