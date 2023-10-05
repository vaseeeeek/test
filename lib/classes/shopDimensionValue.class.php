<?php
/**
 * Class shopDimensionValue
 * @property-read string $value
 * @property-read string $unit
 *
 * @property-read string $type
 * @property-read string $value_base_unit
 *
 * @property-read string $unit_name
 * @property-read string $units
 *
 * @property-read string $html
 * @property-read string $compare
 */
class shopDimensionValue implements ArrayAccess
{
    private $value;
    private $unit;
    private $type;
    private $units;
    private $value_base_unit;
    private $base_code;
    private $format = false;

    public function __construct($row)
    {
        foreach ($row as $field => $value) {
            $this->{$field} = $value;
        }
    }

    public function __set($field, $value)
    {
        return $this->{$field} = $value;
    }

    public function __get($field)
    {
        switch ($field) {
            case 'html':
                return $this->__toString();
            case 'units':
                return $this->getUnits();
            case 'compare':
                return $this->value_base_unit;
            case 'unit_name':
                if (!isset($this->unit_name)) {
                    $this->unit_name = $this->getUnitName($this->unit);
                }
                return $this->unit_name;
            default:
                return isset($this->{$field}) ? $this->{$field} : $this->convert($field);
        }
    }

    private function getUnits()
    {
        if (!isset($this->units)) {
            $this->units = shopDimension::getUnits($this->type);
        }
        return $this->units;
    }

    private function getUnitName($unit)
    {
        $this->getUnits();
        return isset($this->units[$unit]) ? $this->units[$unit]['title'] : $unit;
    }


    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {

    }

    public function offsetExists($offset)
    {
        return in_array($offset, array(
            'id',
            'feature_id',
            'sort',
            'value',
            'unit',
            'type',
            'value_base_unit',
            'units',
            'compare',
        ), true);
    }

    public function __toString()
    {
        return $this->format(false);
    }

    /**
     * @param string $format See formats for sprintf. `@locale` suffix will apply waLocale::format method
     * @return string
     * @throws waException
     */
    public function format($format)
    {
        if ($this->value === null) {
            return '';
        } else {
            if (preg_match('/^(.*)@locale$/', $format, $matches)) {
                $value = waLocale::format($this->value, false);
                $format = strlen($matches[1]) ? $matches[1] : false;
            } else {
                $value = $this->value;
            }

            if ($format === false) {
                $value = $value.' '.$this->unit_name;
            } else {
                $value = sprintf($format, $value, $this->unit_name);
            }
            return trim($value);
        }
    }

    public function convert($unit, $format = null)
    {
        if ($format === null) {
            $format = $this->format;
        }
        $value = shopDimension::getInstance()->convert($this->value, $this->type, $unit, $this->unit);
        return ($format === false) ? $value : trim(sprintf($format, $value, $this->getUnitName($unit)));
    }

    public function is_null()
    {
        return is_null($this->value);
    }
}
