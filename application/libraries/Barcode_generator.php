<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load Composer autoload (if not already loaded by index.php)
if (!class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
    require_once FCPATH . 'vendor/autoload.php';
}

use Picqer\Barcode\BarcodeGeneratorPNG;

class Barcode_generator {
    
    private $generator;
    
    public function __construct() {
        $this->generator = new BarcodeGeneratorPNG();
    }
    
    /**
     * Generate a real, scanner-compatible CODE128 barcode
     */
    public function generate($code, $width = 2, $height = 60)
    {
        return $this->generator->getBarcode($code, BarcodeGeneratorPNG::TYPE_CODE_128, $width, $height);
    }
    
    /**
     * Output barcode directly to browser
     */
    public function output($code, $width = 2, $height = 60)
    {
        header('Content-Type: image/png');
        echo $this->generate($code, $width, $height);
    }
    
}