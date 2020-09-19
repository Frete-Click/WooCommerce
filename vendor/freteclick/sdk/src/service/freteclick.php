<?php
namespace SDK\Service;

use SDK\Models\QuoteRequest;

class FreteClick{

	private static $url = 'https://api.freteclick.com.br/';
	private static $api_key = NULL;	
	private static $api = NULL;	
	
	private function __construct(){}

	public static function getInstance($api_key){
		self::$api_key = $api_key;
		self::$api = new \GuzzleHttp\Client(
			[				
				'headers' => [ 
					'Accept' => 'application/json',
            		'content-type' => 'application/ld+json',
					'api-token' => self::$api_key
				]
			]);	
	    return __CLASS__;	
	}

	public static function quote(QuoteRequest $quote_request){

//echo json_encode($quote_request);
//exit;

		$response = self::$api->request('POST', self::$url.'quotes', [
		    'json'   => $quote_request
		]);		
		
		return $response->getBody()->getContents();
	}

}