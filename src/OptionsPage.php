<?php
namespace TypeRocketSEO;

class OptionsPage
{
    public $optionsName = 'tr_seo_options';

    public function setup()
    {
        add_action('tr_model', [$this, 'tr_model'], 9999999999, 2 );
        add_action('admin_menu', [$this, 'admin_menu']);

        return $this;
    }

    public function admin_menu()
    {
        add_options_page( 'SEO Options', 'SEO Options', 'manage_options', 'tr_seo_options', [$this, 'add_options_page']);
    }

    public function add_options_page()
    {
        do_action('tr_theme_options_page', $this);
        echo '<div class="wrap">';
        include( __DIR__ . '/../page.php' );
        echo '</div>';
    }

    public function tr_model( $model )
    {
        if ($model instanceof \TypeRocket\Models\WPOption) {
            if (!empty( $model->getFillableFields() )) {
                $model->appendFillableField( $this->optionsName );
            }
        }
    }
}