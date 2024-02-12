<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;


class MockHandlers
{
	// mock 502 Bad Gateway nginx response
	// }
	
	function getClient(): Client
	{
	$mock502 = 
			new Response(502, [], "Bad Gateway", "<!DOCTYPE html>
			<html>
			<head>
					<title>502 Bad Gateway</title>
			</head>
			<body>
					<h1>502 Bad Gateway</h1>
					<p>The server encountered a temporary error and could not complete your request.</p>
					<p>Please try again later.</p>
			</body>
			</html>");
	
	$mock = new MockHandler([
		$mock502
	]);
	$handlerStack = HandlerStack::create($mock);
	$mockClient = new Client(['handler' => $handlerStack]);
	return $mockClient;
	}

}

// // The first request is intercepted with the first response.
// $response = $client->request('GET', '/');
// echo $response->getStatusCode();
// //> 200
// echo $response->getBody();
// //> Hello, World
// // The second request is intercepted with the second response.
// echo $client->request('GET', '/')->getStatusCode();
// //> 202

// // Reset the queue and queue up a new response
// $mock->reset();
// // As the mock was reset, the new response is the 201 CREATED,
// // instead of the previously queued RequestException
// echo $client->request('GET', '/')->getStatusCode();
// //> 201