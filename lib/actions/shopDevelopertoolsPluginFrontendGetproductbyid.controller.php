<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 16.07.14
 * Time: 1:41
 */
class shopDevelopertoolsPluginFrontendGetproductbyidController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::request('id');
        $this->response = (array)shopDevelopertoolsPluginViewHelper::getProductById($id);
    }
}
