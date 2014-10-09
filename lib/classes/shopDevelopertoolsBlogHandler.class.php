<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 22.08.14
 * Time: 14:59
 */
class shopDevelopertoolsBlogHandler
{

    public static function getImagesByPostSlug($slug)
    {

        wa('blog');
        $blogPostModel = new blogPostModel();
        $post = $blogPostModel->getBySlug($slug);
        return self::getImagesByPostId($post['id']);

    }

    public static function getImagesByPostId($id)
    {

        wa('blog');
        $blogPostModel = new blogPostModel();
        $post = $blogPostModel->getById($id);

        preg_match_all('{<img src=\"(.*)\">}siU', $post['text'], $matches);
        return $matches[1];

    }

    public static function getImagesByPostSlugHtml($slug, $width, $height)
    {
        wa('blog');
        $blogPostModel = new blogPostModel();
        $post = $blogPostModel->getBySlug($slug);
        return self::getImagesByPostIdHtml($post['id'], $width, $height);
    }


    public static function getImagesByPostIdHtml($id, $width, $height)
    {
        $links = self::getImagesByPostId($id);

        $html = "<ul class='developerToolsGetImagesByPostHtml'>";
        foreach ($links AS $link) {
            preg_match('{\/img\/(.*)$}siU', $link, $matches);
            $filename = $matches[1];
            $filepath = wa('blog')->getDataUrl('/img/', true, 'blog') . $filename;
            $html .= '<li><img src="' . $filepath . '" width="' . $width . '" height="' . $height . '"></li>';
        }

        $html .= "</ul>";
        return $html;
    }
}