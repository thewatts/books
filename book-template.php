<?php

  /* Template Name: Private - Campus Book Creation */

?>
<?php

  // get user and current campus
  $lg_user = lg_get_current_user();
  $campus_permalink = pods_var( 2, 'url' );
  $campus_permalink = pods_sanitize($campus_permalink);

  // determine page type and set data array to send to
  // function that determines access to this page
  $page_type = 'campus_admin';
  $data = array(
    'lg_user' => $lg_user,
    'current_campus' => $campus_permalink
  );

  $semester_permalink = "spring-2013";
  $semester = pods('semesters', $semester_permalink);

  $campus = pods('campuses', $campus_permalink);
  $campus_name = $campus->display('name');

  $semester_name = $semester->display('name');
  // if the user has basic access to this page
  if  ( lg_determine_page_access($page_type, $data) ) :

    // if they don't pass the above test - they don't get access to the below.

  ?>
<?php

  // GET OUR BOOK PAGES YO!

  $pages = lg_get_book_pages_for_campus($campus_permalink);

?>

<!DOCTYPE html>

<!--[if lt IE 7 ]> <html class="ie ie6 no-js" dir="ltr" lang="en-US"> <![endif]-->
<!--[if IE 7 ]>    <html class="ie ie7 no-js" dir="ltr" lang="en-US"> <![endif]-->
<!--[if IE 8 ]>    <html class="ie ie8 no-js" dir="ltr" lang="en-US"> <![endif]-->
<!--[if IE 9 ]>    <html class="ie ie9 no-js" dir="ltr" lang="en-US"> <![endif]-->
<!--[if gt IE 9]><!--><html class="no-js" dir="ltr" lang="en-US"><!--<![endif]-->
<!-- the "no-js" class is for Modernizr. -->

<head id="www-sitename-com" data-template-set="html5-reset-wordpress-theme" profile="http://gmpg.org/xfn/11">
  <?php

    $stylesheet_link = get_stylesheet_directory_uri();
    $stylesheet_link .= '/css/books.css';

  ?>
 <link rel="stylesheet" href="<?php echo $stylesheet_link; ?>">

</head>

<body class="single single-note postid-124 logged-in">
  <!-- cover -->
  <div class="wrapper">
    <div class="cover">
      <div class="cover-info">
        <span class="cover-semester">
          <?php echo $semester_name; ?>
        </span>
        <span class="cover-campus">
          <?php echo $campus_name; ?>
        </span>
      </div><!-- //cover-info -->
      <img src="http://nlc.s3.amazonaws.com/lg-assets/books/bg-cover.jpg">
    </div>
  </div>
  <!-- // cover -->
  <!-- blank divider -->
  <div class="wrapper">
  </div>
  <!-- //blank divider -->

  <?php foreach ($pages as $full_page) : ?>

    <div class="wrapper">

      <?php $even_odd_check = 1; ?>

      <?php //$count = 1; ?>

      <?php foreach ($full_page as $page) : ?>

      <?php //if ($count <= 6) : ?>

        <?php if ($even_odd_check&1) : ?>

          <?php $class = "left"; ?>

        <?php else : ?>

          <?php $class = "right"; ?>

        <?php endif; ?>

        <div class="column column-<?php echo $class; ?>">
          <?php lg_display_book_page($page); ?>
          <?php //print_r($page); ?>

          <?php if($page['page_type'] != 'table_of_contents') : ?>
            <?php if ($page['page_num']) : ?>
              <span class="page-num-wrapper">
                <img src="bg-page-num.png">
                <span class="page-num">
                  <?php echo $page['page_num']; ?>
                </span>
              </span>
            <?php endif; ?>
          <?php endif; ?>

        </div>

        <?php $even_odd_check++; ?>
        <?php //$count++; ?>
      <?php // endif; ?>
      <?php endforeach; ?>

  </div><!-- //wrapper -->

  <?php endforeach; ?>

</body>
</html>
<?php endif; ?>