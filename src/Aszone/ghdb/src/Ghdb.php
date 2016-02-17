<?php

namespace Aszone\Ghdb;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Ghdb{

	public $dork;

    public $pathProxy;

    public $proxy;

    public $tor;

    public $proxylist;

    public $countProxylist;

	public function __construct($dork,$proxylist=false,$tor=false)
	{

		$this->dork = $dork;
        $this->proxylist=$proxylist;
        $this->pathProxy = __DIR__ . '/../resource/proxys.json';
        $this->countProxylist=1;
        if(file_exists($this->pathProxy))
        {
            unlink($this->pathProxy);
        }
        if($tor)
        {
            $this->tor='socks5://127.0.0.1:9050';
        }
        $this->setProxyOfSites();
	}

	public function runGoogle()
    {
        $site= $this->getSiteGoogle();
        $exit=false;
        $count=0;
        $paginator="";
        $resultFinal=array();
        while ($exit == false) {
            if($count!=0){
                $numPaginator=100*$count;
                $paginator="&start=".$numPaginator;
            }
            $urlOfSearch="https://".$site."/search?q=".$this->dork."&num=100&btnG=Search&pws=1".$paginator;
            echo "Page ".$count."\n";
            $arrLinks=$this->getLinks($urlOfSearch);

            $results=$this->sanitazeLinks($arrLinks);
            if(count($results)==0){
                $exit=true;
            }
            $resultFinal=array_merge($results,$resultFinal);
            $count++;
            sleep(1);
        }

        return $resultFinal;

	}

    public function runGoogleApi()
    {
        $exit=false;
        $count=0;
        $paginator="";
        $resultFinal=array();
        while ($exit == false) {
            if($count!=0){
                $numPaginator=100*$count;
                $paginator="&start=".$numPaginator;
            }
            $urlOfSearch="http://ajax.googleapis.com/ajax/services/search/web?v=1.0&rsz=8&q=".$this->dork.$paginator."&userip=".$this->getIp()."&filter=1&safe=off&num=100";
            echo $urlOfSearch;

            $arrLinks=$this->getJsonSearch($urlOfSearch);
            $results=$this->getJsonGoogleApi($arrLinks);
            $results=$this->sanitazeLinksJson($results);
            if(count($results)==0){
                $exit=true;
            }
            $resultFinal=array_merge($results,$resultFinal);
            $count++;
        }
        return $resultFinal;
    }

    public function runDuckduckGo(){
        //https://api.duckduckgo.com/html/?q=[DORK]&kl=en-us&p=-1&s=[PAG]&dc=[PAG3]&o=json&api=d.js
    }

    public function checkBlacklist($url="")
    {
        if(!empty($url)){
            $validXmlrpc = preg_match("/\/\/(.+?)\//", $url, $matches, PREG_OFFSET_CAPTURE);
            $url=$matches[1][0];
            $ini_blakclist = parse_ini_file(__DIR__."/../resource/Blacklist.ini");
            $key=array_search($url,$ini_blakclist);
            if($key!=false){
                return true;
            }
        }
        return false;
    }

    public function clearLink($url="")
    {
        if(!empty($url)){
            $validXmlrpc = preg_match("/search%3Fq%3Dcache:.+?:(.+?)%252B/", $url, $matches, PREG_OFFSET_CAPTURE);
            if(isset($matches[1][0])) {
                $url = $matches[1][0];
            }
            return $url;
        }

        return false;
    }

    public function getSiteGoogle(){
        $ini_google_sites = parse_ini_file(__DIR__."/../resource/AllGoogleSites.ini");
        $site=$ini_google_sites[array_rand($ini_google_sites)];
        return $site;
    }

    public function getLinks($urlOfSearch)
    {
        $valid=true;
        while($valid==true){
            try{
                $client 	= new Client([
                    'defaults' => [
                        'headers' => ['User-Agent' => $this->setUserAgent()],
                        'proxy'   => $this->proxy,
                        'timeout' => 60
                    ]
                ]);
                $body 		= $client->get($urlOfSearch)->getBody()->getContents();
                $valid=false;
                break;
            }catch(\Exception $e){

                echo "ERROR : ".$e->getMessage()."\n";
                if($this->proxy==false){
                    echo "Your ip is blocked, we are using proxy at now...\n";
                    $this->proxylist= true;
                }
                $this->setProxyOfSites();
                sleep(2);
            }
        }

        $crawler 	= new Crawler($body);
        $arrLinks 	= $crawler->filter('a');
        return $arrLinks;
    }

    public function sanitazeLinks($links){
        $hrefs= array();
        foreach ($links as $keyLink => $valueLink)
        {
            $validXmlrpc = preg_match("/\/url\?q=(.+?)&sa/", $valueLink->getAttribute('href'), $matches, PREG_OFFSET_CAPTURE);
            if(isset($matches[1][0]))
            {
                $url=$this->clearLink($matches[1][0]);
                $validResultOfBlackList=$this->checkBlacklist($url);
                if(!$validResultOfBlackList)
                {
                    $hrefs[]=$url;
                }
            }
        }
        $hrefs = array_unique($hrefs);
        return $hrefs;
    }

    public function sanitazeLinksJson($links)
    {
        $hrefs=array();
        foreach ($links as $keyLink => $valueLink)
        {
            $url=$this->clearLink($valueLink);
            $validResultOfBlackList=$this->checkBlacklist($url);
            if(!$validResultOfBlackList)
            {
                $hrefs[]=$valueLink;
            }
        }
        $hrefs = array_unique($hrefs);
        return $hrefs;

    }

    public function getIp(){
        return intval(rand() % 255) . "." . intval(rand() % 255) . "." . intval(rand() % 255) . "." . intval(rand() % 255);
    }

    public function getJsonSearch($urlOfSearch){
        $client 	= new Client();
        $body 		= $client->get($urlOfSearch)->getBody()->getContents();
        $result=json_decode($body);
        return $result;
        //return $arrLinks;
    }

    public function getJsonGoogleApi($listGoogleApi=""){
        $arrayFinal=array();
        if(isset($listGoogleApi->responseData->results)){
            foreach($listGoogleApi->responseData->results as $result){
                $arrayFinal[]=$result->url;
            }
        }
        return $arrayFinal;
    }

    public function setProxyOfSites()
    {
        echo "Setting Proxy...\n";
        if(!empty($this->proxylist) AND (!file_exists($this->pathProxy)))
        {
            $this->registerLisSitetFreeProxyList();
        }

        $this->proxy = $this->getOnlyOneProxy();

    }

    public function getOnlyOneProxy()
    {
        if(empty($this->proxylist) AND empty($this->tor))
        {
            return false;
        }
        if(!empty($this->tor))
        {
            return $this->tor;
        }

        $str = file_get_contents($this->pathProxy);
        $proxys = json_decode($str, true);
        $resultProxy = "tcp://";
        $resultProxy.=$proxys[$this->countProxylist]['ip'].':'.$proxys[$this->countProxylist]['port'];
        $this->countProxylist++;
        echo $resultProxy."\n";
        return $resultProxy;
    }

    public function registerLisSitetFreeProxyList()
    {

        $listProxysIni = parse_ini_file(__DIR__ . "/../resource/SitesProxysFree.ini");
        echo "Loading proxys by site ".$listProxysIni[2]."\n";
        $client 	= new Client();
        $body 		= $client->get($listProxysIni[2],array(), array(
            'headers' => array('User-Agent' => $this->setUserAgent())
        ))->getBody()->getContents();

        $crawler 	= new Crawler($body);
        $count=$crawler->filterXPath('//table/tbody/tr')->count();
        $listProxys = array();
        for($i = 1; $i <= $count; $i++)
        {
            $listProxys[$i]['ip']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[1]')->text();
            $listProxys[$i]['port']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[2]')->text();
            $listProxys[$i]['codeCountry']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[3]')->text();
            $listProxys[$i]['country']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[4]')->text();
            $listProxys[$i]['anonymity']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[5]')->text();
            $listProxys[$i]['google']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[6]')->text();
            $listProxys[$i]['https']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[7]')->text();
            $listProxys[$i]['lastChecked']=$crawler->filterXPath('//table/tbody/tr['.$i.']/td[8]')->text();
        }
        return $this->createJsonListProxys($listProxys);
    }

    public function setUserAgent()
    {
        $browser = parse_ini_file(__DIR__ . "/../resource/UserAgent/Browser.ini");
        $system = parse_ini_file(__DIR__ . "/../resource/UserAgent/System.ini");
        $Locale = parse_ini_file(__DIR__ . "/../resource/UserAgent/Locale.ini");
        return $browser[rand(0, count($browser) - 1)] . '/' . rand(1, 20) . '.' . rand(0, 20) . ' (' . $system[rand(0, count($system) - 1)] . ' ' . rand(1, 7) . '.' . rand(0, 9) . '; ' . $Locale[rand(0, count($Locale) - 1)] . ';)';
    }

    public function createJsonListProxys($datas)
    {
        if(file_exists($this->pathProxy)){
            unlink($this->pathProxy);
        }
        $fp = fopen($this->pathProxy, 'w');
        fwrite($fp, json_encode($datas));
        fclose($fp);
        return file_exists($this->pathProxy);
    }

}