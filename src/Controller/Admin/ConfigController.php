<?php
/**
 * Article module config controller
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Pi Engine http://www.xoopsengine.org
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article\Controller\Admin;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Module\Article\Form\DraftCustomForm;
use Module\Article\Form\DraftCustomFilter;
use Module\Article\File;

/**
 * Public action controller for config module
 */
class ConfigController extends ActionController
{
    const ELEMENT_EDIT_PATH = 'var/article/config/elements.edit.php';
    
    const FORM_MODE_NORMAL   = 'normal';
    const FORM_MODE_EXTENDED = 'extension';
    const FORM_MODE_CUSTOM   = 'custom';
    
    /**
     * Rendering form
     * 
     * @param Zend\Form\Form $form     Form instance
     * @param string         $message  Message assign to template
     * @param bool           $isError  Whether is error message
     */
    protected function renderForm($form, $message = null, $isError = false)
    {
        $params = array('form' => $form);
        if ($isError) {
            $params['error'] = $message;
        } else {
            $params['message'] = $message;
        }
        $this->view()->assign($params);
        $this->view()->assign('form', $form);
    }
    
    /**
     * Saving config result into file
     * 
     * @param array|int  $elements     Elements want to display, if mode is not custom, its value is mode name
     * @param array      $allElements  All elements in article edit page
     * @param array      $options      Elements of normal and extended mode
     * @return bool 
     */
    protected function saveFormConfig($elements, $allElements, $options = array())
    {
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
 * The all elements for displaying are showed as follows, if you choose custom mode,
 * you need to return the element wants to display in `elements` field.
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
            $codeContent .= '    \'mode\'     => \'' . self::FORM_MODE_CUSTOM . '\',' . "\r\n";
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
     * Default action, jump to form configuration page
     * 
     * @return ViewModel 
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('', array(
            'action'    => 'form',
        ));
    }

    /**
     * Configuring whether to display form in draft edit page
     * 
     * @return ViewModel 
     */
    public function formAction()
    {
        $draftForm = new \Module\Article\Form\DraftEditForm;
        $items     = $draftForm->getExistsFormElements();
        $this->view()->assign('items', $items);
        
        $form = new DraftCustomForm('custom', array('elements' => $items));
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => 'form')),
            'method'  => 'post',
            'class'   => 'form-horizontal',
        ));
        $this->view()->assign('title', __('Configuration Form'));
        $this->view()->assign('form', $form);
        $this->view()->assign('custom', self::FORM_MODE_CUSTOM);
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            if (self::FORM_MODE_CUSTOM != $post['mode']) {
                foreach (array_keys($items) as $name) {
                    $post[$name] = 0;
                }
            }
            $form->setData($post);
            $form->setInputFilter(new DraftCustomFilter(array('elements' => $items)));

            if (!$form->isValid()) {
                return $this->renderForm($form, __('There are some error occured'), true);
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
                self::FORM_MODE_NORMAL   => $draftForm->getDefaultElements(self::FORM_MODE_NORMAL),
                self::FORM_MODE_EXTENDED => $draftForm->getDefaultElements(self::FORM_MODE_EXTENDED),
            );
            $result  = $this->saveFormConfig($elements, $items, $options);
            if (!$result) {
                return $this->renderForm($form, __('Can not save data!'), true);
            }
            
            $this->renderForm($form, __('Data saved successful!'), false);
        }
    }
}
