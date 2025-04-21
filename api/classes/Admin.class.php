<?php


namespace Sets;
require_once 'Operator.class.php';
require_once 'Metric.class.php';

class Admin extends Operator
{
    public function isAdmin() {
        if($this->role<=1) {
            return true;
        } else {
            return false;
        }
    }

    public function addMetric($name, $red, $orange, $yellow, $target)
    {
        if($this->isAdmin()) {
            $metric = new Metric($name, $red, $orange, $yellow, $target);
            $result = $metric->addMetric();
            if (!$result) {
                return false;
            }
            return $result;
        } else {
            throw new \Exception("Пользователь не является администратором", 10);
        }
    }

    public function getMetricsDashboard()
    {
        if($this->isAdmin()) {
            $metric = new Metric();
            $result = $metric->getDashboard();
            if (!$result) {
                return false;
            }
            return $result;
        } else {
            throw new \Exception("Пользователь не является администратором", 10);
        }
    }

}