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
use Aszone\Ghdb\Ghdb;
use Service\Mailer;
use Service\GuzzleTor;


class GoogleHackingController extends Command{

	public $tor;
	public $vp;
	public $dork;
	public $email;
	public $enginers;
	public $txt;
	public $proxylist;

    protected function configure() {
        $this
          	->setName("googlehacking")
          	->setDescription("Google Hacking is tecnica of user google for get data secret")
			->setDefinition(
                new InputDefinition(array(
                   	new InputOption(
                    	'backup-files',
                    	'bkp',
                    	InputOption::VALUE_NONE,
                    	'Set the hash. Example: --tor'),
					new InputOption(
						'dork',
						'd',
						InputOption::VALUE_REQUIRED,
						'Set dork. Example: --dork'),

					new InputOption(
						'eng',
						'e',
						InputOption::VALUE_REQUIRED,
						'What seraches enginer?',
						array('google', 'googleapi')
					),
					new InputOption(
						'txt',
						null,
						InputOption::VALUE_NONE,
						'Set dork. Example: --txt'),

					new InputOption(
						'tor',
						null,
						InputOption::VALUE_NONE,
						'Set dork. Example: --tor'),

					new InputOption(
						'proxylist',
						null,
						InputOption::VALUE_NONE,
						'Set dork. Example: --proxylist'),
					new InputOption(
						'vp',
						null,
						InputOption::VALUE_NONE,
						'Set dork. Example: --vp'),
					new InputOption(
						'email',
						null,
						InputOption::VALUE_NONE,
						'Set the mail for send result. Example: --email'),

                    /*new InputOption(
                    	'hashs',
                    	'hss',
                    	InputOption::VALUE_REQUIRED,8/
                    	'Set the file with list of hashs. Example: --hashs=/home/foo/hashs.lst'),*/

                ))
            )
            ->setHelp('<comment>Command used to brute force</comment>');
    }
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->validParamns($input,$output);
		/*$dork    		= $input->getOption('dork');
		$virginProxies	= $input->getOption('virginProxies');
        $enginiers  	= $input->getOption('enginiers');
		$email    		= $input->getOption('email');
		$txt    		= $input->getOption('txt');
		$tor    		= $input->getOption('tor');
		$proxylist    	= $input->getOption('proxylist');*/
		$filterProxy=array();

        $ghdb = new Ghdb($this->dork,$this->proxylist,$this->tor,$this->vp);
        foreach($this->eng as $enginer)
		{
            switch($enginer)
			{
                case 'google':
					$output->writeln("<comment>*".$enginer."</comment>");
                    $result['google']=$ghdb->runGoogle();

                    break;
                case 'googleapi':
                    $result['googleapi']=$ghdb->runGoogleApi();
                    break;
				default:
					$output->writeln("<comment>Name Enginer not exist, help me and send email with site of searching not have you@example.com ... </comment>");
					break;
            }
        }

		if(!empty($this->email)){
			$this->sendMail($result,$this->email);
		}

		if(!empty($this->txt)){
			$this->saveTxt($result,$this->createNameFile());
		}

		$this->printResult($result,$output);

	}

	protected function validParamns(InputInterface $input,OutputInterface $output)
	{

		$this->dork    		= $input->getOption('dork');
		$this->vp			= $input->getOption('vp');
		$this->eng  		= $this->sanitazeValuesOfEnginers($input->getOption('eng'));
		$this->email   		= $input->getOption('email');
		$this->txt    		= $input->getOption('txt');
		$this->tor	    	= $input->getOption('tor');
		$this->proxylist   	= $input->getOption('proxylist');

		if(!$this->dork)
		{
			$output->writeln("<error>Please, insert your dork... </error>");
			$output->writeln("<error>example: --dork=\"site:com inurl:/admin\"</error>");
			exit();
		}
		if(!$this->eng)
		{
			$output->writeln("<error>Please, insert your sites of searching... </error>");
			$output->writeln("<error>example: --enginiers=\"google,dukedukego,googleapi\"</error>");
			exit();
		}

	}

	protected function sanitazeValuesOfEnginers($enginers)
	{
		if($enginers)
		{
			return explode(",",$enginers);
		}
		return false;
	}
	protected function saveTxt($data,$filename)
	{
		$file=__DIR__."/../../../results/".$filename.".txt";
		$myfile = fopen($file, "w") or die("Unable to open file!");
		if(is_array($data)){
			foreach($data as $dataType)
			{
				foreach ($dataType as $singleData)
				{
					$txt = $singleData."\n";
					fwrite($myfile, $txt);
				}
			}
		}
		else
		{
			$txt = $data;
			fwrite($myfile, $txt);
		}
		fclose($myfile);

		if(!file_exists($file)){
			return false;
		}
		return true;

	}

	protected function sendMail($resultFinal)
	{
		//Send Mail with parcial results
		$mailer = new Mailer();
		if(empty($resultFinal)){
			$mailer->sendMessage('you@example.com',"Fail, not finder password in list. =\\");
		}else{
			$msg = "PHP Avenger Informer final, list of SUCCESS:<br><br>";
			foreach($resultFinal as $keyResultEnginer=>$resultEnginer){
				foreach($resultEnginer as $keyResult=>$result){
					$msg.=$keyResultEnginer." ".$result." <br>";
				}
			}
			$mailer->sendMessage('you@example.com',$msg);
		}

	}

	protected function createNameFile()
	{
		return $this->getName().'_'.date('m-d-Y_hia');
	}

	protected function printResult($resultFinal, OutputInterface $output)
	{

		foreach($resultFinal as $keyResultEnginer=>$resultEnginer){
			foreach($resultFinal as $keyResult=> $result){
				var_dump($keyResult);
				exit();
				$output->writeln("*-------------------------------------------------");
				$output->writeln("<info>*".$keyResultEnginer." -> ".$result."</info>");
			}
		}
		$output->writeln("*-------------------------------------------------");
	}
}