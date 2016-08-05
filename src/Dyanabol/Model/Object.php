<?php
/**
 * rmayne
 *
 * @link      http://github.com/rmayne
 * @copyright Copyright (c)2016 rmayne
 * @license   MIT
 */
 
namespace Dyanabol\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\I18n\Validator\IsInt;


class Object implements InputFilterAwareInterface
{

  protected $inputFilter;


  public function exchangeArray($data)
  {
    foreach ($data as $key => $value) {
      $this->$key = $value;
    }
  }

  public function setInputFilter(InputFilterInterface $inputFilter)
  {
    throw new \Exception("Not used");
  }

  public function getArrayCopy()
  {
     return get_object_vars($this);
  }


  public function getInputFilter()
  {
    if (!$this->inputFilter) {
      $inputFilter = new InputFilter();

      $this->inputFilter = $inputFilter;
    }
    return $this->inputFilter;
  }

}

