<?php
use \Terminus\Products;
use \VCR\VCR;

class ProductsTest extends PHPUnit_Framework_TestCase {

 function testProductsInstance() {
    $products = Products::instance();
		VCR::turnOn();
		VCR::insertCassette('productsbyid');
    $test = $products->getById('3b754bc2-48f8-4388-b5b5-2631098d03de');
    $this->assertEquals('CiviCRM Starter Kit', $test['longname']);
    $test = $products->query();
    $this->assertNotEmpty($test);
		VCR::turnOff();
 }

}
