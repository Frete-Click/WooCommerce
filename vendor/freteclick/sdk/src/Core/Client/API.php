<?php
namespace SDK\Core\Client;

use GuzzleHttp\Client as GuzzClient;
use Psr\Http\Message\ResponseInterface;

class API
{
	private $endpoint = 'https://api.freteclick.com.br';
	private $apiKey   = null;	

	public function __construct(string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

	public function private(string $method, string $resource, array $options = []): ResponseInterface
	{
		if (empty($this->apiKey))
			throw new \Exception('API key can not be empty');

		$client = new GuzzClient([
			'http_errors' => false,
			'connect_timeout' => 5,
			'timeout' => 10
		]);

		$headers = [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
			'api-token'    => $this->apiKey
		];

		$options['headers'] = isset($options['headers'])
			? array_merge($headers, $options['headers'])
			: $headers;

		$url = rtrim($this->endpoint, '/') . $resource;

		return $client->request($method, $url, $options);
	}

	public function public(string $method, string $resource, array $options = []): ResponseInterface
	{
		$client = new GuzzClient([
			'http_errors' => false,
			'connect_timeout' => 5,
			'timeout' => 10
		]);

		$headers = [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json'
		];

		$options['headers'] = isset($options['headers'])
			? array_merge($headers, $options['headers'])
			: $headers;

		$url = rtrim($this->endpoint, '/') . $resource;

		return $client->request($method, $url, $options);
	}
}
