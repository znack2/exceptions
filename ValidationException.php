<?php namespace App\Validator;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Support\MessageProvider;
use RuntimeException;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;

class ValidationException extends RuntimeException implements MessageProvider , Jsonable, Arrayable
{
    protected $model;
    protected $errors;
    protected $messageBag;

    public function getErrors(){return $this->errors;}
    public function getMessageBag() {return $this->getErrors(); }
    public function setErrors(MessageBag $errors) {$this->errors = $errors; }
    public function getModel() {return $this->model; }
    public function setModel($model) {$this->model = $model; }

    public function __construct(MessageBag $errors)//$message, $errors
    {
        $this->errors = $errors;
        parent::__construct('Validation has failed.');
    }

    public function __toString()
    {
        $lines = explode("\n", parent::__toString());

        return array_shift($lines)." \nValidation errors:\n".implode($this->errors->all(), "\n")."\n".implode($lines, "\n");
    }

    public function __construct(MessageBag $messageBag){
        $this->messageBag = $messageBag;
    }

    public function getMessageBag(){
        return $this->messageBag;
    }
    
    public function toArray()
    {
        return [
            'error'=>'validation_exception',
            'error_description'=>$this->getMessageBag()
        ];
    }
    
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
