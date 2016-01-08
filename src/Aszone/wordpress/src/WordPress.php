<?php

namespace Aszone\WordPress;

use GuzzleHttp\Client;
use Respect\Validation\Validator as v;
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
				$validHref=$valueLink->getAttribute('href');
				
				if (!empty($validHref)) {
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
			$validHref=$valueLink->getAttribute('href');
			if (!empty($validHref)) {
				$validXmlrpc = preg_match("/(.+?)(wp-content\/themes|wp-content\/plugins).*/", substr($valueLink->getAttribute('href'), 0), $matches, PREG_OFFSET_CAPTURE);
				if ($validXmlrpc) {
					return $matches[1][0];
				}

			}

		}
	}

	public function getRootUrl(){
		
	}

	public function getUsers($url,$limitNumberUsers=99999){

		$baseUrlWordPress=$this->getBaseUrlWordPressByUrl($url);

		$userList	= array();
		//Number for validade finish list of user
		$emptySequenceUsers=0;
		for ($i = 1; $i <= $limitNumberUsers; $i++) {

			try{

				$client 	= new Client();
				$result = $client->get( $baseUrlWordPress.'/?author='.$i, array() );
				$validGetUserByUrl = preg_match("/(.+?)\/\?author=".$i."/", substr($result->getEffectiveUrl(), 0), $matches, PREG_OFFSET_CAPTURE);
				var_dump($validGetUserByUrl);
				if(!$validGetUserByUrl){
					$username=$this->getUserByUrl($result->getEffectiveUrl());
				}else{
					$username=$this->getUserBytagBody($result->getBody()->getContents());
				}

				$userList[]=$username;
				echo $username;
				echo ' | ';
				$emptySequenceUsers=0;
			}catch(\Exception $e){
				if($limitNumberUsers==99999){
					$emptySequenceUsers++;
					echo ' | Sequence empty ';
					if($emptySequenceUsers==10){
						return $userList;
					}
				}
			}


		}

		return $userList;


	}

	protected function getUserBytagBody($body){

		$crawler 	= new Crawler($body);
		$bodys=$crawler->filter('body');
		foreach ($bodys as $keyBody => $valueBody) {
			$class=$valueBody->getAttribute('class');
		}
		$username = preg_match("/author-(.+?)\s/", substr($class, 0), $matches, PREG_OFFSET_CAPTURE);
		if(isset($matches[1][0]) and (!empty($matches[1][0]) ) ){
			return $matches[1][0];
		}

		return false;

	}

	protected function getUserByUrl($urlUser){
		$validUser = preg_match("/author\/(.+?)\//", substr($urlUser, 0), $matches, PREG_OFFSET_CAPTURE);
		if(isset($matches[1][0]) and (!empty($matches[1][0]) ) ){
			return $matches[1][0];
		}
		return false;
	}

	public function getPlugins(){

	}

	public function getThemes(){

	}
}