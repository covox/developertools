<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 05.08.14
 * Time: 14:32
 */
class shopDevelopertoolsPluginViewHelper extends waViewHelper
{

    private static $routing;
    private static $view;

    private static function getView()
    {
        if (!empty(self::$view)) {
            $view = self::$view;
        } else {
            $view = waSystem::getInstance()->getView();
        }
        return $view;
    }

    private static function getRouting()
    {
        if (!empty(self::$routing)) {
            $routing = self::$routing;
        } else {
            $routing = wa()->getRouting();
        }
        return $routing;
    }

    /*
    * Эти четыре функции нужны для внутреннего использования.
    */

    public static function getThemePath()
    {
        $theme = waRequest::param('theme', 'default');
        $theme_path = wa()->getDataPath('themes', true) . '/' . $theme;
        if (!file_exists($theme_path) || !file_exists($theme_path . '/theme.xml')) {
            $theme_path = wa()->getAppPath() . '/themes/' . $theme;
        }
        return $theme_path;
    }

    /*
     * Call from Smarty: {developerToolsShopViewHelper::getCurrencies()}
     */
    public static function getCurrencies()
    {
        return wa('shop')->getConfig()->getCurrencies();
    }

    /*
     * Call from Smarty: {developerToolsShopViewHelper::getReviewsByField($field, $value, $status = 'approved', $order='DESC', $limit=false)}
     *
     * @field - field in table shop_product_reviews. For example 'product_id'
     * @status - status of the review in database, e.g. 'approved'
     * @order - direction of the order in database, e,g,'ASC' or 'DESC'
     * @value - specific value to search in database
     * @limit - limiting the number of results
     *
     */
    public static function getReviewsByField($field, $value, $status = 'approved', $order = 'DESC', $limit = false)
    {
        $rm = new shopProductReviewsModel();
        $reviews = shopDevelopertoolsReviewHandler::getReviews($field, $value, $status, $order, $limit);

        foreach ($reviews AS $i => $review) {
            $reviews[$i] = $rm->getReview($review['id'], true);
        }
        return $reviews;
    }

    /*
     * Call from Smarty: {developerToolsShopViewHelper::getReviewsByFieldHtml($field, $value, $status = 'approved', $order='DESC', $limit=false)}
     *
     * @field - field in table shop_product_reviews. For example 'product_id'
     * @status - status of the review in database, e.g. 'approved'
     * @order - direction of the order in database, e,g,'ASC' or 'DESC'
     * @value - specific value to search in database
     * @limit - limiting the number of results
     *
     */
    public static function getReviewsByFieldHtml($field, $value, $status = 'approved', $order = 'DESC', $limit = false)
    {
        $view = self::getView();
        $rm = new shopProductReviewsModel();

        $reviews = shopDevelopertoolsReviewHandler::getReviews($field, $value, $status, $order, $limit);
        $output = '';

        $view->assign('reply_allowed', false);

        foreach ($reviews AS $review) {
            $review = $rm->getReview($review['id'], true);
            $view->assign('review', $review);
            $output .= $view->fetch(self::getThemePath() . '/review.html');
        }

        return $output;
    }

    /*
     * Call from Smarty: {$reviewsCount = developerToolsShopViewHelper::getReviewsTotalCount($product_id)}
     */
    public static function getReviewsTotalCount($product_id)
    {
        $rm = new shopProductReviewsModel();
        return $rm->count($product_id);
    }

    /*
     * Эта функция используется для асинхронных вызовов.
     * Вариант {$wa->shop->product($product_id)} мне не подходит.
     */
    public static function getProductById($product_id)
    {
        return shopDevelopertoolsProductHandler::prepareProductArray($product_id);
    }

    /*
     * Call from Smarty: {developerToolsShopViewHelper::getProductByIdHtml($product_id)}
     */
    public static function getProductByIdHtml($id)
    {
        $product = new shopProduct($id);
        $view = self::getView();
        shopDevelopertoolsProductHandler::prepareProduct($product);
        return $view->fetch(self::getThemePath() . '/product.html');
    }
}
