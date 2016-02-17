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
						null,
						InputOption::VALUE_REQUIRED,
						'Set dork. Example: --dork'),

					new InputOption(
						'enginiers',
						'e',
						InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
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
	protected function execute(InputInterface $input, OutputInterface $output) {

		$dork    	= $input->getOption('dork');
        $enginiers  = $input->getOption('enginiers');
		$email    	= $input->getOption('email');
		$txt    	= $input->getOption('txt');
		$tor    	= $input->getOption('tor');
		$proxylist    	= $input->getOption('proxylist');
		$filterProxy=array();

        $ghdb = new Ghdb($dork,$proxylist,$tor);
        foreach($enginiers as $enginer){
            switch($enginer){
                case 'google':
					$output->writeln("<comment>*".$enginer."</comment>");
                    $result['google']=$ghdb->runGoogle();

                    break;
                case 'googleapi':
                    $result['googleapi']=$ghdb->runGoogleApi();
                    break;
            }
        }

		if(!empty($email)){
			$this->sendMail($result,$email);
		}

		if(!empty($txt)){
			$this->saveTxt($result,$this->createNameFile());
		}

		$this->printResult($result,$output);

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
			$mailer->sendMessage('lenonleite@gmail.com',"Fail, not finder password in list. =\\");
		}else{
			$msg = "PHP Avenger Informer final, list of SUCCESS:<br><br>";
			foreach($resultFinal as $keyResultEnginer=>$resultEnginer){
				foreach($resultEnginer as $keyResult=>$result){
					$msg.=$keyResultEnginer." ".$result." <br>";
				}
			}
			$mailer->sendMessage('lenonleite@gmail.com',$msg);
		}

	}

	protected function createNameFile()
	{
		return $this->getName().'_'.date('m-d-Y_hia');
	}

	protected function printResult($resultFinal, OutputInterface $output)
	{
		var_dump($resultFinal);
		exit();
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