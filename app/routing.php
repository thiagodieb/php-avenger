<?php
use Avenger\Controller;

$application->add(new Avenger\Controller\PasswordCrackingController());
$application->add(new Avenger\Controller\BruteForceController());
$application->add(new Avenger\Controller\WordPressController());
$application->add(new Avenger\Controller\GoogleHackingController());