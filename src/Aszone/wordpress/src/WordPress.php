<?php

namespace Aszone\WordPress;

use GuzzleHttp\Client;
use Respect\Validation\Validator as v;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DomCrawler\Crawler;
//use Aszone\Site;


class WordPress
{

	//VERIFY IF IS WORDPRESS
	public function isWordPress($url,$proxy="",$tor=""){

		$isUrl   	= v::url()->notEmpty()->validate($url);
		$client 	= new Client();
		//$site 		= new Site();
		$res 		= $client->get( $url,array());
		//$response 	= $res->send();
		//$isActive 	= $site->isActive($res->getStatusCode());
		$body =$res->getBody()->getContents();
		//echo $body;
		//exit();
		//if($isUrl && $isActive){
		if($isUrl){

			//$bodyaaa 		= $res->getBody();

			
			$crawler 	= new Crawler($body);
			$arrLinksJs	= $crawler->filter('script');
			foreach ($arrLinksJs as $keyTest => $valueTest) {
				echo $valueTest->attr('src');
				//var_dump()
			}
			//$arrLinksJs = $crawler->filterXpath();
			//$arrLinks	=$regex->getLinks($body);
			var_dump($arrLinksJs);
			exit();
			//var_dump($res->getReasonPhrase());

		}
		exit();
        
	}

	public function getUsers(){

	}

	public function getPlugins(){

	}

	public function getThemes(){

	}
}