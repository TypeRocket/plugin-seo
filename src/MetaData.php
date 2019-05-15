<?php


namespace TypeRocketSEO;


class MetaData
{

    public $title = null;
    public $itemId = null;
    public $meta = null;
    public $url = null;
    public $options = null;

    public function setup()
    {
        add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
        add_action( 'wp_head', [$this, 'head_data'], 1 );
        add_action( 'template_redirect', [$this, 'loaded'], 0 );
        add_filter( 'document_title_parts', [$this, 'title'], 100, 3 );
        remove_action( 'wp_head', 'rel_canonical' );
        add_action( 'wp', [$this, 'redirect'], 99, 1 );
    }

    public function loaded()
    {
        $this->itemId = (int) get_queried_object_id();
        $this->meta = tr_posts_field( 'seo.meta', $this->itemId );
        $this->url = get_the_permalink($this->itemId);
        $this->options = get_option('tr_seo_options');
    }

    // Page Title
    public function title( $title, $arg2 = null, $arg3 = null )
    {
        $newTitle = trim( $this->meta['title'] );

        if ( !empty($newTitle) ) {
            $this->title = $newTitle;
            return [$newTitle];
        } else {
            $this->title = $title;
            return $title;
        }

    }

    public function title_tag()
    {
        echo '<title>' . $this->title( '|', false, 'right' ) . "</title>";
    }

    public function getLastValidItem(array $options, $callback = 'esc_attr')
    {
        $result = null;
        foreach ($options as $option) {
            if(!empty($option)) {
                $value = call_user_func($callback, trim($option));

                if(!empty($value)) {
                    $result = $value;
                }
            }
        }

        return $result;
    }

    // 301 Redirect
    public function redirect()
    {
        if ( is_singular() && !empty($this->meta['redirect']) ) {
            wp_redirect( $this->meta['redirect'], 301 );
            exit;
        }
    }

    // head meta data
    public function head_data()
    {
        $object_id = (int) $this->itemId;

        // Vars
        $url        = $this->url;
        $seo        = $this->meta;
        $seo_global = $this->options;
        $desc       = esc_attr( $this->meta['description'] );

        // Images
        $img        = !empty($seo['meta_img']) ? wp_get_attachment_image_src( (int) $seo['meta_img'], 'full')[0] : null;

        // Basic
        $basicMeta['description'] = $desc;

        // OG
        $ogMeta['og:locale']      = $seo_global['og']['locale'] ?? null;
        $ogMeta['og:site_name']   = $seo_global['og']['site_name'] ?? null;
        $ogMeta['og:type']        = $this->getLastValidItem([ is_front_page() ? 'website' : 'article' , $seo['og_type'] ]);
        $ogMeta['og:title']       = esc_attr( $seo['og_title'] );
        $ogMeta['og:description'] = esc_attr( $seo['og_desc'] );
        $ogMeta['og:url']         = $url;
        $ogMeta['og:image']       = $img;

        // Canonical
        $canon            = esc_attr( $seo['canonical'] );

        // Robots
        $robots['index']  = esc_attr( $seo['index'] );
        $robots['follow'] = esc_attr( $seo['follow'] );

        $twMeta['twitter:card']        = esc_attr( $seo['tw_card'] );
        $twMeta['twitter:title']       = esc_attr( $seo['tw_title'] );
        $twMeta['twitter:description'] = esc_attr( $seo['tw_desc'] );
        $twMeta['twitter:site']        = $this->getLastValidItem([$seo_global['tw']['site'],$seo['tw_site']]);
        $twMeta['twitter:image']       = !empty($seo['tw_img']) ? wp_get_attachment_image_src( (int) $seo['tw_img'], 'full')[0] : null;
        $twMeta['twitter:creator']     = $this->getLastValidItem([$seo_global['tw']['creator'],$seo['tw_creator']]);

        // Basic
        foreach ($basicMeta as $basicName => $basicContent) {
            if(!empty($basicContent)) {
                echo "<meta name=\"{$basicName}\" content=\"{$basicContent}\" />";
            }
        }

        // Canonical
        if ( ! empty( $canon ) ) {
            echo "<link rel=\"canonical\" href=\"{$canon}\" />";
        } else {
            rel_canonical();
        }

        // Robots
        if ( ! empty( $robots ) ) {
            $robot_data = '';
            foreach ( $robots as $value ) {
                if ( ! empty( $value ) && $value != 'none' ) {
                    $robot_data .= $value . ', ';
                }
            }

            $robot_data = mb_substr( $robot_data, 0, - 2 );
            if ( ! empty( $robot_data ) ) {
                echo "<meta name=\"robots\" content=\"{$robot_data}\" />";
            }
        }

        // OG
        foreach ($ogMeta as $ogName => $ogContent) {
            if(!empty($ogContent)) {
                echo "<meta property=\"{$ogName}\" content=\"{$ogContent}\" />";
            }
        }

        // Twitter
        foreach ($twMeta as $twName => $twContent) {
            if(!empty($twContent)) {
                echo "<meta name=\"{$twName}\" content=\"{$twContent}\" />";
            }
        }

        $this->schemaJsonLd([
            'url' => $url,
            'description' => $desc,
            'og_global' => $seo_global,
        ]);
    }

    public function schemaJsonLd(array $data)
    {
        /** @var WP_Post $post */
        global $post;
        /**
         * @var $url
         * @var $og_global
         * @var $description
         */
        extract($data);

        if(empty($og_global)) { return; }

        $home = home_url();
        $lang = esc_js(str_replace('_', '-', $og_global['og']['locale']));
        $site = $og_global['og']['site_name'];
        $title = str_replace('&amp;', '&', esc_js($this->title));
        $desc = esc_js($description);

        // ISO 8601 Date Format
        $pub = get_the_date('c', $post);
        $mod = get_the_modified_date("c", $post);

        // Same As
        $same = array_map(function($value) {
            return esc_url_raw($value);
        }, $og_global['og']['social_links']);

        $schema_web = [
            "@context" => "http://schema.org/",
            "@graph"=> [
                [
                    "@type"=>"Organization",
                    "@id"=>"$home#organization",
                    "name"=>"$site",
                    "url"=> "$home",
                    "sameAs"=> $same
                ],
                [
                    "@type"=>"WebSite",
                    "@id"=> "$home#website",
                    "url"=> "$home",
                    "name"=> "$site",
                    "publisher"=>  [
                        "@id"=> "$home#organization"
                    ]
                ],
                [
                    "@type"=> "WebPage",
                    "@id"=> "$url#webpage",
                    "url"=> "$url",
                    "inLanguage"=> "$lang",
                    "name"=> "$title",
                    "isPartOf"=> [ "@id"=> "$home/#website"],
                    "datePublished"=> "$pub",
                    "dateModified"=> "$mod",
                    "description"=> "$desc"
                ]
            ]
        ];

        if($schema_web) {
            ?><script type="application/ld+json"><?php echo json_encode($schema_web); ?></script><?php
        }

        $biz = $og_global['schema']['enable'] ?? null;

        if($biz == '1') {
            $location = array_map('esc_js', $og_global['schema']['location']);
            $schema = array_map('esc_js', $og_global['schema']);
            $keyword = $schema['keyword'];
            $phone   = $schema['phone'];
            $price   = $schema['price_range'];

            $schema_biz = array_filter([
                "@context" => "http://schema.org/",
                "@type" => "ProfessionalService",
                "additionalType" => "http://www.productontology.org/id/$keyword",
                "url" => $home,
                "name" => $schema['name'],
                "description" => $schema['description'],
                "logo" => $schema['logo'] ? wp_get_attachment_image_src($schema['logo'], 'full')[0] : null,
                "image" => $schema['company_image'] ? wp_get_attachment_image_src($schema['company_image'], 'full')[0] : null,
                "telephone" => $phone,
                "priceRange" => $price,
                "address" => array_filter([
                    "@type" => "PostalAddress",
                    "addressLocality" => $location['city'],
                    "addressRegion" => $location['state'],
                    "addressCountry" => $location['country']
                ]),
                "sameAs"=> $same
            ]);

            ?><script type="application/ld+json"><?php echo json_encode($schema_biz); ?></script><?php
        }
    }
}