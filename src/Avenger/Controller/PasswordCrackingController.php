<?php
namespace Avenger\Controller;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Helper\Wordlist;
use Symfony\Component\Console\Helper\Table;
//use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\ProgressBar;

class PasswordCrackingController extends Command{

	protected function configure() {
        $this
          	->setName("password")
          	->setDescription("Sample description for our command named test")
            
			->setDefinition(
                new InputDefinition(array(
                    new InputArgument(
                    	'type',
                    	InputArgument::REQUIRED,
                    	'What type? Example:  md5 , list-md5 , identify'),
                   	new InputArgument(
                    	'wordlist',
                    	InputArgument::REQUIRED,
                    	'List of password. Example: /home/foo/wordlist.lst'),
                   	new InputOption(
                    	'hash',
                    	'hs',
                    	InputOption::VALUE_REQUIRED,
                    	'Set the hash. Example: --hash=e10adc3949ba59abbe56e057f20f883e'),
                   	new InputOption(
                    	'hashs',
                    	'hss',
                    	InputOption::VALUE_REQUIRED,
                    	'Set the file with list of hashs. Example: --hashs=/home/foo/hashs.lst'),
                
                ))
            )			
            ->setHelp('<comment>Command used to break hashs in md5</comment>');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	
    	
    	switch ($input->getArgument('type')) {
    		case 'md5':
    			$this->executeMd5($input, $output);
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
    			$output->writeln("<error>This is 'Type' is not exist, select md5, list-md5</error>");
    			break;
    	}

    }

    protected function executeMd5(InputInterface $input, OutputInterface $output) {

    	$wordlistClass = new Wordlist();
    	$wordlist 	= $input->getArgument('wordlist');
    	$hash 		= $input->getOption('hash');

    	$validateWordlist=$wordlistClass->validate($wordlist);
    	$validateHash=$wordlistClass->isValidMd5($hash);

    	//VALIDATE WORDLIST
    	if($validateWordlist && $validateHash){
    		$compassFile=$wordlistClass->verifyCompass($wordlist);
	    	if($compassFile){
	    		$wordlistClass->extractCompass($wordlist);
	    	}

	    	// READ FILE LISTS OS PASSOWRDS 
			$arrWordlist = file($wordlist,FILE_IGNORE_NEW_LINES);

			//CONFIG PROFRESSBAR
			$progress = new ProgressBar($output, count($arrWordlist) );
			$progress->setBarCharacter('<comment>=</comment>');
			$progress->setFormat('verbose');
			$progress->setProgressCharacter('|');
			$progress->setBarWidth(100);
			$progress->setRedrawFrequency(500);
			$progress->start();
			

			//VERIFY HASH WITH WORDLIST
			$validateSuccess=false;
			foreach ($arrWordlist as $valueWordList) {	
				$progress->advance();
				$md5ValueWordList=md5($valueWordList);
				if($md5ValueWordList==$hash){
					$password =$valueWordList;
					$validateSuccess=true;
				}

			}

			//RESULT FINAL
			$progress->finish();
			if($validateSuccess==false){
				$output->writeln("");
				$output->writeln("<error>Password not found.</error>");
				$output->writeln("<error>Hash: ".$hash."</error>");
			}else{
				$output->writeln("");
				$output->writeln("<info>Password found.</info>");
				$output->writeln("<info>Password: ".$password."</info>");
				$output->writeln("<info>Hash: ".$hash."</info>");
			}
		
    	}elseif(!$validateHash){
    		$output->writeln("<error>Hash md5 is not valid</error>");
    	}else{
    		$output->writeln("<error>The Wordlist with this problem</error>");
    	}  	
		
    }

    protected function executeListMd5(InputInterface $input, OutputInterface $output) {

    	$wordlistClass = new Wordlist();
    	$wordlist 	= $input->getArgument('wordlist');
    	$hashs 		= $input->getOption('hashs');

    	$validateWordlist=$wordlistClass->validate($wordlist);
    	$validateHashs=$wordlistClass->validate($hashs);

    	if($validateWordlist && $validateHashs){

    		// READ FILE LISTS OS PASSOWRDS AND HASHS
    		$arrWordlist = file($wordlist,FILE_IGNORE_NEW_LINES);
    		$arrHashs	 = file($hashs,FILE_IGNORE_NEW_LINES);
    		$resultFinal = array();
    		$arrValueHash= array();    		

    		//CONFIG PROFRESSBAR
    		$progress = new ProgressBar($output, count($arrWordlist)*count($arrHashs) );
			$progress->setBarCharacter('<comment>=</comment>');
			$progress->setFormat('verbose');
			$progress->setProgressCharacter('|');
			$progress->setBarWidth(100);
			$progress->setRedrawFrequency(500);
			$progress->start();

			//VERIFY HASHS WITH WORDLIST
    		foreach ($arrHashs as $keyHash => $valueHash) {
    			
    			//VERFY IF ID EXIST
    			if(strpos($valueHash,":")!==false){
    				$arrValueHash=explode(":", $valueHash);
    				$valueHash=$arrValueHash[1];
    			}

    			foreach ($arrWordlist as $keyWordList => $valueWordList) {
    				
    				$progress->advance();
    				$md5ValueWordList=md5($valueWordList);

    				if($valueHash==$md5ValueWordList){

    					//INCREMENT LIST FINAL
    					if(!empty($arrValueHash)){
    						$resultFinal[$keyHash]['id']=$arrValueHash[0];
    					}
    					$resultFinal[$keyHash]['hash']=$valueHash;
    					$resultFinal[$keyHash]['password']=$valueWordList;
    					
    					// PARCIAL RESULT
    					$output->writeln("");
						$output->writeln("<info>Password found.</info>");
						$output->writeln("<info>Password: ".$valueWordList."</info>");
						$output->writeln("<info>Hash: ".$valueHash."</info>");
						break;
    				}
    			}
    		}
    		$progress->finish();

    		//PRINT RESULT LIST FINAL
    		$output->writeln("");
    		$table = new Table($output);
    		if(!isset($resultFinal[0]['id'])){
    			$table->setHeaders(array('MD5', 'PASSWORD'));
    		}else{
    			$table->setHeaders(array('ID','MD5', 'PASSWORD'));
    		}	        
	        $table->setRows($resultFinal);
	        $table->render();

    	}

    }

    
}