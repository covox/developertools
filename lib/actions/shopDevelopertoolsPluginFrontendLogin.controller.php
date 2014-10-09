<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 05.09.14
 * Time: 13:59
 */
class shopDevelopertoolsPluginFrontendLoginController extends waController
{

    public function execute()
    {

        $auth = wa()->getAuth();

        $params['login'] = waRequest::post('login');
        $params['password'] = waRequest::post('password');
        $params['remember'] = waRequest::post('rememberme');

        try {
            if ($auth->auth($params)) {
                $this->afterAuth();
            }
        } catch (waException $e) {
//            $error = $e->getMessage();
            $this->redirect(wa()->getRouteUrl('/login'));
        }
    }

    protected function checkAuthConfig()
    {
        // check auth config
        $auth = wa()->getAuthConfig();
        if (!isset($auth['auth']) || !$auth['auth']) {
            throw new waException(_ws('Page not found'), 404);
        }
        /*
        // check auth app and url
        $login_url = wa()->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/login');
        if (wa()->getConfig()->getRequestUrl(false) != $login_url) {
            $this->redirect($login_url);
        }
        */
    }

    protected function saveReferer()
    {
        if (!waRequest::param('secure')) {
            $referer = waRequest::server('HTTP_REFERER');
            $root_url = wa()->getRootUrl(true);
            if ($root_url != substr($referer, 0, strlen($root_url))) {
                $this->getStorage()->del('auth_referer');
                return;
            }
            $referer = substr($referer, strlen($this->getConfig()->getHostUrl()));
            if (!in_array($referer, array(
                wa()->getRouteUrl('/login'),
                wa()->getRouteUrl('/forgotpassword'),
                wa()->getRouteUrl('/signup')
            ))
            ) {
                $this->getStorage()->set('auth_referer', $referer);
            }
        }
    }


    protected function checkXMLHttpRequest()
    {
        // Voodoo magic: reload page when user performs an AJAX request after session died.
        if (waRequest::isXMLHttpRequest() && (waRequest::param('secure') || wa()->getEnv() == 'backend')) {
            //
            // The idea behind this is quite complicated.
            //
            // When browser expects JSON and gets this response then the error handler is called.
            // Default error handler (see wa.core.js) looks for the wa-session-expired header
            // and reloads the page when it's found.
            //
            // On the other hand, when browser expects HTML, it's most likely to insert it to the DOM.
            // In this case <script> gets executed and browser reloads the whole layout to show login page.
            // (This is also the reason to use 200 HTTP response code here: no error handler required at all.)
            //
            header('wa-session-expired: 1');
            echo _ws('Session has expired. Please reload current page and log in again.') . '<script>window.location.reload();</script>';
            exit;
        }
    }

    protected function afterAuth()
    {
        $referer = waRequest::server('HTTP_REFERER');
        $referer = substr($referer, strlen($this->getConfig()->getHostUrl()));
        $checkout_url = wa()->getRouteUrl('shop/frontend/checkout');

        if ($referer && !strncasecmp($referer, $checkout_url, strlen($checkout_url))) {
            $url = $referer;
        } elseif ($referer != wa()->getRouteUrl('/login')) {
            $url = $this->getStorage()->get('auth_referer');
        }
        if (empty($url)) {
            if (waRequest::param('secure')) {
                $url = $this->getConfig()->getCurrentUrl();
            } else {
                $url = wa()->getRouteUrl('shop/frontend/my');
            }
        }
        $this->getStorage()->del('auth_referer');
        $this->getStorage()->del('shop/cart');
        $this->redirect($url);
    }
}