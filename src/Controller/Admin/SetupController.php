<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link         http://code.pialog.org for the Pi Engine source repository
 * @copyright    Copyright (c) Pi Engine http://pialog.org
 * @license      http://pialog.org/license.txt New BSD License
 */

namespace Module\Article\Controller\Admin;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Module\Article\Form\DraftCustomForm;
use Module\Article\Form\DraftCustomFilter;
use Module\Article\File;
use Module\Article\Form\DraftEditForm;
use Module\Article\Service;
use Module\Article\Installer\Resource\Route;
use Module\Article\Form\RouteCustomForm;
use Module\Article\Form\RouteCustomFilter;
use Zend\EventManager\Event;

/**
 * Config controller
 * 
 * Feature list:
 * 
 * 1. Custom config the form to display in draft edit page
 * 
 * @author Zongshu Lin <lin40553024@163.com>
 */
class SetupController extends ActionController
{
    const ELEMENT_EDIT_PATH = 'var/article/config/elements.edit.php';
    
    const FORM_MODE_NORMAL   = 'normal';
    const FORM_MODE_EXTENDED = 'extension';
    const FORM_MODE_CUSTOM   = 'custom';
    
    /**
     * Saving config result into file
     * 
     * @param array|int  $elements     Elements want to display, if mode is 
     *                                 not custom, its value is mode name
     * @param array      $allElements  All elements in article edit page
     * @param array      $options      Elements of normal and extended mode
     * @return bool 
     */
    protected function saveFormConfig(
        $elements, 
        $allElements, 
        $options = array()
    ) {
        $content =<<<EOD
<?php
/**

EOD;
        
        $normalContent =<<<EOD
 * The elements of normal mode for displaying are showed as follows:
 * 

EOD;
        if (isset($options[self::FORM_MODE_NORMAL])) {
            foreach ($options[self::FORM_MODE_NORMAL] as $value) {
                $normalContent .= ' * ' . $value . "\r\n";
            }
            $content .= $normalContent;
            $content .= " *\r\n";
        }
        
        $extendedContent =<<<EOD
 * The elements of extension mode for displaying are showed as follows:
 * 

EOD;
        if (isset($options[self::FORM_MODE_EXTENDED])) {
            foreach ($options[self::FORM_MODE_EXTENDED] as $value) {
                $extendedContent .= ' * ' . $value . "\r\n";
            }
            $content .= $extendedContent;
            $content .= " *\r\n";
        }
        
        $all =<<<EOD
 * The all elements for displaying are showed as follows, if you choose custom 
 * mode, you need to return the element wants to display in `elements` field.
 * For example:
 * return array(
 *     'mode'     => 'custom',
 *     'elements' => array(
 *         'title',
 *         'subtitle',
 *         ...
 *     ),
 * );      

EOD;
        foreach (array_keys($allElements) as $value) {
            $all .= ' * ' . $value . "\r\n";
        }
        $content .= $all;
        $content .= "**/\r\n";
        
        $codeContent =<<<EOD
return array(

EOD;
        if (is_string($elements)) {
            $codeContent .= '    \'mode\'     => \'' . $elements . '\',' . "\r\n";
        } else {
            $codeContent .= '    \'mode\'     => \'' 
                         . self::FORM_MODE_CUSTOM 
                         . '\',' . "\r\n";
            $codeContent .= '    \'elements\' => array(' . "\r\n";
            foreach ($elements as $element) {
                $codeContent .= '        \'' . $element . '\',' . "\r\n";
            }
            $codeContent .= '    ),' . "\r\n";
        }
        $content .= $codeContent;
        $content .= ');' . "\r\n";
        
        $filename = Pi::path(self::ELEMENT_EDIT_PATH);
        $result   = File::addContent($filename, $content);
        
        return $result;
    }
    
    /**
     * Get route configuration parameters
     * 
     * @param array  $configs
     * @return array 
     */
    protected function canonizeConfig($configs)
    {
        if (empty($configs)) {
            return array();
        }
        
        $routeConfig = array();
        foreach ($configs as $name => $config) {
            $routeConfig = $config;
            $routeConfig['name'] = $name;
            
            unset($routeConfig['options']);
            $routeConfig = array_merge($routeConfig, $config['options']);
            
            unset($routeConfig['default']);
            $routeConfig = array_merge(
                $routeConfig,
                $config['options']['default']
            );
            break;
        }
        
        return $routeConfig;
    }
    
    /**
     * Save route parameters into file
     * 
     * @param array $config
     * @return bool 
     */
    protected function saveRouteConfig($config)
    {
        $config['priority'] = $config['priority'] ?: 100;
        
        // Assemble options fields value
        $options = '';
        $optionalFields = array(
            'structure_delimiter', 'param_delimiter',
            'key_value_delimiter', 'route',
        );
        foreach ($optionalFields as $field) {
            if (!empty($config[$field])) {
                $options .= '            '
                         . "'" . $field . "' => '" 
                         . $config[$field] 
                         . "',\r\n";
            }
        }
        
        // Assemble default fields value
        $defaultFields = array('module', 'controller', 'action');
        $default = '';
        foreach ($defaultFields as $field) {
            if (!empty($config[$field])) {
                $default .= '                '
                         . "'" . $field . "' => '" 
                         . $config[$field] 
                         . "',\r\n";
            }
        }
        
        $content =<<<EOD
<?php
return array(
    '{$config['name']}' => array(
        'section'  => '{$config['section']}',
        'priority' => '{$config['priority']}',
        'type'     => '{$config['type']}',
        'options'  => array(
{$options}
            'default'  => array(
{$default}
            ),
        ),
    ),
);

EOD;
        
        $filename = sprintf(
            '%s/%s/config/%s', 
            Pi::path('var'),
            $this->getModule(), 
            Route::RESOURCE_CONFIG_NAME
        );
        
        $result = File::addContent($filename, $content);
        
        return $result;
    }

    /**
     * Default action, jump to form configuration page
     * 
     * @return ViewModel 
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('', array('action' => 'form'));
    }

    /**
     * Config whether to display form in draft edit page
     * 
     * @return ViewModel 
     */
    public function formAction()
    {
        $items = DraftEditForm::getExistsFormElements();
        $this->view()->assign('items', $items);
        
        $form = new DraftCustomForm('custom', array('elements' => $items));
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => 'form')),
            'method'  => 'post',
            'class'   => 'form-horizontal',
        ));
        $options = Service::getFormConfig();
        if (!empty($options)) {
            $data['mode'] = $options['mode'];
            if (self::FORM_MODE_CUSTOM == $options['mode']) {
                foreach ($options['elements'] as $value) {
                    $data[$value] = 1;
                }
            }
            $form->setData($data);
        }
        
        $this->view()->assign('title', __('Form Configuration'));
        $this->view()->assign('form', $form);
        $this->view()->assign('custom', self::FORM_MODE_CUSTOM);
        $this->view()->assign('action', 'form');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            if (self::FORM_MODE_CUSTOM != $post['mode']) {
                foreach (array_keys($items) as $name) {
                    $post[$name] = 0;
                }
            }
            $neededElements = DraftEditForm::getNeededElements();
            $form->setData($post);
            $form->setInputFilter(
                new DraftCustomFilter($post['mode'], 
                array('needed' => $neededElements))
            );

            if (!$form->isValid()) {
                return Service::renderForm(
                    $this, 
                    $form, 
                    __('Items marked red is required!')
                );
            }
            
            $data     = $form->getData();
            $elements = array();
            if (self::FORM_MODE_CUSTOM == $data['mode']) {
                foreach (array_keys($items) as $name) {
                    if (!empty($data[$name])) {
                        $elements[] = $name;
                    }
                }
            } else {
                $elements = $data['mode'];
            }
            $options = array(
                self::FORM_MODE_NORMAL   => DraftEditForm::getDefaultElements(
                    self::FORM_MODE_NORMAL
                ),
                self::FORM_MODE_EXTENDED => DraftEditForm::getDefaultElements(
                    self::FORM_MODE_EXTENDED
                ),
            );
            $result  = $this->saveFormConfig($elements, $items, $options);
            if (!$result) {
                return Service::renderForm($this, $form, __('Can not save data!'));
            }
            
            Service::renderForm($this, $form, __('Data saved successful!'), false);
        }
    }
    
    /**
     * Save route parameter
     * 
     * @return ViewModel 
     */
    public function routeAction()
    {
        $module   = $this->getModule();
        $filename = sprintf(
            '%s/%s/config/%s', 
            Pi::path('var'),
            $module, 
            Route::RESOURCE_CONFIG_NAME
        );
        
        // Get custom configuration parameters
        $fields  = array(
            'name', 'section', 'priority', 'type', 'structure_delimiter',
            'param_delimiter', 'key_value_delimiter', 'route',
            'module', 'controller', 'action',
        );
        $configs = array();
        $configFile = '';
        if (file_exists($filename)) {
            $configs = include $filename;
            $configs = $this->canonizeConfig($configs);
            $configFile = $filename;
        }
        foreach ($fields as $field) {
            if (!isset($configs[$field])) {
                $configs[$field] = '';
            }
        }
        
        // Get custom class
        $class = '';
        if (isset($configs['type']) and class_exists($configs['type'])) {
            $class = $configs['type'];
        }
        
        // Get form
        $form = new RouteCustomForm();
        $form->setAttributes(array(
            'action'    => $this->url(
                '',
                array('action' => 'route', 'status' => 'edit')
            ),
            'class'     => 'form-horizontal',
        ));
        $form->setData($configs);
        
        // Get current route
        $rowRoute = Pi::model('route')->select(array('module' => $module));
        foreach ($rowRoute as $row) {
            list($moduleName, $routeName) = explode('-', $row->name, 2);
            if ('article' == $routeName) {
                $routeName .= ' [default]';
            } else {
                $routeName .= ' [custom]';
            }
            break;
        }
        
        $this->view()->assign(array(
            'configs'   => $configs,
            'class'     => $class,
            'form'      => $form,
            'status'    => $this->params('status', 'browse'),
            'filename'  => $configFile,
            'route'     => $routeName,
        ));
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new RouteCustomFilter);
            if (!$form->isValid()) {
                return Service::renderForm(
                    $this,
                    $form,
                    __('There are some error occured!')
                );
            }
            
            $data = $form->getData();
            $result = $this->saveRouteConfig($data);
            if (!$result) {
                return Service::renderForm(
                    $this,
                    $form,
                    __('Can not save configuration data!')
                );
            }
            
            return $this->redirect()->toRoute('', array('action' => 'route'));
        }
    }
    
    /**
     * Delete custom route configuration file
     * 
     * @return ViewModel 
     */
    public function deleteRouteAction()
    {
        $filename = sprintf(
            '%s/%s/config/%s', 
            Pi::path('var'),
            $this->getModule(), 
            Route::RESOURCE_CONFIG_NAME
        );
        
        if (file_exists($filename)) {
            @unlink($filename);
        }
        
        return $this->redirect()->toRoute('', array('action' => 'route'));
    }
    
    public function setupRouteAction()
    {
        Pi::service('log')->active(false);
        
        $module = $this->getModule();
        $route  = $this->params('custom', 0);
        
        $return = array('status' => false);
        $resourceClass = 'Pi\Application\Installer\Resource\Route';
        if (!class_exists($resourceClass)) {
            $return['message'] = __('Route resource class is not exists!');
            echo json_encode($return);
            exit;
        }
        $methodAction = 'updateAction';
        if (!method_exists($resourceClass, $methodAction)) {
            $return['message'] = __('Update method is not exists!');
            echo json_encode($return);
            exit;
        }
        
        if ($route) {
            $optionsFile = sprintf(
                '%s/%s/config/%s',
                Pi::path('var'),
                $module, 
                Route::RESOURCE_CONFIG_NAME
            );
        } else {
            $optionsFile = Pi::path('module') .  '/article/config/route.php';
        }
        
        if (!file_exists($optionsFile)) {
            $return['message'] = __('Config file is not exists!');
            echo json_encode($return);
            exit;
        }
        $options = include $optionsFile;
        $class   = '';
        foreach ($options as $config) {
            $class = $config['type'];
            break;
        }
        if (!class_exists($class)) {
            $return['message'] = __('Route class is not exists!');
            echo json_encode($return);
            exit;
        }
        Pi::model('route')->delete(array('module' => $module));
        
        if (empty($options) || !is_array($options)) {
            $options = array();
        }
        
        $event = new Event;
        $event->setParam('module', $module);
        $resourceHandler = new $resourceClass($options);
        $resourceHandler->setEvent($event);
        $ret = $resourceHandler->$methodAction();
        
        $return['status'] = $ret;
        $return['message'] = $ret ? __('Success!') : __('Setup failed!');
        echo json_encode($return);
        exit;
    }
}
