<?php
/**
 * Created by PhpStorm.
 * User: einarvalur
 * Date: 11/03/14
 * Time: 18:16
 */

namespace Stjornvisi\Form;


use Zend\Form\Form;

class Resource extends Form {
	public function __construct(array $authors = array()){

		parent::__construct( strtolower( str_replace('\\','-',get_class($this) ) ));

		$this->setAttribute('method', 'post');

		$this->add(array(
			'name' => 'name',
			'type' => 'Stjornvisi\Form\Element\Img',
			'attributes' => array(
				'placeholder' => 'Nafn...',
				'required' => 'required',
				'data-url' => '/skrar/mynd'	//TODO can I use a function to call the router?
			),
			'options' => array(
				'label' => 'Nafn',
			),
		));

		$this->add(array(
			'name' => 'description',
			'type' => 'Zend\Form\Element\Textarea',
			'attributes' => array(
				'placeholder' => 'Lýsing...',
			),
			'options' => array(
				'label' => 'Lýsing',
			),
		));

		$this->add(array(
			'name' => 'submit',
			'type' => 'Zend\Form\Element\Submit',
			'attributes' => array(
				'value' => 'Submit',
			),
			'options' => array(
				'label' => 'Submit',
			),
		));

	}
} 
