<?php

/**
 * Class for work with google tag manager ecommerce at BitrixFramework
 * 
 * @author ИП Бреский Дмитрий Игоревич <dimabresky@gmail.com>
 */
class BxGoogleTagManagerEcommerce {

    /**
     * @var self
     */
    protected static $__instanse = null;

    /**
     * @var \CMain
     */
    protected $_application = null;

    /**
     * @var array
     */
    protected $_allowMethods = array(
        "impressions", "detail", "add2cart", "remove_from_cart", "checkout", "purchase", "click_by_product"
    );

    /**
     * @var array
     */
    protected $_methods = array();

    /**
     * @var array
     */
    protected $_methods_parameters = array();

    /**
     * @var string
     */
    protected $_current_method = null;

    /**
     * @var string
     */
    public $currency = "RUB";

    /**
     * @global \CMain $APPLICATION
     */
    private function __construct() {
        global $APPLICATION;
        $this->_application = $APPLICATION;
    }

    /* private function __clone() {

      } */

    /**
     * @param string $method
     * @return mixed
     */
    protected function _findMethod($method) {

        return \array_search($method, $this->_methods);
    }

    /**
     * @return self
     */
    public static function getInstanse() {

        if (self::$__instanse === null) {
            self::$__instanse = new self;
        }

        return self::$__instanse;
    }

    /**
     * @param string $method
     * @throws \Exception
     */
    public function setMethod($method) {

        if (!in_array($method, $this->_allowMethods)) {
            throw new \Exception("Unknown google tag manager ecommerce method " . $method);
        }

        $this->_current_method = $method;
        $key = $this->_findMethod($this->_current_method);
        if ($key === false) {
            $this->_methods[] = $this->_current_method;
        }
    }

    /**
     * @param array $parameters
     * @throws \Exception
     */
    public function setMethodParameters($parameters) {

        if ($this->_current_method === null) {
            throw new \Exception("Method is not setted");
        }

        $this->_methods_parameters[$this->_findMethod($this->_current_method)] = $parameters;
    }

    /**
     * create data layer script for google tag manager
     */
    public function createDataLayer($dataLayerVarName = "dataLayer") {
        $this->_application->AddBufferContent(function () use ($dataLayerVarName) {
            ob_start();
            ?><script>(function (window) {
                                if (!window["<?= $dataLayerVarName ?>"]) {
                                    window["<?= $dataLayerVarName ?>"] = [];
                                }
                            })(window);</script><?
            foreach ($this->_methods as $key => $method) {

                $parameters = $this->_methods_parameters[$key];

                switch ($method) {

                    case "impressions":
                        ?>
                        <script>
                            (function (window) {
                                window["<?= $dataLayerVarName ?>"].push({
                                    ecommerce: {
                                        currencyCode: "<?= $this->currency ?>",
                                        impressions: <?= \Bitrix\Main\Web\Json::encode($parameters) ?>
                                    }
                                });
                            })(window);
                        </script>
                        <?
                        break;

                    case "detail":
                        ?>
                        <script>
                            (function (window) {
                                window["<?= $dataLayerVarName ?>"].push({
                                    ecommerce: {
                                        detail: {
                                            actionField: {list: "product"},
                                            products: <?= \Bitrix\Main\Web\Json::encode(array($parameters)) ?>
                                        }
                                    }
                                });
                            })(window);
                        </script>
                        <?
                        break;

                    case "add2cart":
                        ?>
                        <script>
                            (function (window) {
                                var d = window.document;
                                d.addEventListener("DOMContentLoaded", function () {
                                    d.querySelectorAll("[data-gtm-ecommerce-add2cart-products]").forEach(function (el) {
                                        el.addEventListener("click", function () {

                                            var products = window.JSON.parse(this.dataset.gtmEcommerceAdd2cartProducts);

                                            if (Array.isArray(products)) {
                                                window["<?= $dataLayerVarName ?>"].push({
                                                    event: "addToCart",
                                                    ecommerce: {
                                                        currencyCode: "<?= $this->currency ?>",
                                                        add: {
                                                            actionField: {list: products[0].list},
                                                            products: products
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                    });
                                });

                            })(window);

                        </script>
                        <?
                        break;

                    case "click_by_product":
                        ?>
                        <script>
                            (function (window) {
                                var d = window.document;
                                d.addEventListener("DOMContentLoaded", function () {
                                    d.querySelectorAll("[data-gtm-ecommerce-click-by-product]").forEach(function (el) {
                                        el.addEventListener("click", function () {

                                            var products = window.JSON.parse(this.dataset.gtmEcommerceClickByProduct);

                                            if (Array.isArray(products)) {
                                                window["<?= $dataLayerVarName ?>"].push({
                                                    event: "productClick",
                                                    ecommerce: {
                                                        currencyCode: "<?= $this->currency ?>",
                                                        click: {
                                                            actionField: {list: products[0].list},
                                                            products: products
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                    });
                                });

                            })(window);

                        </script>
                        <?
                        break;

                    case "remove_from_cart":
                        ?>
                        <script>
                            (function (window) {
                                var d = window.document;
                                d.addEventListener("DOMContentLoaded", function () {
                                    d.querySelectorAll("[data-gtm-ecommerce-remove-from-cart-products]").forEach(function (el) {
                                        el.addEventListener("click", function () {
                                            var products = window.JSON.parse(this.dataset.gtmEcommerceRemoveFromCartProducts);
                                            if (Array.isArray(products)) {
                                                window["<?= $dataLayerVarName ?>"].push({
                                                    event: "removeFromCart",
                                                    ecommerce: {
                                                        remove: {
                                                            products: products
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                    });
                                });
                            })(window);
                        </script>
                        <?
                        break;

                    case "checkout":
                        ?>
                        <script>
                            (function (window) {
                                window["<?= $dataLayerVarName ?>"].push({
                                    event: "checkout",
                                    ecommerce: {
                                        actionField: {step: "<?= $parameters["step"] ?>", option: "<?= $parameters["option"] ?>"},
                                        products: <?= \Bitrix\Main\Web\Json::encode($parameters["products"]) ?>
                                    }
                                });
                            })(window);
                        </script>
                        <?
                        break;

                    case "purchase":
                        ?>
                        <script>
                            (function (window) {
                                window["<?= $dataLayerVarName ?>"].push({
                                    event: "purchase",
                                    ecommerce: {
                                        actionField: <?= \Bitrix\Main\Web\Json::encode(array($parameters["action"])) ?>,
                                        products: <?= \Bitrix\Main\Web\Json::encode($parameters["products"]) ?>
                                    }
                                });
                            })(window);
                        </script>
                        <?
                        break;
                }
            }

            return ob_get_clean();
        });
    }

}
