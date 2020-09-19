<?php
namespace SDK\Models;

use SDK\Models\Package;
use SDK\Models\Origin;
use SDK\Models\Destination;
use SDK\Models\Config;

class QuoteRequest{

	public $origin = [];
	public $destination = [];
	public $productTotalPrice = 0;
	public $productType = "";	
	public $order = 'total';
	public $quote_type = 'simple';
	public $packages = [];
	public $contact = null;

	public function addPackage(Package $package){		

		$this->packages[] 			= array(
			'qtd' 		=> (int) $package->getQuantity(),
			'weight' 	=> $package->getWeight(),
			'height' 	=> $package->getHeight(),
			'width' 	=> $package->getWidth(),
			'depth' 	=> $package->getDepth()
		);
		$this->productTotalPrice 	+= $package->getProductPrice();
		$this->productType 			.= $package->getProductType().',';
		return $this;
	}	

	public function setConfig(Config $config){
		$this->order = $config->getOrder();
		$this->quote_type = $config->getQuoteType();
		return $this;
	}

	public function setOrigin(Origin $origin){
		$this->origin = [
			//'cep' 			=> (int) $origin->getCEP(),
			//'street' 		=> $origin->getStreet(),
			//'number' 		=> (int) $origin->getNumber(),
			//'complement' 	=> $origin->getComplement(),
			//'district' 		=> $origin->getDistrict(),
			'city' 			=> $origin->getCity(),
			'state' 		=> $origin->getState(),
			'country' 		=> $origin->getCountry()
		];		
		return $this;
	}

	public function setDestination(Destination $destination){
		$this->destination = [
			//'cep' 			=> (int) $destination->getCEP(),
			//'street'		=> $destination->getStreet(),
			//'number' 		=> (int) $destination->getNumber(),
			//'complement' 	=> $destination->getComplement(),
			//'district' 		=> $destination->getDistrict(),
			'city' 			=> $destination->getCity(),
			'state' 		=> $destination->getState(),
			'country' 		=> $destination->getCountry()
		];	
		return $this;
	}	

	
}