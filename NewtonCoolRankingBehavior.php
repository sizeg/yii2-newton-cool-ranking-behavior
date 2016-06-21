<?php

namespace sizeg\newtoncoolranking;

/**
 * Class NewtonCoolRankingBehavior
 * 
 * Rank Hotness With Newton's Law of Cooling
 * 
 * @author Dmitry Demin <sizemail@gmail.com>
 */
class NewtonCoolRankingBehavior extends \yii\base\Behavior
{

    /**
     * @var string the attribute that will receive current temperature value
     */
    public $rankAttribute = 'rank';

    /**
     * @var string the attribute that will receive time value of last temperature update
     */
    public $rankTimeAttribute = 'rankTime';

    /**
     * @var string the attribute that store temperature boost value
     */
    public $rankBoostAttribute = 'rankBoost';

    /**
     * @var int Initial temperature for the new item reflecting its hotness
     */
    public $initial = 1000;

    /**
     * @var int Temperature boost value
     * In case, when AR has no rankBoostAttribute attribute, boost with a default value
     */
    public $boost = 0;

    /**
     * @var int Cooling rate
     * How many hours it should take for the temperature to fall by roughly half
     */
    public $coolingRate = 150;

    /**
     * @var mixed In case, when the rankValue is `null` it will math this formula:
     * (Current T) = (Last recorded T) × exp( -(Cooling rate) × (Hours since last recorded T) )
     */
    public $rankValue;

    /**
     * @var mixed In case, when the value is `null`, the result of the PHP function [time()](http://php.net/manual/en/function.time.php)
     * will be used as value.
     */
    public $timeValue;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \yii\db\BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
        ];
    }

    /**
     * Set initial data on before insert
     */
    public function beforeInsert($event)
    {
        $this->owner->{$this->rankAttribute} = $this->getRankValue($this->initial, $event);
        $this->owner->{$this->rankTimeAttribute} = $this->getTimeValue($event);
    }

    /**
     * Calculate current temperature
     * (Current T) = (Last recorded T) × exp( -(Cooling rate) × (Hours since last recorded T) )
     * 
     * @param float $rank Last recorded temperature
     * @param int $time Time of last recorded temperature update
     * @return float
     */
    public function calculateRank($rank, $time)
    {
        $time = is_int($time) ? $time : strtotime($time);
        $diff = time() - $time;

        if ($diff > 0) {
            $hours = floor($diff / 60 / 60);
            $rank = $rank * exp(-(1 / $this->coolingRate) * $hours);
        }

        return $rank;
    }

    /**
     * Get current rank
     * @param float $up Temperature increment value
     * @param Event|null $event the event that triggers the current attribute updating.
     * @return float the temperature value
     */
    protected function getRankValue($up, $event)
    {
        if ($this->rankValue === null) {
            // Just return a value
            if ($this->owner->isNewRecord) {
                return $up;
            } else {
                $rank = $this->calculateRank($this->owner->{$this->rankAttribute}, $this->owner->{$this->rankTimeAttribute});
                return ($rank + $up);
            }
        } elseif ($this->rankValue instanceof Closure || is_array($this->rankValue) && is_callable($this->rankValue)) {
            return call_user_func($this->rankValue, $up, $event);
        }

        return $this->rankValue;
    }

    /**
     * In case, when the [[timeValue]] is `null`, the result of the PHP function [time()](http://php.net/manual/en/function.time.php)
     * will be used as value.
     * @return mixed
     */
    protected function getTimeValue($event)
    {
        if ($this->timeValue === null) {
            return time();
        } elseif ($this->timeValue instanceof Closure || is_array($this->timeValue) && is_callable($this->timeValue)) {
            return call_user_func($this->timeValue, $event);
        }

        return $this->timeValue;
    }

    /**
     * Updates a temperature with specified value
     *
     * ```php
     * $model->heat(10);
     * ```
     * @param float $up the heat up value to update
     * @throws InvalidCallException if owner is a new record.
     */
    public function heat($up)
    {
        /* @var $owner BaseActiveRecord */
        $owner = $this->owner;
        if ($owner->getIsNewRecord()) {
            throw new InvalidCallException('Updating the rank is not possible on a new record.');
        }
        $owner->{$this->rankAttribute} = $this->getRankValue($up, null);
        $owner->{$this->rankTimeAttribute} = $this->getTimeValue(null);
        $owner->update(false, [$this->rankAttribute, $this->rankTimeAttribute]);
    }

    /**
     * Boost a temperature with specified value
     * 
     * ```php
     * $model->boost();
     * ```
     * @param float $up the heat up value to update
     * In case when up value is null, checks AR for rankBoostAttribute or uses default boost value
     * @throws InvalidCallException if owner is a new record.
     */
    public function boost($up = null)
    {
        if ($up === null) {
            if ($this->owner->hasProperty($this->rankBoostAttribute)) {
                $up = $this->owner->{$this->rankBoostAttribute};
            } else {
                $up = $this->boost;
            }
        }
        $this->heat($up);
    }
}
