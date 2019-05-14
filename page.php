<?php
if ( ! function_exists( 'add_action' )) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

// Setup Form
$form = tr_form()->useJson();
?>

<h1>SEO Options</h1>
<div class="typerocket-container">
    <?php
    echo $form->open();

    // OG
    $og = function() use ($form) {
        $form->setGroup( $this->optionsName . '.og');
        echo $form->text('Locale')->setDefault('en_US');
        echo $form->text('Site Name');
        echo $form->items('Social Links')->setInputType('url')->setHelp('Used by Schema to populate the "Same As" fields. Social media URLs are a common value for "Same As".');
    };

    // API
    $twitter = function() use ($form) {
        $form->setGroup( $this->optionsName . '.tw');
        echo $form->text('Site', ['placeholder' => '@typerocket']);
        echo $form->text('Creator', ['placeholder' => '@typerocket']);
        echo '<div class="control-section"><a href="https://developer.twitter.com/en/docs/tweets/optimize-with-cards/guides/getting-started.html">Twitter card documentation</a></div>';
    };

    // Save
    $save = $form->submit( 'Save' );

    // Layout
    tr_tabs()->setSidebar( $save )
        ->addTab( 'Open Graph', $og )
        ->addTab( 'Twitter', $twitter )
        ->render( 'box' );
    echo $form->close();
    ?>

</div>
