<?php


namespace Sets;
require_once 'Action.class.php';

class Metric extends Action
{
    protected $name;
    protected $red;
    protected $orange;
    protected $yellow;
    protected $target;

    public function __construct($name=null, $red=null, $orange=null, $yellow=null, $target=null)
    {
        parent::__construct();
        $this->name = $name;
        $this->red = $red;
        $this->orange = $orange;
        $this->yellow = $yellow;
        $this->target = $target;
    }

    public function addMetric() {
        $query = "INSERT INTO `metrics`(`name`, `red`, `orange`, `yellow`, `target`) VALUES (:name, :red, :orange,
            :yellow, :target)";
        $sth = $this->db->prepare($query);
        $result = $sth->execute(
            array(
                ":name" => $this->name,
                ":red" => $this->red,
                ":orange" => $this->orange,
                ":yellow" => $this->yellow,
                ":target" => $this->target,
            )
        );
        if (!$result) {
            return false;
        }
        return $result;
    }

    public function getList() {
        $query = "SELECT * FROM `metrics` WHERE 1";
        $sth = $this->db->prepare($query);
        $sth->execute(
            array(

            )
        );
        $result = $sth->fetchAll();
        return $result;
    }

    public function saveMetricValues($metrics) {
        $values = str_repeat('?,', count($metrics[0]) - 1) . '?';
        $query = "INSERT INTO metric_values (metric_id, value) VALUES " .
            // repeat the (?,?) sequence for each row
            str_repeat("($values),", count($metrics) - 1) . "($values)" . " ON DUPLICATE KEY UPDATE 
            metric_id=VALUES(metric_id), value=VALUES(value)";
        $sth = $this->db->prepare($query);
        $sth->execute(array_merge(...$metrics));
        return true;
    }

    public function getDashboard() {
        $query = "SELECT *, if(`value`>`yellow`,'green',if(`value`>`orange`,'yellow',if(`value`>`red`,'orange','red'))) 
            as `color` FROM `metric_values` left join `metrics` on `metric_values`.`metric_id`=`metrics`.`id` WHERE 1;";
        $sth = $this->db->prepare($query);
        $sth->execute(
            array(

            )
        );
        $result = $sth->fetchAll();
        return $result;
    }
}