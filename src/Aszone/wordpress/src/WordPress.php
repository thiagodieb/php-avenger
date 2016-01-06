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
		if($isUrl){
			$client 	= new Client();
			$res 		= $client->get( $url,array());
			$body 		=$res->getBody()->getContents();
			$crawler 	= new Crawler($body);
			$baseUrlWordPress=$this->getBaseUrlWordPressCrawler($crawler);
			if($baseUrlWordPress){
				return true;
			}
			return false;
		}
        
	}

	public function getBaseUrlWordPressByUrl($url){
		$isUrl   	= v::url()->notEmpty()->validate($url);
		if($isUrl) {
			$client 	= new Client();
			$body 		= $client->get( $url,array())->getBody()->getContents();
			//$body 		= $res->getBody()->getContents();
			$crawler 	= new Crawler($body);
			$arrLinks 	= $crawler->filter('link');

			foreach ($arrLinks as $keyLink => $valueLink) {
				if (!empty($valueLink->getAttribute('href'))) {
					$validXmlrpc = preg_match("/(.+?)(wp-content\/themes|wp-content\/plugins).*/", substr($valueLink->getAttribute('href'), 0), $matches, PREG_OFFSET_CAPTURE);
					if ($validXmlrpc) {
						return $matches[1][0];
					}

				}
			}
		}
	}

	public function getBaseUrlWordPressCrawler($crawler){

		$arrLinks 	= $crawler->filter('link');

		foreach ($arrLinks as $keyLink => $valueLink) {
			if (!empty($valueLink->getAttribute('href'))) {
				$validXmlrpc = preg_match("/(.+?)(wp-content\/themes|wp-content\/plugins).*/", substr($valueLink->getAttribute('href'), 0), $matches, PREG_OFFSET_CAPTURE);
				if ($validXmlrpc) {
					return $matches[1][0];
				}

			}

		}
	}

	public function getRootUrl(){
		
	}

	public function getUsers(){

	}

	public function getPlugins(){

	}

	public function getThemes(){

	}
}