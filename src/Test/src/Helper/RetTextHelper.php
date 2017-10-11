<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 06.05.17
 * Time: 11:06 AM
 */

namespace rollun\test\Helper;


use Zend\View\Helper\AbstractHelper;
use Zend\View\Model\ViewModel;

class RetTextHelper extends AbstractHelper
{

    const TEMPLATE_NAME = 'test-helper::red-text';
    const TEMPLATE_JS_NAME = 'test-helper::red-js-text';

    /**
     * @param string $text
     * @return string
     */
    public function __invoke($text)
    {
        $data = "";
        //return $this->render($text);
        $data = "<p class=\"text-danger\">$text . (Обычная строка)</p>";
        $data .= $this->render($text, static::TEMPLATE_NAME);
        $data .= $this->render($text, static::TEMPLATE_JS_NAME);
        return $data;
    }

    protected function render($text, $template)
    {
        $view = $this->getView();
        $model = new ViewModel();
        $model->setTemplate($template);
        $model->setVariables(['redText' => $text]);
        $render = $view->render($model);
        return $render;
    }

}