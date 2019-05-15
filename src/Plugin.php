<?php
namespace TypeRocketSEO;

class Plugin
{

    public $version = '4.1';
    public $postTypeMeta = null;


    public function __construct()
    {
        add_action( 'typerocket_loaded', [$this, 'setup']);
    }

    public function setup()
    {
        if ( ! defined( 'WPSEO_URL' ) && ! defined( 'AIOSEOP_VERSION' ) ) {
            define( 'TR_SEO', $this->version );

            if ( is_admin() ) {
                $page = (new OptionsPage())->setup();
            }

            $pt = (new PostTypeMeta())->setup();
            $meta = (new MetaData())->setup();
        }
    }
}