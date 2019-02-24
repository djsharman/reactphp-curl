<?php

namespace djsharman\React\Curl;


class Exception extends \RuntimeException {
    /**
     * @var \MCurl\Result
     */
    private $result;

    public function __construct(\MCurl\Result $result) {
        $this->result = $result;
        parent::__construct($result->getError(), $result->getErrorCode());
    }

    /**
     * @return \MCurl\Result
     */
    public function getResult() {
        return $this->result;
    }


}