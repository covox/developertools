<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 16.07.14
 * Time: 1:41
 */
class shopDevelopertoolsPluginFrontendGetreviewsbyfieldController extends waJsonController
{
    public function execute()
    {
        $field = waRequest::request('field');
        $value = waRequest::request('value');

        $status = waRequest::request('status');
        $status = !empty($status) ? waRequest::request('status') : 'approved';

        $order = waRequest::request('order');
        $order = !empty($order) ? waRequest::request('order') : 'DESC';

        $limit = waRequest::request('limit');
        $limit = !empty($limit) ? waRequest::request('limit') : false;


        $this->response = shopDevelopertoolsPluginViewHelper::getReviewsByField($field, $value, $status, $order,
            $limit);
    }
}
