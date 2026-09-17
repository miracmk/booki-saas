<?php defined('BASEPATH') or exit('No direct script access allowed');

class Docs extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->view('pages/docs', [
            'company_name' => setting('company_name', 'BooKi') ?: 'BooKi'
        ]);
    }
}
