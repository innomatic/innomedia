<?php

require_once('innomatic/ajax/XajaxResponse.php');

class AjaxHelloWorld {
	public static function printHelloWorld() {
		$objResponse = new XajaxResponse();
		$objResponse->addAlert("Hello World!");
		
		return $objResponse;
	}
}