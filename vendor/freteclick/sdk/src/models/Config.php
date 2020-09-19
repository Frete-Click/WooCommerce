<?php
namespace SDK\Models;


class Config{

	protected $order = 'total';
	protected $quote_type = 'simple';


	public function getOrder(){
		return $this->order;	
	}
	public function getQuoteType(){
		return $this->quote_type;	
	}

	public function setOrder($order){
		$this->order = $order;
		return $this;
	}
	public function setQuoteType($quote_type){
		$this->quote_type = $quote_type;
		return $this;
	}

}