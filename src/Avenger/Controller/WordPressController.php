<?php
namespace Avenger\Controller;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Aszone\WordPress\WordPress;
use Respect\Validation\Validator as v;

class WordPressController extends Command{
    protected function configure() {
        $this
            ->setName("wordpress")
            ->setDescription("Sample description for our command named test")

            ->setDefinition(
                new InputDefinition(array(

                    new InputArgument(
                        'target',
                        InputArgument::REQUIRED,
                        'What target? Example: http://www.target.com, /home/foo/listOfTargets.txt'),

                    new InputOption(
                        'plugins-vull',
                        'p',
                        InputOption::VALUE_NONE,
                        'Example: --plugins-vull'),
                    new InputOption(
                        'expert',
                        'e',
                        InputOption::VALUE_NONE,
                        'Example: --expert'),
                    new InputOption(
                        'mail',
                        'm',
                        InputOption::VALUE_REQUIRED,
                        'Example: --mail:lenonleite@gmail.com'),
                ))
            )
            ->setHelp('<comment>Command used to break hashs in md5</comment>');
    }
    protected function execute(InputInterface $input, OutputInterface $output) {

        $pluginsVull    = $input->getOption('plugins-vull');
        $expert         = $input->getOption('expert');
        $targetCmd      = $input->getArgument('target');

        $validTarget   = v::file()->notEmpty()->validate($targetCmd);

        //validate if is a list or single site
        if($validTarget){

            $arrTarget   = file($targetCmd,FILE_IGNORE_NEW_LINES);
            $this->printResultPluginsVull($this->getPluginsVull($arrTarget,$expert),$output);


        }
    }

    protected function getPluginsVull($arrOfTarget=array(),$expert=false){

        $resultPluginsVull = array();
        foreach($arrOfTarget as $keyTarget => $target){
            $wp = new WordPress($target);
            if($expert){
                $resultPluginsVull[$target]=$wp->getPluginsVullExpert();
            }else{
                $resultPluginsVull[$target]=$wp->getPluginsVull();
            }
        }
        return $resultPluginsVull;

    }

    protected function printResultPluginsVull($pluginsVull=array(),OutputInterface $output){

        //Show number of results
        $output->writeln("Total of <info>".count($pluginsVull)."</info> possibles vunerabilities");

        //Show list of plugins vull
        foreach($pluginsVull as $keyPluginVull => $vunerabilities){

            $output->writeln("<comment>============================================</comment>");
            $output->writeln("Site <comment>".$keyPluginVull."</comment>");

            //Show info of vunerabilities of plugin
            foreach($vunerabilities as $keyVunerabilty => $dataPlugin){
                $output->writeln(" Plugin <comment>".$keyVunerabilty."</comment>");

                foreach($dataPlugin['vulnerabilities'] as $vunerability) {

                    $output->writeln("");
                    //Show result about vunerabilities
                    $output->writeln(" -Title : <info>" . $vunerability['title'] . "</info>");
                    $output->writeln(" -Version with problem <info>".$vunerability['fixed_in']."</info>");
                    $output->writeln(" -Type of vulnerability <info>".$vunerability['vuln_type']."</info>");

                    //Show references
                    $output->writeln(" -References : ");
                    ksort($vunerability['references']);
                    foreach ($vunerability['references'] as $keyReferenceOfVull => $referenceOfVull) {

                        foreach($referenceOfVull as $keyReference=> $reference){
                            switch ($keyReferenceOfVull) {
                                case 'osvdb':
                                    $output->writeln("  -- Osvdb : <info>http://osvdb.org/show/osvdb/" . $reference . "</info>");
                                    break;
                                case 'cve':
                                    $output->writeln("  -- Cve : <info> https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-" . $reference . "</info>");
                                    break;
                                case 'secunia':
                                    $output->writeln("  -- Secunia : <info> https://secunia.com/community/advisories/" . $reference . "</info>");
                                    break;
                                case 'url':
                                    $output->writeln("  -- Others : <info>" . $reference . "</info>");
                                    break;
                            }
                        }

                    }

                }
                $output->writeln("<comment>----------------------------------------</comment>");
            }

        }
    }


}