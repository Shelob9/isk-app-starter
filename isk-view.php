<?php $front = false; if ( is_home() || is_front_page() || is_page( 'itisknown') ) $front = true; ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<h1 class="entry-title <?php if ( $front ) echo 'isk-front-title'; ?>"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
		<?php if ( $front ) : ?>
			<button class="button facet-reset-button" onclick="FWP.reset()">Reset</button>
		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'app_starter' ) ); ?>
		<?php
		wp_link_pages( array(
			'before' => '<div class="page-links">' . __( 'Pages:', 'app_starter' ),
			'after'  => '</div>',
		) );
		?>
	</div><!-- .entry-content -->


	<footer class="entry-footer">

	</footer><!-- .entry-footer -->
</article><!-- #post-## -->
