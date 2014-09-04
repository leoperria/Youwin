<?php

class PID {

    private $lastMicrotime = 0;
    private $deltaTime = 0;

    private $integral = 0;

    private $errorPrevious = 0;

    private $setPoint = 0;
    private $errorCurrent = 0;
    private $Kp = 0;
    private $Ti = 0;
    private $Td = 0;
    private $min = 0;
    private $max = 0;

    public function __construct(float $setPoint, float $Kp, float $Ti, float $Td, float $min, float $max)
    {
        if($this->min >= $this->max)
        {
            return false;
        }

        $this->lastMicrotime = microtime(true);

        $this->setPoint = $setPoint;
        $this->Kp = $Kp;
        $this->Ti = $Ti;
        $this->Td = $Td;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * This is considered the "Parallel" form of the PID Algorithm
     */
    public function run(float $actual, $deltaTime=false)
    {
        $this->errorCurrent = ($this->setPoint - $actual);

        if($deltaTime !== false)
        {
            $this->deltaTime = $deltaTime;
        }
        else
        {
            $this->deltaTime = microtime(true) - $this->lastMicrotime;
            $this->lastMicrotime = microtime(true);
        }

        $proportional = $this->CalculateProportional();
        $integral = $this->CalculateIntegral();
        $derivative = $this->CalculateDerivative();

        return ($proportional+$integral+$derivative);
    }

    private function CalculateProportional()
    {
        return ($this->Kp * $this->errorCurrent);
    }

    private function CalculateIntegral()
    {
        if($this->Ti == 0)
        {
            return 0;
        }

        if(($this->integral >= $this->min) && ($this->integral <= $this->max))
        {
            $this->integral = (($this->deltaTime * $this->errorCurrent) / $this->Ti) + $this->integral;
        }

        return $this->integral;
    }

    private function CalculateDerivative()
    {
        if($this->Td == 0)
        {
            return 0;
        }

        $delta = (($this->Td / $this->deltaTime) * ($this->errorPrevious - $this->errorCurrent));
        $this->errorPrevious = $this->errorCurrent;

        return $delta;
    }

    public function setMinMax(float $min, float $max)
    {
        $this->min = $min;
        $this->max = $max;
    }
}