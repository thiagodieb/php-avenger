<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

/**
 * Provides helpers to display a table.
 *
 * @author Lenon Leite <lenonleite@gmail.com>
 * @author Саша Стаменковић <umpirsky@gmail.com>
 * @author Abdellatif Ait boudad <a.aitboudad@gmail.com>
 * @author Max Grigorian <maxakawizard@gmail.com>
 */
class Wordlist
{
	public function validate($file){
		$resultValidade=array();
		$resultValidade=$this->fileExist($file);
		if($resultValidade['error']==false){
			return true;
		}
		return false;
	}

	private function fileExist($file){
		$error['error']=false;
		if (!file_exists($file)) {
		   $error['msg']="File no exist";
		   $error['error']=true;
		}
		return $error;
	}

	public function verifyCompass($file){
		$filenameParts = explode( ".", $file );		
		if(($filenameParts[1]=="zip")OR($filenameParts[1]=="rar")){
			return true;
		}
		return false;
	}

	public function extractCompass($file){
		$filenameParts = explode( ".", $filepath );

		switch ($filenameParts[1]) {

			case 'rar':
				$rar_file = rar_open($file) or die("Failed to open Rar archive");
				$list = rar_list($rar_file);
				break;
			
			case 'zip':
				
				break;

			default:
				# code...
				break;
		}
	}

	public function sanitizePassword($password){
		return $password = trim(preg_replace('/\s/',' ',$password));
	}

	public function isValidMd5($md5)
	{
    	return !empty($md5) && preg_match('/^[a-f0-9]{32}$/', $md5);
	}

	
}