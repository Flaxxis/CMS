<?php

abstract class controllerAbstract extends Zend_Controller_Action
{

    public $post;
    public $params;
    public $cookie;
    /**
     * @var $customer Models_Customer
     */
    public $customer;

    public function init()
    {
        $this->CurMenu = $this->view->CurMenu = Zend_Registry::get('CurMenu');
        if($this->CurMenu->getRedirectUrl()){
            $this->redirect($this->CurMenu->getRedirectUrl());
        }

        $this->post = $this->view->post = new Orm_Array($this->getRequest()->getPost());
        $this->requestParams = $this->view->requestParams = new Orm_Array($this->getRequest()->getParams());
        $this->cookie = $this->view->cookie = new Orm_Array($this->getRequest()->getCookie());
        $customer = Models_Customer::FindUser();

        $this->customer = $this->view->customer = $customer;

        Zend_Registry::set('view',$this->view);
        Zend_Registry::set('customer',$customer);


        $this->view->messageInfo = $this->_helper->FlashMessenger->getMessages('info');
        $this->view->messageError = $this->_helper->FlashMessenger->getMessages('error');
    }

    public function validIp($ips = array(), $redirect = null)
    {
        if (in_array($_SERVER['REMOTE_ADDR'], $ips)) {
            return true;
        }
        if ($redirect) {
            $this->_redirect($redirect);
        }
        return false;
    }
}

