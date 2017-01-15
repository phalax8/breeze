<?php

class breezeErrors
{
    private $_errors = [];
    const ERROR_FREELANCE = 0;
    const ERROR_SYSTEM = 1;

    public function add($type, $description)
    {
        $error = new breezeError($type, $description);

        $this->_errors[] = $error;
    }

    public function breezeErrorsGetErrors()
    {
        return $this->_errors;
    }

    public function getERRORSYSTEM()
    {
        return self::ERROR_SYSTEM;
    }

    public function breezeErrorsClear()
    {
        $this->_errors = [];
    }

    public function breezeErrorsToString()
    {
        $result = '';

        foreach($this->_errors as $error){
            $result .= ($result == '') ?  '': '<br><br>';
            $result .= $error->breezeErrorsGetDescription();
        }

        return $result;
    }
}

class breezeError
{
    private $_type = 0;
    private $_description = '';

    public function __construct($type, $description)
    {
        $this->_type = $type;
        $this->_description = $description;
    }

    public function breezeErrorGetError()
    {
        return $this->_type;
    }

    public function breezeErrorsGetDescription()
    {
        return $this->_description;
    }
}
