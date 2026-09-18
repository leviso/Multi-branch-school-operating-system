<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Errors.php
 * @copyright : Reserved Synobix Team
 */

class Errors extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->view('errors/error_404_message.php');
    }
}
