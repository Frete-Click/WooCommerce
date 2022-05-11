<?php
namespace SDK\Models;


class Config{

	protected $order = 'total';
	protected $quote_type = 'simple';
	protected $increase_deadline = 0;
	protected $no_retrieve = false;


	public function getOrder(){
		return $this->order;	
	}

	public function getQuoteType(){
		return $this->quote_type;	
	}

	public function getIncreaseDeadline(){
		return $this->increase_deadline;	
	}

	public function setOrder($order){
		$this->order = $order;
		return $this;
	}

	public function setQuoteType($quote_type){
		$this->quote_type = $quote_type;
		return $this;
	}

	public function setIncreaseDeadline($increase_deadline){
		$this->increase_deadline = $increase_deadline;
		return $this;
	}	

	public function setNoRetrieve($no_retrieve){
		$this->no_retrieve = $no_retrieve;
		return $this;
	}

	public function getNoRetrieve(){
		return $this->no_retrieve;
	}
}