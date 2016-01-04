<?php

namespace Aszone;

use GuzzleHttp\Client;

class Site{

	public function isActive($cod=""){
		if($cod=="200"){
			return true;
		}
		return false;
	}
	
}