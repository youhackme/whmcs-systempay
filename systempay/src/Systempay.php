<?php

namespace Systempay;

class Systempay
{
    // private $key;

    private $params = [];
    private $nb_products = 0;


    /**
     * Magic method that allows you to use getters and setters on each systempay parameters
     * Remember to not keep the 'vads' prefix in your accessor function name
     *
     * @param       String $method name of the accessor
     * @param              array   [optional] $args list of arguments
     *
     * @return      $this
     * @throws      InvalidArgumentException
     */
    public function __call($method, $args)
    {
        if (function_exists($method)) {
            return call_user_func_array($method, $args);
        }
        if (preg_match("/get_(.*)/", $method, $matches)) {
            return $this->params["vads_{$matches[1]}"];
        }
        if (preg_match("/set_(.*)/", $method, $matches)) {
            if (count($args) != 1) {
                throw new InvalidArgumentException($method . ' takes one argument.');
            }
            $this->params["vads_{$matches[1]}"] = $args[0];

            return $this;
        }
    }

    /**
     * Method to do massive assignement of parameters
     *
     * @param           array $params associative array of systempay parameters
     *
     * @return         $this
     */
    public function set_params($params)
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Get all systempay parameters
     * @return      array associative array of systempay parameters
     */
    public function get_params()
    {
        return $this->params;
    }


    /**
     * Generate systempay signature and add it to the parameters array
     *
     * @param $key The certificate key
     *
     * @return $this
     */
    public function set_signature($key)
    {
        ksort($this->params);
        $s = "";
        foreach ($this->params as $n => $v) {
            $s .= $v . "+";
        }
        $s                         .= $key;
        $this->params['signature'] = sha1($s);

        return $this;
    }

    /**
     * Return systempay signature
     * @return      String systempay signature
     */
    public function get_signature()
    {
        return $this->params['signature'];
    }

    /**
     * Defines the total amount of the order. If you doesn't give the amount in parameter, it will be automatically
     * calculated by the sum of products you've got in your basket
     *
     * @param int $amount , systempay format
     *
     * @return     $this
     */
    public function set_amount($amount = 0)
    {
        $this->params['vads_amount'] = 0;
        if ($amount) {
            $this->params['vads_amount'] = 100 * $amount;
        } else {//calcul du montant à partir du tableau de paramètre
            array_where($this->params, function ($key, $value) {
                if (preg_match("/vads_product_amount([0-9]+)/", $key, $match)) {
                    $this->params['vads_amount'] += $this->params["vads_product_qty{$match[1]}"] * $value;
                }
            });
        }

        return $this;
    }

    /**
     * Get total amount of the order
     *
     * @param    bool $decimal if true, you get a decimal otherwise you get standard systempay amount format (int)
     *
     *
     * @return      float
     */
    public function get_amount($decimal = true)
    {
        return $decimal ? $this->params['vads_amount'] / 100 : $this->params['vads_amount'];
    }

    /**
     * Return HTML SystempPay form
     *
     * @param       String $button html code of the submit button
     *
     * @return      string
     */
    public function get_form($button)
    {
        $html_form = '<form method="post" action="https://paiement.systempay.fr/vads-payment/" accept-charset="UTF-8">';
        foreach ($this->params as $key => $value) {
            $html_form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        $html_form .= $button;
        $html_form .= "</form>";

        return $html_form;
    }

    /**
     * Add a product to the order
     *
     * @param       array $product , must have the following keys : 'label,amount,type,ref,qty'
     *
     * @return      $this
     */
    public function add_product($product)
    {
        $this->params                     = array_merge($this->params, [
            "vads_product_label{$this->nb_products}"  => $product["label"],
            "vads_product_amount{$this->nb_products}" => (int)$product["amount"] * 100,
            "vads_product_type{$this->nb_products}"   => $product["type"],
            "vads_product_ref{$this->nb_products}"    => $product["ref"],
            "vads_product_qty{$this->nb_products}"    => $product["qty"],
        ]);
        $this->params['vads_nb_products'] = $this->nb_products += 1;

        return $this;
    }

}