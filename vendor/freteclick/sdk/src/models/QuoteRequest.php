<?php
namespace SDK\Models;

class QuoteRequest{

	public $origin = ["country"=>"Brasil","state"=>"SP","city"=>"Guarulhos"];
	public $destination = ["country"=>"Brasil","state"=>"RJ","city"=>"Rio de Janeiro"];
	public $productTotalPrice = 100;
	public $packages = [0=>["qtd"=>1,"weight"=>0.6,"height"=>0.4,"width"=>0.4,"depth"=>0.4]];
	public $productType = "teste";
	public $contact = null;

	
}