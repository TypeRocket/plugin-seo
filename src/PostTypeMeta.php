<?php
namespace TypeRocketSEO;

class PostTypeMeta
{
    public $postTypes = null;

    public function setup()
    {
        $pt = apply_filters('tr_seo_post_types', $this->postTypes);
        $this->postTypes = $pt ?? get_post_types( ['public' => true] );
        add_action('tr_model', [$this, 'tr_model'], 9999999999, 2 );

        if ( is_admin() ) {
            add_action( 'add_meta_boxes', [$this, 'add_meta_boxes']);
        }

        return $this;
    }

    public function tr_model( $model )
    {
        global $post;

        if($model instanceof \TypeRocket\Models\WPPost) {
            $fillable = $model->getFillableFields();
            /** @var \WP_Post $data */
            $types = get_post_types(['public' => true]);
            if(!empty($fillable) && !empty($types[$post->post_type]) ) {
                $model->appendFillableField('seo');
            }
        }
    }

    public function add_meta_boxes()
    {
        $args = [
            'label'    => __('Search Engine Optimization'),
            'priority' => 'low',
            'callback' => [$this, 'callback']
        ];
        $obj = new \TypeRocket\Register\MetaBox( 'tr_seo', null, $args );
        $obj->addPostType( $this->postTypes )->register();
    }

    public function callback()
    {
        // build form
        $form = new \TypeRocket\Elements\Form();
        $form->setDebugStatus( false );
        $form->setGroup( 'seo.meta' );
        $seo_plugin = $this;

        // General
        $general = function() use ($form, $seo_plugin){

            $title = [
                'label' => __('Page Title')
            ];

            $desc = [
                'label' => __('Search Result Description')
            ];

            echo $form->text( 'title', ['id' => 'tr_title'], $title );
            echo $form->textarea( 'description', ['id' => 'tr_description'], $desc );

            $seo_plugin->general();
        };

        // Social
        $social = function() use ($form){

            $og_title = [
                'label' => __('Title'),
                'help'  => __('The open graph protocol is used by social networks like FB, Google+ and Pinterest. Set the title used when sharing.')
            ];

            $og_desc = [
                'label' => __('Description'),
                'help'  => __('Set the open graph description to override "Search Result Description". Will be used by FB, Google+ and Pinterest.')
            ];

            $og_type = [
                'label' => __('Page Type'),
                'help'  => __('Set the open graph page type. You can never go wrong with "Article".')
            ];

            $img = [
                'label' => __('Image'),
                'help'  => __("The image is shown when sharing socially using the open graph protocol. Will be used by FB, Google+ and Pinterest. Need help? Try the Facebook <a href=\"https://developers.facebook.com/tools/debug/og/object/\" target=\"_blank\">open graph object debugger</a> and <a href=\"https://developers.facebook.com/docs/sharing/best-practices\" target=\"_blank\">best practices</a>.")
            ];

            echo $form->text( 'og_title', [], $og_title );
            echo $form->textarea( 'og_desc', [], $og_desc );
            echo $form->select( 'og_type', [], $og_type )->setOptions(['Article' => 'article', 'Profile' => 'profile']);
            echo $form->image( 'meta_img', [], $img );
        };

        // Twitter
        $twitter = function() use ($form){

            $tw_img = [
                'label' => __('Image'),
                'help'  => __("Images for a 'summary_large_image' card should be at least 280px in width, and at least 150px in height. Image must be less than 1MB in size. Do not use a generic image such as your website logo, author photo, or other image that spans multiple pages.")
            ];

            $tw_help = __("Need help? Try the Twitter <a href=\"https://cards-dev.twitter.com/validator/\" target=\"_blank\">card validator</a>, <a href=\"https://dev.twitter.com/cards/getting-started\" target=\"_blank\">getting started guide</a>, and <a href=\"https://business.twitter.com/en/help/campaign-setup/advertiser-card-specifications.html\" target=\"_blank\">advertiser creative specifications</a>.");

            $card_opts = [
                __('Summary')             => 'summary',
                __('Summary large image') => 'summary_large_image',
            ];

            echo $form->text('tw_site')->setLabel('Site Twitter Account')->setAttribute('placeholder', '@username');
            echo $form->text('tw_creator')->setLabel('Page Author\'s Twitter Account')->setAttribute('placeholder', '@username');
            echo $form->select('tw_card')->setOptions($card_opts)->setLabel('Card Type')->setSetting('help', $tw_help);
            echo $form->text('tw_title')->setLabel('Title')->setAttribute('maxlength', 70 );
            echo $form->textarea('tw_desc')->setLabel('Description')->setHelp( __('Description length is dependent on card type.') );
            echo $form->image('tw_img', [], $tw_img );
        };

        // Advanced
        $advanced = function() use ($form){
            global $post;

            $link = esc_url_raw(get_permalink($post));

            $redirect = [
                'label'    => __('301 Redirect'),
                'help'     => __('Move this page permanently to a new URL.') . '<a href="#tr_redirect" id="tr_redirect_lock">' . __('Unlock 301 Redirect') .'</a>',
                'readonly' => true
            ];

            $follow = [
                'label' => __('Robots Follow?'),
                'desc'  => __("Don't Follow"),
                'help'  => __('This instructs search engines not to follow links on this page. This only applies to links on this page. It\'s entirely likely that a robot might find the same links on some other page and still arrive at your undesired page.')
            ];

            $follow_opts = [
                __('Not Set')      => 'none',
                __('Follow')       => 'follow',
                __("Don't Follow") => 'nofollow'
            ];

            $index_opts = [
                __('Not Set')     => 'none',
                __('Index')       => 'index',
                __("Don't Index") => 'noindex'
            ];

            $canon = [
                'label' => __('Canonical URL'),
                'help'  => __('The canonical URL that this page should point to, leave empty to default to permalink.')
            ];

            $help = [
                'label' => __('Robots Index?'),
                'desc'  => __("Don't Index"),
                'help'  => __('This instructs search engines not to show this page in its web search results.')
            ];

            echo $form->text( 'canonical', [], $canon );
            echo $form->text( 'redirect', ['readonly' => 'readonly', 'id' => 'tr_redirect'], $redirect );
            echo $form->row([
                $form->select( 'follow', [], $follow )->setOptions($follow_opts),
                $form->select( 'index', [], $help )->setOptions($index_opts)
            ]);

            $schema = "<a class=\"button\" href=\"https://search.google.com/structured-data/testing-tool/u/0/#url=$link\" target=\"_blank\">Analyze Schema</a>";
            $speed = "<a class=\"button\" href=\"https://developers.google.com/speed/pagespeed/insights/?url=$link\" target=\"_blank\">Analyze Page Speed</a>";

            echo $form->rowText('<div class="control-label"><span class="label">Google Tools</span></div><div class="control"><div class="button-group">'.$speed.$schema.'</div></div>');
        };

        $tabs = new \TypeRocket\Elements\Tabs();
        $tabs->addTab( [
            'id'       => 'seo-general',
            'title'    => __("Basic"),
            'callback' => $general
        ])
            ->addTab( [
                'id'      => 'seo-social',
                'title'   => __("Social"),
                'callback' => $social
            ])
            ->addTab( [
                'id'      => 'seo-twitter',
                'title'   => __("Twitter Cards"),
                'callback' => $twitter
            ])
            ->addTab( [
                'id'      => 'seo-advanced',
                'title'   => __("Advanced"),
                'callback' => $advanced
            ])
            ->render();
    }

    public function general()
    {
        global $post; ?>
        <div id="tr-seo-preview" class="control-group">
            <h4><?php _e('Example Preview'); ?></h4>

            <p><?php _e('Google has <b>no definitive character limits</b> for page "Titles" and "Descriptions". However, your Google search result may look something like:'); ?>

            <div class="tr-seo-preview-google">
        <span id="tr-seo-preview-google-title-orig">
          <?php echo mb_substr( strip_tags( $post->post_title ), 0, 59 ); ?>
        </span>
                <span id="tr-seo-preview-google-title">
          <?php
          $title = tr_posts_field( 'seo.meta.title' );
          if ( ! empty( $title ) ) {
              $s  = strip_tags( $title );
              $tl = mb_strlen( $s );
              echo mb_substr( $s, 0, 59 );
          } else {
              $s  = strip_tags( $post->post_title );
              $tl = mb_strlen( $s );
              echo mb_substr( $s, 0, 59 );
          }

          if ( $tl > 59 ) {
              echo '...';
          }
          ?>
        </span>

                <div id="tr-seo-preview-google-url">
                    <?php echo get_permalink( $post->ID ); ?>
                </div>
                <span id="tr-seo-preview-google-desc-orig">
          <?php echo mb_substr( strip_tags( $post->post_content ), 0, 150 ); ?>
        </span>
                <span id="tr-seo-preview-google-desc">
          <?php
          $desc = tr_posts_field( 'seo.meta.description' );
          if ( ! empty( $desc ) ) {
              $s  = strip_tags( $desc );
              $dl = mb_strlen( $s );
              echo mb_substr( $s, 0, 150 );
          } else {
              $s  = strip_tags( $post->post_content );
              $dl = mb_strlen( $s );
              echo mb_substr( $s, 0, 150 );
          }

          if ( $dl > 150 ) {
              echo ' ...';
          }
          ?>
        </span>
            </div>
        </div>
    <?php }
}