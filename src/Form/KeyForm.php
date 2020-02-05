<?php


namespace MKDF\Keys\Form;

use Zend\Form\Form;
use Zend\Form\Element\Hidden;
use Zend\Form\Element\Text;
use Zend\Form\Element\Textarea;
use Zend\Form\Element\Submit;

class KeyForm extends Form
{
    // Constructor.
    public function __construct()
    {
        // Define form name
        parent::__construct('key-form');
        // Set POST method for this form
        $this->setAttribute('method', 'post');
        // (Optionally) set action for this form
        // $this->setAttribute('action', '/key/post');
        $this->setAttribute('class', '/key/post');
        $this->add([
            'type'  => 'hidden',
            'name' => 'id',
            'attributes' => [
                'id'  => 'keyId',
            ]]);
        $e = new Text('name',['label'=>'Name']);
        $e->setAttribute('class','form-control');
        $this->add($e);
        $e = new Textarea('description',['label'=>'Description']);
        $e->setAttribute('class','form-control');
        $this->add($e);
        $e = new Submit('submit',['label'=>'Submit']);
        $e->setAttribute('value', 'Submit');
        $e->setAttribute('class', 'btn btn-primary');
        $this->add($e);

        // Input Filter
        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
                'name'     => 'name',
                'required' => true,
                'filters'  => [
                    ['name' => 'StringTrim'],
                    ['name' => 'StripTags'],
                    ['name' => 'StripNewlines'],
                ],
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'min' => 1,
                            'max' => 128
                        ],
                    ],
                ],
            ]
        );
        $inputFilter->add([
                'name'     => 'description',
                'required' => true,
                'filters'  => [
                    ['name' => 'StripTags'],
                ],
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'min' => 1,
                            'max' => 4096
                        ],
                    ],
                ],
            ]
        );
    }
}