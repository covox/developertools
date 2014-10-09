<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 06.08.14
 * Time: 13:05
 */
class shopDevelopertoolsReviewHandler
{

    public static function getReviews($field, $value, $status = 'approved', $order = 'DESC', $limit = false)
    {
        $model = new waModel();
        $result = $model->select(' FROM ')->where($field . ' = ' . $value . ' AND status LIKE "' . $status . '"')->order('id ' . $order)->limit($limit);
        return $result->fetchAll();
    }
}