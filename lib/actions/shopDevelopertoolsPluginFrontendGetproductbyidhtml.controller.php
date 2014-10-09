<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 16.07.14
 * Time: 1:41
 */
class shopDevelopertoolsPluginFrontendGetproductbyidhtmlController extends waController
{
    public function execute()
    {
        $id = waRequest::request('id');
        $this->response = (array)developerToolsShopViewHelper::getProductById($id);
        print shopDevelopertoolsPluginViewHelper::getProductByIdHtml($id);
    }
}
