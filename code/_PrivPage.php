<?php

class _PrivPage extends __PrivatePage
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _PrivPage::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ($root, $file, $layout=null)
    {
        _PrivPage::__instanceInit ($this);
        parent::__construct((($layout==null)?'layout-private':$layout));
        $this->PANEL($root.$file);
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
        }

        if (method_exists (get_parent_class (), '__get')) return parent::__get ($gsprn);
        throw new _UndefinedProperty ($gsprn);
    }

    public function __set ($gsprn, $sprv)
    {
        switch ($gsprn)
        {
        }
        if (method_exists (get_parent_class (), '__set')) parent::__set ($gsprn, $sprv);
    }

}