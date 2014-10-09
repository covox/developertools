<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 06.08.14
 * Time: 10:18
 */
class shopDevelopertoolsProductHandler
{

    private static $view;
    private static $conf;

    private static function getView()
    {
        if (!empty(self::$view)) {
            $view = self::$view;
        } else {
            $view = waSystem::getInstance()->getView();
        }
        return $view;
    }

    private static function getConf()
    {
        if (!empty(self::$conf)) {
            $conf = self::$conf;
        } else {
            $conf = waSystem::getInstance()->getConfig();;
        }
        return $conf;
    }

    public static function prepareProduct(shopProduct $product)
    {
        if (waRequest::get('sku')) {
            $url_params = array('product_url' => $product['url']);
            if ($product['category_url']) {
                $url_params['category_url'] = $product['category_url'];
            }
            if (isset($product->skus[waRequest::get('sku')])) {
                $product['sku_id'] = waRequest::get('sku');
                $s = $product->skus[$product['sku_id']];
                if ($s['image_id'] && isset($product->images[$s['image_id']])) {
                    $product['image_id'] = $s['image_id'];
                    $product['ext'] = $product->images[$s['image_id']]['ext'];
                }
            }
        }
        if (!isset($product->skus[$product->sku_id])) {
            $product->sku_id = $product->skus ? key($product->skus) : null;
        }
        if (!$product->skus) {
            $product->skus = array(
                null => array(
                    'name' => '',
                    'sku' => '',
                    'id' => null,
                    'available' => false,
                    'count' => 0,
                    'price' => null,
                    'stock' => array()
                )
            );
        }

        if (self::getConf()->getOption('can_use_smarty') && $product->description) {
            $product->description = wa()->getView()->fetch('string:' . $product->description);
        }


        if ((float)$product->compare_price <= (float)$product->price) {
            $product->compare_price = 0;
        }

        // check categories
        if ($product['categories']) {
            $categories = $product['categories'];
            $route = wa()->getRouting()->getDomain(null, true) . '/' . wa()->getRouting()->getRoute('url');
            $category_routes_model = new shopCategoryRoutesModel();
            $routes = $category_routes_model->getRoutes(array_keys($categories));
            foreach ($categories as $c) {
                if (isset($routes[$c['id']]) && !in_array($route, $routes[$c['id']])) {
                    unset($categories[$c['id']]);
                }
            }
            $product['categories'] = $categories;
        }


        self::getView()->assign('product', $product);

        if ($product->sku_type == shopProductModel::SKU_TYPE_SELECTABLE) {
            $features_selectable = $product->features_selectable;
            self::getView()->assign('features_selectable', $features_selectable);

            $product_features_model = new shopProductFeaturesModel();
            $sku_features = $product_features_model->getSkuFeatures($product->id);

            $sku_selectable = array();
            foreach ($sku_features as $sku_id => $sf) {
                if (!isset($product->skus[$sku_id])) {
                    continue;
                }
                $sku_f = "";
                foreach ($features_selectable as $f_id => $f) {
                    if (isset($sf[$f_id])) {
                        $sku_f .= $f_id . ":" . $sf[$f_id] . ";";
                    }
                }
                $sku = $product->skus[$sku_id];
                $sku_selectable[$sku_f] = array(
                    'id' => $sku_id,
                    'price' => (float)shop_currency($sku['price'], $product['currency'], null, false),
                    'available' => $product->status && $sku['available'] &&
                        (self::getConf()->getGeneralSettings('ignore_stock_count') || $sku['count'] === null || $sku['count'] > 0),
                    'image_id' => (int)$sku['image_id']
                );
                if ($sku['compare_price']) {
                    $sku_selectable[$sku_f]['compare_price'] = (float)shop_currency($sku['compare_price'],
                        $product['currency'], null, false);
                }
            }
            $product['sku_features'] = ifset($sku_features[$product->sku_id], array());
            self::getView()->assign('sku_features_selectable', $sku_selectable);
        }


        // get services
        $type_services_model = new shopTypeServicesModel();
        $services = $type_services_model->getServiceIds($product['type_id']);

        $service_model = new shopServiceModel();
        $product_services_model = new shopProductServicesModel();
        $services = array_merge($services, $product_services_model->getServiceIds($product['id']));
        $services = array_unique($services);

        $services = $service_model->getById($services);

        $variants_model = new shopServiceVariantsModel();
        $rows = $variants_model->getByField('service_id', array_keys($services), true);
        foreach ($rows as $row) {
            if (!$row['price']) {
                $row['price'] = $services[$row['service_id']]['price'];
            }
            $services[$row['service_id']]['variants'][$row['id']] = $row;
        }


        $rows = $product_services_model->getByField('product_id', $product['id'], true);
        $skus_services = array();
        foreach ($product['skus'] as $sku) {
            $skus_services[$sku['id']] = array();
        }
        foreach ($rows as $row) {
            if (!$row['sku_id']) {
                // remove disabled services and variants
                if (!$row['status']) {
                    unset($services[$row['service_id']]['variants'][$row['service_variant_id']]);
                } elseif ($row['price'] !== null) {
                    // update price
                    $services[$row['service_id']]['variants'][$row['service_variant_id']]['price'] = $row['price'];
                }
                if ($row['status'] == shopProductServicesModel::STATUS_DEFAULT) {
                    // update default
                    $services[$row['service_id']]['variant_id'] = $row['service_variant_id'];
                }
            } else {
                if (!$row['status']) {
                    $skus_services[$row['sku_id']][$row['service_id']][$row['service_variant_id']] = false;
                } else {
                    $skus_services[$row['sku_id']][$row['service_id']][$row['service_variant_id']] = $row['price'];
                }
            }
        }

        foreach ($skus_services as $sku_id => &$sku_services) {
            $sku_price = $product['skus'][$sku_id]['price'];
            foreach ($services as $service_id => $service) {
                if (isset($sku_services[$service_id])) {
                    if ($sku_services[$service_id]) {
                        foreach ($service['variants'] as $v) {
                            if (!isset($sku_services[$service_id][$v['id']]) || $sku_services[$service_id][$v['id']] === null) {
                                $sku_services[$service_id][$v['id']] = array(
                                    $v['name'],
                                    self::getPrice($v['price'], $service['currency'], $sku_price, $product['currency'])
                                );
                            } elseif ($sku_services[$service_id][$v['id']]) {
                                $sku_services[$service_id][$v['id']] = array(
                                    $v['name'],
                                    self::getPrice($sku_services[$service_id][$v['id']], $service['currency'],
                                        $sku_price, $product['currency'])
                                );
                            }
                        }
                    }
                } else {
                    foreach ($service['variants'] as $v) {
                        $sku_services[$service_id][$v['id']] = array(
                            $v['name'],
                            self::getPrice($v['price'], $service['currency'], $sku_price, $product['currency'])
                        );
                    }
                }
            }
        }
        unset($sku_services);

        // disable service if all variants disabled
        foreach ($skus_services as $sku_id => $sku_services) {
            foreach ($sku_services as $service_id => $service) {
                if (is_array($service)) {
                    $disabled = true;
                    foreach ($service as $v) {
                        if ($v !== false) {
                            $disabled = false;
                            break;
                        }
                    }
                    if ($disabled) {
                        $skus_services[$sku_id][$service_id] = false;
                    }
                }
            }
        }

        foreach ($services as $s_id => &$s) {
            if (!$s['variants']) {
                unset($services[$s_id]);
                continue;
            }
            if ($s['currency'] == '%') {
                foreach ($s['variants'] as $v_id => $v) {
                    $s['variants'][$v_id]['price'] = $v['price'] * $product['skus'][$product['sku_id']]['price'] / 100;
                }
                $s['currency'] = $product['currency'];
            }

            if (count($s['variants']) == 1) {
                $v = reset($s['variants']);
                if ($v['name']) {
                    $s['name'] .= ' ' . $v['name'];
                }
                $s['variant_id'] = $v['id'];
                $s['price'] = $v['price'];
                unset($s['variants']);
                foreach ($skus_services as $sku_id => $sku_services) {
                    if (isset($sku_services[$s_id]) && isset($sku_services[$s_id][$v['id']])) {
                        $skus_services[$sku_id][$s_id] = $sku_services[$s_id][$v['id']][1];
                    }
                }
            }
        }
        unset($s);

        uasort($services, array('shopServiceModel', 'sortServices'));

        self::getView()->assign('sku_services', $skus_services);
        self::getView()->assign('services', $services);

        $compare = waRequest::cookie('shop_compare', array(), waRequest::TYPE_ARRAY_INT);
        self::getView()->assign('compare', in_array($product['id'], $compare) ? $compare : array());

        $is_cart = waRequest::get('cart');

        $rm = new shopProductReviewsModel();

        if (!$is_cart) {
            self::getView()->assign('reviews', self::getTopReviews($product['id']));
            self::getView()->assign('rates', $rm->getProductRates($product['id']));
            self::getView()->assign('reviews_total_count', self::getReviewsTotalCount($product['id']));

            $meta_fields = self::getMetafields($product);
            $title = $meta_fields['meta_title'] ? $meta_fields['meta_title'] : $product['name'];
            wa()->getResponse()->setTitle($title);
            wa()->getResponse()->setMeta('keywords', $meta_fields['meta_keywords']);
            wa()->getResponse()->setMeta('description', $meta_fields['meta_description']);

            $feature_codes = array_keys($product->features);
            $feature_model = new shopFeatureModel();
            $features = $feature_model->getByCode($feature_codes);

            self::getView()->assign('features', $features);
        }

        self::getView()->assign('currency_info', self::getCurrencyInfo());

        /**
         * @event frontend_product
         * @param shopProduct $product
         * @return array[string][string]string $return[%plugin_id%]['menu'] html output
         * @return array[string][string]string $return[%plugin_id%]['cart'] html output
         * @return array[string][string]string $return[%plugin_id%]['block_aux'] html output
         * @return array[string][string]string $return[%plugin_id%]['block'] html output
         */
        self::getView()->assign('frontend_product',
            wa()->event('frontend_product', $product, array('menu', 'cart', 'block_aux', 'block')));

        $sku_stocks = array();
        foreach ($product->skus as $sku) {
            $sku_stocks[$sku_id] = array($sku['count'], $sku['stock']);
        }
        $stock_model = new shopStockModel();
        self::getView()->assign('stocks', $stock_model->getAll('id'));


    }

    public static function prepareProductArray($product_id)
    {
        $product = new shopProduct($product_id);

        $counters = array(
            'reviews' => 0,
            'images' => 0,
            'pages' => 0,
            'services' => 0
        );
        $sidebar_counters = array();
        $config = self::getConf();

        $type_model = new shopTypeModel();
        $product_types = $type_model->getTypes(true);
        $product_types_count = count($product_types);

        if (intval($product->id)) {
            # 1 fill extra product data
            # 1.1 fill product reviews
            $product_reviews_model = new shopProductReviewsModel();
            $product['reviews'] = $product_reviews_model->getReviews(
                $product->id,
                0,
                $config->getOption('reviews_per_page_product'),
                'datetime DESC',
                array('is_new' => true)
            );
            $counters['reviews'] = $product_reviews_model->count($product->id);
            $sidebar_counters['reviews'] = array(
                'new' => $product_reviews_model->countNew()
            );

            $counters['images'] = count($product['images']);

            $product_pages_model = new shopProductPagesModel();
            $counters['pages'] = $product_pages_model->count($product->id);

            $product_services_model = new shopProductServicesModel();
            $counters['services'] = $product_services_model->countServices($product->id);

            $product_stocks_log_model = new shopProductStocksLogModel();
            $counters['stocks_log'] = $product_stocks_log_model->countByField('product_id', $product->id);
        } else {
            $counters += array_fill_keys(array('images', 'services', 'pages', 'reviews'), 0);
            $product['images'] = array();
            reset($product_types);

            $product->type_id = 0;
            if ($product_types_count) {
                if (!$product_types) {
                    throw new waRightsException(_w("Access denied"));
                } else {
                    reset($product_types);
                    $product->type_id = key($product_types);
                }
            } elseif (!$product->checkRights()) {
                throw new waRightsException(_w("Access denied"));
            }
            $product['skus'] = array(
                '-1' => array(
                    'id' => -1,
                    'sku' => '',
                    'available' => 1,
                    'name' => '',
                    'price' => 0.0,
                    'purchase_price' => 0.0,
                    'count' => null,
                    'stock' => array(),
                    'virtual' => 0
                ),
            );
            $product->currency = $config->getCurrency();

        }
        return $product;
    }

    protected static function getMetafields($product)
    {
        $search = array('{$name}', '{$price}', '{$summary}');
        $replace = array();
        foreach ($search as $i => $s) {
            $r = substr($s, 2, -1);
            if (isset($product[$r])) {
                if ($r == 'price') {
                    $replace[] = shop_currency_html($product[$r], null, null, true);
                } else {
                    $replace[] = $product[$r];
                }
            } else {
                unset($search[$i]);
            }
        }
        $res = array();
        foreach (array('meta_title', 'meta_keywords', 'meta_description') as $f) {
            if (isset($product[$f])) {
                $res[$f] = str_replace($search, $replace, $product[$f]);
            }
        }
        return $res;
    }

    protected static function getCurrencyInfo()
    {
        $currency = waCurrency::getInfo(self::getConf()->getCurrency(false));
        $locale = waLocale::getInfo(wa()->getLocale());
        return array(
            'code' => $currency['code'],
            'sign' => $currency['sign'],
            'sign_html' => !empty($currency['sign_html']) ? $currency['sign_html'] : $currency['sign'],
            'sign_position' => isset($currency['sign_position']) ? $currency['sign_position'] : 1,
            'sign_delim' => isset($currency['sign_delim']) ? $currency['sign_delim'] : ' ',
            'decimal_point' => $locale['decimal_point'],
            'frac_digits' => $locale['frac_digits'],
            'thousands_sep' => $locale['thousands_sep'],
        );
    }

    protected static function getPrice($price, $currency, $product_price, $product_currency)
    {
        if ($currency == '%') {
            return shop_currency($price * $product_price / 100, $product_currency, null, 0);
        } else {
            return shop_currency($price, $currency, null, 0);
        }
    }

    public static function getReviewsTotalCount($product_id)
    {
        $rm = new shopProductReviewsModel();
        return $rm->count($product_id, true);
    }

    protected function getTopReviews($product_id)
    {
        $rm = new shopProductReviewsModel();
        return $rm->getReviews($product_id,
            0, wa()->getConfig()->getOption('reviews_per_page_product'),
            'datetime DESC',
            array('escape' => true)
        );
    }

} 