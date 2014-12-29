<?php
/*
  Template Name: Paylink Payment Page
 */

get_header();
?>

<div id="main-content" class="main-content">
    <div id="primary" class="content-area">
        <div id="content" class="site-content" role="main">
            <?php
            if (have_posts()) {
                the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>> 
                <div class="entry-content">
                <?php
                //twentyfourteen_post_thumbnail();
                the_title( '<header class="entry-header"><h1 class="entry-title">', '</h1></header><!-- .entry-header -->' );
                the_content();
                ?>
                    <div id="PayForm"></div>
                    <script type="text/javascript">
                        $ = jQuery;
                        var paylinkJs = new Paylink(
                                "U1cyfbhFbmpBjMgGayNh6EPASSYXQA2Png0JmBbshs33-oz5dPAEzgEra5zK_ZGRjS3KYSnGJI2c0lO2N5ehnx4b3MoqUIN6ieZ_9kyiThd7ThaNQCOPFesJsGPQ3q-uSz6w1BvN6x2ZQOyYUAXxxw",
                                {
                                    form: {
                                        identifier: {
                                            placeholder: "AC9999",
                                            pattern: "[A-Za-z]{2}[0-9]{4}",
                                            label: "Account No"
                                        },
                                        amount: {
                                            order: 1,
                                            label: "Total Amount"
                                        }
                                    }
                                }
                            );
                        paylinkJs.billPayment("#PayForm");
                    </script>
                <?php
                edit_post_link( __( 'Edit', 'twentyfourteen' ), '<span class="edit-link">', '</span>' );
                
                // If comments are open or we have at least one comment, load up the comment template.
                if (comments_open() || get_comments_number()) {
                    comments_template();
                }
                ?>
                </div>
                <?php
            }
            ?>
            </article>
        </div><!-- #content -->
    </div><!-- #primary -->
<?php get_sidebar('content'); ?>
</div><!-- #main-content -->
<?php
get_footer();