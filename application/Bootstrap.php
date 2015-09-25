<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    public function run()
    {
        $front   = $this->getResource('FrontController');
        $default = $front->getDefaultModule();
        if (null === $front->getControllerDirectory($default)) {
            throw new Zend_Application_Bootstrap_Exception(
                'No default controller directory registered with front controller'
            );
        }

        $front->setParam('bootstrap', $this);
        $response = $front->dispatch();

        if ($front->returnResponse()) {
            return $response;
        }

    }

    protected function _initViewHelpers(){
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view   = $layout->getView();

        /**
         * @var $view Zend_View
         */
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        $view->headTitle('TEST');
        $view->headTitle()->setSeparator(' :: ');

    }

    protected function _initRequest()
    {
        // Обеспечение инициализации контроллера
        $this->bootstrap('FrontController');

        // Извлечение фронт-контроллера из реестра загрузки
        $front = $this->getResource('FrontController');

        $request = new Zend_Controller_Request_Http();


        $front->setRequest($request);

        // Обеспечение сохранения запроса в реестре загрузки
        return $request;
    }

    public function _initRoute(){

        $request = new Zend_Controller_Request_Http();
        $curMenu = new Models_Menu();
        $route = array();

        $findMenu = Models_Menu::DB()->findFirst(array('Url'=>$request->getPathInfo()));
        if($findMenu){
            $curMenu = $findMenu;
        }

        if(!$curMenu->getId()) {
            foreach (explode('/', $request->getPathInfo()) as $v) {
                if (strlen($v)) {
                    $route[] = $v;
                }
            }

            $cnt = 0;
            do {
                $found = 0;

                if (isset($route[$cnt]) && ($m = ActiveRecord::useStatic('Models_Menu')->findByUrlAndParentId($route[$cnt], ($curMenu->getId() ? $curMenu->getId() : null)))) {
                    if (!$curMenu->getId() || ($curMenu->getId() == $m->getParentId())) {
                        $curMenu = $m;
                        $found = 1;
                        unset($route[$cnt]);
                    }
                }
                $cnt++;
            } while ($found);

            $getParams = array();
            $ki = 0;
            foreach ($route as $k => $get) {
                if ($ki == 0) {
                    $getParams[$get] = @$route[$k + 1];
                    $ki = 1;
                } else {
                    $ki = 0;
                }
            }
        }


        // Получаем маршрут, по-умолчанию
        $router = Zend_Controller_Front::getInstance()->getRouter();
        // создаем пользовательские маршруты
        // маршрут для статических страниц
        if($curMenu->getId() and $curMenu->getController()) {
            $routes = array(
                'controller' => $curMenu->getController(),
                'action'     => $curMenu->getAction()?:'index',
            );
            $routes += $getParams;
            $route_static = new Zend_Controller_Router_Route(
                $request->getPathInfo(),
                $routes
            );
            $router->addRoute('static', $route_static);
        }

        Zend_Registry::set('CurMenu',$curMenu);
    }
}

