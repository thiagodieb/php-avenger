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
use Aszone\FakeHeaders\FakeHeaders;
use Service\Mailer;
use GuzzleHttp\Client;



class SqlInjectionController extends Command
{
    public $target;

    public $tor;

    protected function configure()
    {
        $this
            ->setName("sqli")
            ->setDescription("Test Sql Injection")
            ->setDefinition(
                new InputDefinition(array(

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
                        'txt',
                        null,
                        InputOption::VALUE_NONE,
                        'Set the hash. Example: --txt'),
                    new InputOption(
                        'email',
                        null,
                        InputOption::VALUE_NONE,
                        'Set the hash. Example: --email'),

                ))
            )
            ->setHelp('<comment>Command used to Local File Download</comment>');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $wordlist = $input->getOption('wordlist');
        $tor = $input->getOption('tor');
        $txt = $input->getOption('txt');
        $email = $input->getOption('email');

        $result = [];
        $this->setTor($tor);
        $targets = $this->getWordListInArray($wordlist);
        if($targets)
        {
            foreach ($targets as $keyTarget=> $target)
            {
                $output->writeln("");
                $output->writeln("<comment>Target number ".$keyTarget." : ".$target."</comment>");
                $this->target=$target;
                $result[] = $this->checkSuccess();
                if(!end($result))
                {
                    $output->writeln("");
                    $output->writeln("<error>Is not vull</error>");
                }
                else
                {
                    $output->writeln("");
                    $output->writeln("<info>Is  vull</info>");
                    if(!empty($email))
                    {
                        $output->writeln("");
                        $output->writeln("<info>Send email with result of target...</info>");
                        $this->sendMail(end($result));
                    }
                }


            }
        }
        else
        {
            $output->writeln("<error>Insert One target or a wordlist of mass scan!</error>");
            return;
        }

        if(!empty($txt)){
            $this->saveTxt($result,$this->createNameFile());
        }


        exit();

    }

    protected function setTor($tor=false)
    {
        if($tor){
            $this->tor=['proxy' => [
                'http' => 'socks5://127.0.0.1:9050',
                'https' => 'socks5://127.0.0.1:9050'
            ]];
        }

    }

    protected function checkSuccess()
    {
        $isValidLfd=$this->isSqlInjection($this->target);
        if(!$isValidLfd)
        {
            return false;
        }
        return $this->setVull();
    }

    protected function isSqlInjection()
    {
        $explodeUrl=parse_url($this->target);
        if(isset($explodeUrl['query']))
        {
            return true;
        }
        return false;
    }

    protected function getWordListInArray($wordlist)
    {
        $checkFileWordList = v::file()->notEmpty()->validate($wordlist);
        if ($checkFileWordList) {
            $targetResult = file($wordlist, FILE_IGNORE_NEW_LINES);
            return $targetResult;
        }

        return false;
    }

    protected function setVull()
    {
        $url = $this->generateUrlByExploit();
        return $this->setAttack($url);

    }

    protected function generateUrlByExploit()
    {
        $explodeUrl=parse_url($this->target);
        $explodeQuery=explode("&",$explodeUrl['query']);
        $queryFinal="";
        //Identify and sets urls of values of Get
        foreach($explodeQuery as $keyQuery=> $query)
        {
            $queryFinal.=$query."'";
            //$explodeQueryEqual=explode("=",$query);
            //$wordsValue[$keyQuery]=$explodeQueryEqual[1];
            //$wordsKey[$keyQuery]=$explodeQueryEqual[0];
        }
        return $explodeUrl["scheme"]."://".$explodeUrl['host'].$explodeUrl['path']."?".$queryFinal;
    }

    protected function setAttack($url)
    {
        echo "***".$url."***";
        $header=new FakeHeaders();
        $client 	= new Client(['defaults' => [
            'headers' => ['User-Agent' => $header->getUserAgent()],
            'proxy'   => $this->tor,
            'timeout' => 30
            ]
        ]);
        try{
            $body=$client->get( $url )->getBody()->getContents();
            if($body)
            {
                if($this->checkErrorSql($body))
                {
                    return $url;
                }
            }
        }
        catch(\Exception $e)
        {
            if($e->getCode()!="404" AND $e->getCode()!="403"  )
            {

                return $url;
            }

            echo "Error code => ".$e->getCode()."\n";


        }
        return false;
    }

    protected function checkErrorSql($body)
    {
        //echo $body;
        $errors=$this->getErrorsOfList();
        foreach($errors as $error)
        {
            $isValid=strpos($body,$error);
            if($isValid!==false)
            {
                return true;
            }
        }
        return false;
    }

    protected function getErrorsOfList()
    {
        $errorsMysql= parse_ini_file(__DIR__."/../../../resource/sqlInjection/Errors/mysql.ini");
        $errorsMariaDb= parse_ini_file(__DIR__."/../../../resource/sqlInjection/Errors/mariadb.ini");
        $errorsOracle= parse_ini_file(__DIR__."/../../../resource/sqlInjection/Errors/oracle.ini");
        $errorssqlServer= parse_ini_file(__DIR__."/../../../resource/sqlInjection/Errors/sqlserver.ini");
        $errorsPostgreSql= parse_ini_file(__DIR__."/../../../resource/sqlInjection/Errors/postgresql.ini");

        return array_merge($errorsMysql,$errorsMariaDb,$errorsOracle,$errorssqlServer,$errorsPostgreSql);

    }

    protected function sendMail($result)
    {
        //Send Mail with parcial results
        $mailer = new Mailer();
        if(empty($result)){
            $mailer->sendMessage('you@example.com',"Fail, not finder password in list. =\\");
        }else{
            $msg = "PHP Avenger Informer, SUCCESS:<br><br>Link Vull is ".$result;

            $mailer->sendMessage('you@example.com',$msg);
        }

    }
}