<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 22.08.14
 * Time: 15:12
 */
class shopDevelopertoolsPluginBlogViewHelper
{

    public static function getImagesByPostId($id)
    {
        return shopDevelopertoolsBlogHandler::getImagesByPostId($id);
    }

    public static function getImagesByPostSlug($slug)
    {
        return shopDevelopertoolsBlogHandler::getImagesByPostSlug($slug);
    }

    public static function getImagesByPostIdHtml($id, $width = '', $height = '')
    {
        return shopDevelopertoolsBlogHandler::getImagesByPostIdHtml($id, $width, $height);
    }

    public static function getImagesByPostSlugHtml($slug, $width = '', $height = '')
    {
        return shopDevelopertoolsBlogHandler::getImagesByPostSlugHtml($slug, $width, $height);
    }
}