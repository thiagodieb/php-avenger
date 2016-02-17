<?php

namespace Aszone\WordPress;

use GuzzleHttp\Client;
use Respect\Validation\Validator as v;
use Symfony\Component\DomCrawler\Crawler;
//use Aszone\Site;


class WordPress
{
	public $target;

	public $proxy;

	public $portProxy;

	public $tor;

	public $pathPluginJson;

	public $optionTor;

	/**
	 * @param string $proxy
	 */
	public function setProxy($proxy)
	{
		$this->proxy = $proxy;
	}

	/**
	 * @param string $portProxy
	 */
	public function setPortProxy($portProxy)
	{
		$this->portProxy = $portProxy;
	}

	/**
	 * @param string $tor
	 */
	public function setTor($tor)
	{
		$this->tor = $tor;
		$this->optionTor=['proxy' => [
			'http' => 'socks5://'.$this->tor['proxy'],
			'https' => 'socks5://'.$this->tor['proxy']
		]];
	}


	public function __construct($target)
	{
		$this->optionTor=array();
		$this->target = $target;
		$this->installPlugin();
	}

	//VERIFY IF IS WORDPRESS
	public function isWordPress(){

		$isUrl   	= v::url()->notEmpty()->validate($this->target);
		if($isUrl){

			$baseUrlWordPress=$this->getBaseUrlWordPressCrawler();
			if($baseUrlWordPress){
				return true;
			}
			return false;
		}

	}

	public function getBaseUrlWordPressByUrl(){
		$isUrl   	= v::url()->notEmpty()->validate($this->target);
		if($isUrl) {
			$client 	= new Client();
			$body 		= $client->get( $this->target,$this->optionTor)->getBody()->getContents();
			//Check status block
			$crawler 	= new Crawler($body);
			$arrLinks 	= $crawler->filter('link');
			foreach ($arrLinks as $keyLink => $valueLink) {
				$validHref=$valueLink->getAttribute('href');
				if (!empty($validHref)) {
					$validXmlrpc = preg_match("/(.+?)((wp-content\/themes|wp-content\/plugins)|xmlrpc.php|feed\/|comments\/feed\/).*/", substr($valueLink->getAttribute('href'), 0), $matches, PREG_OFFSET_CAPTURE);
					if ($validXmlrpc) {
						$resultTeste=explode($matches[1][0],$this->target);
						if(count($resultTeste)>=2){
							return $matches[1][0];
						}
					}

				}
			}
		}
	}

	public function getBaseUrlWordPressCrawler(){

		$client 	= new Client();
		$res 		= $client->get( $this->target,$this->optionTor);
		//Check status block
		$body 		= $res->getBody()->getContents();
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

	public function getRootUrl(){
		
	}

	public function getUsers($limitNumberUsers=99999){

		$baseUrlWordPress=$this->getBaseUrlWordPressByUrl($this->target);

		$userList	= array();
		//Number for validade finish list of user
		$emptySequenceUsers=0;
		for ($i = 1; $i <= $limitNumberUsers; $i++) {

			try{
				$client 	= new Client();
				$result = $client->get( $baseUrlWordPress.'/?author='.$i, $this->optionTor );

				//Check status block

				$validGetUserByUrl = preg_match("/(.+?)\/\?author=".$i."/", substr($result->getEffectiveUrl(), 0), $matches, PREG_OFFSET_CAPTURE);

				if(!$validGetUserByUrl){
					$username=$this->getUserByUrl($result->getEffectiveUrl());
				}else{
					$username=$this->getUserBytagBody($result->getBody()->getContents());
				}
				if(!empty($username)){
					$userList[]=$username;
					echo $username;
					echo ' | ';
					$emptySequenceUsers=0;
				}else{
					if($limitNumberUsers==99999){
						$emptySequenceUsers++;
						echo ' | Sequence empty ';
						if($emptySequenceUsers==10){
							return $userList;
						}
					}
				}
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

	public function getPluginsVullExpert(){

		$jsonPlugins=$this->getListPluginsVull();
		//verify if plugins in list of vull
		foreach($jsonPlugins as $keyPlugin => $plugin){
			$validPlugin=$this->checkPluginExpert($keyPlugin);
			echo $keyPlugin.' | ';
			if($validPlugin){
				$arrPlugin[$keyPlugin]=$plugin;
				$arrPlugin[$keyPlugin]['url']=$this->target.'/wp-content/plugins/'.$keyPlugin;
			}
		}
		//Verify W3 total cache and Wp Super cache using detectable active because wpscan has
		// unsing guzzle
		// example http://exempla.com/wp-content/plugins/wp-super-cache/
		return $arrPlugin;

	}

	public function getPluginsVull(){


		try {
			$arrPluginsVull=array();
			$client 	= new Client();
			$res 		= $client->get($this->target, $this->optionTor);
			//check if is block
			$body 		= $res->getBody()->getContents();
			$crawler 		= new Crawler($body);
			$arrLinksLink 	= $crawler->filter('link');
			$arrLinksScript = $crawler->filter('script');

			//find href on links of css
			foreach ($arrLinksLink as $keyLink => $valueLink) {
				if(!empty($valueLink->getAttribute('href'))){
					$arryUrls[]=$valueLink->getAttribute('href');
				}

			}

			//find src on scripts of js
			foreach ($arrLinksScript as $keyScript => $valueScript) {
				if(!empty($valueScript->getAttribute('src'))){
					$arryUrls[]=$valueScript->getAttribute('src');
				}
			}

			//extract only name of plugin
			$arrPlugins= array();
			foreach($arryUrls as $urls){
				$validUrlPlugins = preg_match("/\/wp-content\/plugins\/(.+?)\//", substr($urls, 0), $matches, PREG_OFFSET_CAPTURE);
				if ($validUrlPlugins) {
					$arrPlugins[]= $matches[1][0];
				}
			}

			//clean plugin repated
			$arrPlugins = array_unique($arrPlugins);

			//return listOfPluginsVull
			$jsonPlugins=$this->getListPluginsVull();

			//Equals list of site with list of all plugins vull
			foreach($arrPlugins as $plugin){
				if(array_key_exists($plugin,$jsonPlugins)){
					$arrPluginsVull[$plugin]=$jsonPlugins[$plugin];
				}
			}

		}catch(\Exception $e){
			$arrPluginsVull=array();
		}

		return $arrPluginsVull;
	}

	public function getThemes(){

	}

	private function checkPluginExpert($plugin){

		try {
			$url = $this->target . '/wp-content/plugins/' . $plugin;
			$client = new Client();
			$res = $client->get($url, $this->optionTor);
			//check if change new tor ip
			$status = $res->getStatusCode();
			if (!$status == 200) {
				return false;
			}
			return true;
		}catch(\Exception $e){
			return false;
		}
	}

	private function installPlugin(){

		$pathDataZip			=__DIR__.'/../resource/data.zip';
		$pathFolderTmp			=__DIR__.'/../resource/tmp/';
		$this->pathPluginJson	=__DIR__.'/../resource/tmp/data/plugins.json';
		if(!file_exists($this->pathPluginJson)){
			$zip = new \ZipArchive;
			$zip->open($pathDataZip);
			$zip->extractTo($pathFolderTmp);
			$zip->close();
		}

	}

	private function getListPluginsVull(){
		$htmlPlugin = file_get_contents($this->pathPluginJson);
		$jsonPlugins = json_decode($htmlPlugin, true);
		ksort($jsonPlugins);
		return $jsonPlugins;
	}
}