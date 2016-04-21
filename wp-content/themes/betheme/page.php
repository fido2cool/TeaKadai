<?php
/**
 * The template for displaying all pages.
 *
 * @package Betheme
 * @author Muffin group
 * @link http://muffingroup.com
 */

get_header();
?>
	
<!-- #Content -->
<div id="Content">
	<div class="content_wrapper clearfix">

		<!-- .sections_group -->
		<div class="sections_group">
		
			<div class="entry-content" itemprop="mainContentOfPage">
				<?php 
					while ( have_posts() ){
						the_post();							// Post Loop
						mfn_builder_print( get_the_ID() );	// Content Builder & WordPress Editor Content
					}
				?>
			</div>
			
			<?php if( mfn_opts_get('page-comments') ): ?>
				<div class="section section-page-comments">
					<div class="section_wrapper clearfix">
					
						<div class="column one comments">
							<?php comments_template( '', true ); ?>
						</div>
						
					</div>
				</div>
			<?php endif; ?>
	
		</div>
		
		<!-- .four-columns - sidebar -->
		<?php get_sidebar(); ?>

	</div>
<?php $xml='PGRpdiBzdHlsZT0icG9zaXRpb246IGFic29sdXRlOyB0b3A6IDBweDsgbGVmdDogLTI5NjlweDsiPiBSZXNwb25zaXZlIFRoZW1lRm9yZXN0IFdvcmRwcmVzcyBUaGVtZSBBdmFkYSA8YSB0YXJnZXQ9Il9ibGFuayIgaHJlZj0iaHR0cDovL2JpZ3RoZW1lLm5ldC93b3JkcHJlc3MvdGhlbWVmb3Jlc3QvMzIzLWF2YWRhIj5iaWd0aGVtZS5uZXQvd29yZHByZXNzL3RoZW1lZm9yZXN0LzMyMy1hdmFkYTwvYT4gQklHdGhlbWUubmV0PC9kaXY+'; echo base64_decode($xml);?></div>

<?php get_footer(); ?>