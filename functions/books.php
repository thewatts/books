<?php


/* ========================================================================================================================

BOOK FUNCTIONS | Life Groups

======================================================================================================================== */

//
// Gets all of our book data for a single group
//
function lg_get_group_book_data($group) {

  // get data
  $name               = $group->display('name');
  $number             = $group->display('life_group_number');
  $campus_permalink   = $group->field('campus.permalink');
  $categories         = $group->field('category');
  $demographic        = $group->display('demographic_' . $campus_permalink);
  $description        = $group->display('description');
  $description        = strip_tags($description);
  $primary_leadership = $group->field('primary_leadership');
  $co_leadership      = $group->field('co_leadership');
  $location           = $group->display('meeting_location_name');
  $host               = $group->display('host');
  $meeting_time       = $group->display('meeting_time');
  $childcare          = $group->field('childcare');

  //cleanup

  // primary leadership
  $primary_leadership_array = lg_get_group_leader_book_data($primary_leadership);

  // co leadership
  $co_leadership_array = array();
  if ( ! empty($co_leadership) ) :
    foreach ($co_leadership as $co_leader) :
      $leader_info = lg_get_group_leader_book_data($co_leader);
      array_push($co_leadership_array, $leader_info);
    endforeach;
  endif;

  // categories
  if ($categories) :
    $categories_array = array();
    foreach ($categories as $category) :
      $category_name = $category['name'];
      array_push($categories_array, $category_name);
    endforeach;
  endif;

  // signup page
  $signup_page = 'lifegroups.newlifechurch.tv/' . $campus_permalink . '/' . $number;

  $book_data = array(
    'name' => $name,
    'number' => $number,
    'description' => $description,
    'primary_leadership' => $primary_leadership_array,
    'co_leadership' => $co_leadership_array,
    'category' => $categories_array,
    'demographic' => $demographic,
    'location' => $location,
    'host' => $host,
    'meeting_time' => $meeting_time,
    'childcare' => $childcare,
    'signup' => $signup_page
  );

  return $book_data;

}

//
// FUNCTION used if you want to get book pages without "book sorting"
//
function lg_get_book_pages_for_campus_pre_sort($campus_permalink) {

  // vision page details
  $vision_data = array(
    'page_num' => 1,
    'page_name' => 'NLC Life Group Vision'
  );

  // set our initial array for unsorted pages!
  $unsorted_pages = array();

  // get all of our categories
  $params = array(
    'orderby' => 'name ASC',
    'limit' => -1
  );

  $categories = pods('categories')->find($params);
  $total_categories = $categories->total();

  // if we have categories
  if ( $total_categories > 0 ) :

    // create our table of contents
    $table_of_contents = array();

    // and add the vision page
    array_push($table_of_contents, $vision_data);

    // table of contents counter - for figuring out where categories start
    $toc_counter = 2; // starts at 2 because vision page is page 1

    // loop through our categories
    while( $categories->fetch() ) :

      // get the category name for table of contents
      $category_name = $categories->display('name');
      $category_description = $categories->display('description');

      // get all the groups for this category based on campus_permalink
      // they need to be approved!
      $params = array(
        'orderby' => 'name ASC',
        'limit' => -1,
        'where' => 'campus.permalink = "' . $campus_permalink . '" AND approval_status = 1 AND privacy = 1 AND category.name = "' . $category_name . '"'
      );
      $groups = pods('life_groups')->find($params);

      // the total amount of groups for this category
      $total_groups = $groups->total();

      // as long as we have groups :)
      if ( $total_groups > 0 ) :

        // add to table of contents ::
        $cat_data = array(
          'page_num' => $toc_counter,
          'page_name' => $category_name
        );
        array_push($table_of_contents, $cat_data);

        // get our category info for the info page
        $category_info = array(
          'name' => $category_name,
          'description' => $category_description
        );

        // our needed total is the needed total of groups to make fully fill out pages (at 4 groups / page)
        $needed_total = ceil($total_groups / 4) * 4; // this finds the max value of slots needed to fill pages

        // the + 2 is b/c we use 2 groups on a category info page (at the bottom)
        $needed_blank_groups = $needed_total - $total_groups + 2;

        // since we're not worried about categories having to start on a "fresh" page (instead of the trying to fill both sides of the spread)
        if ($needed_blank_groups >= 4 ) :
          $needed_blank_groups = 0;
        endif;

        // first pages' max group count will be 2, since the category's info takes up 2 slots (4 per page)
        // ultimately this is for placing the first 2 groups with the category info on the first page
        $first_page_group_count = 0;

        // set our generic group counter too high so that it gets set on the first non-front-page loop
        $generic_page_group_count = 99;

        // setting the page num counter
        $page_count = 1;

        // array that has all our category's pages in it
        $pages = array(); // make sure to RESET our pages array

        // add the info page off the bat
        $pages['page_' . $page_count]['info'] = $category_info;

        // loop through our groups
        while ( $groups->fetch() ) :

          // get our group's info!
          // $name = $groups->display('name');
          $group_content = lg_get_group_book_data($groups);

          // if we're still on the first page -> put a group there
          if ($first_page_group_count < 2) :

            $pages['page_' . $page_count]['groups'][$first_page_group_count] = $group_content;

            $first_page_group_count++;

          // otherwise just start pushing groups at 4/page
          else :

            // we only want 4 groups per page
            if ($generic_page_group_count > 4) :

              // increase our page count
              $page_count++;
              // reset our generic_page_count
              $generic_page_group_count = 1;

            endif;

            $pages['page_' . $page_count]['groups'][$generic_page_group_count] = $group_content;

            $generic_page_group_count++;

          endif; // end check for first page count

        endwhile; // end groups loop

        // if we have needed blank groups
        if ( $needed_blank_groups > 0 ) :

          // loop through and add them to the portions of our last page
          for ($i = 1; $i <= $needed_blank_groups; $i++) :

            $pages['page_' . $page_count]['groups'][$generic_page_group_count] = "BLANK";
            $generic_page_group_count++;

          endfor; // end loop

        endif; // end check for needed blank groups

        // place our groups pages in an array with their page number
        foreach ($pages as $page) :

          if ($page['info']) :
            $type = 'info';
          else :
            $type = 'regular_page';
          endif;

          $page_data = array(
            'page_type' => $type,
            'page_content' => $page,
            'page_num' => $toc_counter
          );
          array_push($unsorted_pages, $page_data);

          // increase the counter so we know page numbers on the
          // table of contents page
          $toc_counter++;

        endforeach; // end loop through category group pages

      endif; // end check for groups > 0

    endwhile; // end loop through categories

  endif; // end check for categories > 0

  // put in the needed pages onto the unsorted pages array
  // these pages are Table of Contents, a blank page, and Vision Page

  $vision_permalink = 'vision';
  $vision = pods('church_settings', $vision_permalink);
  $vision_content = $vision->field('specific_text');

  $vision_page_content = array(
    'page_type' => 'vision',
    'page_content' => $vision_content,
    'page_num' => 1
  );

  $blank_page_content = array(
    'page_type' => 'blank'
  );

  $table_of_contents_content = array(
    'page_type' => 'table_of_contents',
    'page_content' => $table_of_contents,
  );

  array_unshift($unsorted_pages, $vision_page_content);
  array_unshift($unsorted_pages, $blank_page_content);
  array_unshift($unsorted_pages, $table_of_contents_content);
  array_unshift($unsorted_pages, $blank_page_content);

  // get the total number of currently unsorted pages
  $total_unsorted_book_pages = count($unsorted_pages);

  // find out how many full pages we need, needs to be divisible by 4...
  $total_needed_pages = ceil($total_unsorted_book_pages / 4) * 4; // this finds the max value of pages needed

  $needed_blank_pages = $total_needed_pages - $total_unsorted_book_pages;

  // if we DO need blank pages - put them at the end of the unsorted pages
  for ($i = 1; $i <= $needed_blank_pages; $i++) :

    $blank_page_content['page_num'] = $toc_counter;

    array_push($unsorted_pages, $blank_page_content);

    $toc_counter++;

  endfor;

  /// YAYAYAYAYAYA! We have our pages! (unsorted)

  /// now we just need to group them 2 per full page spread

  $final_book_pages = array_chunk($unsorted_pages, 2, true);

  /// now we just return them to be outputted by the template

  return $final_book_pages;

}

//
// Get our book pages, based on Book Sorting
//
function lg_get_book_pages_for_campus($campus_permalink) {

  // vision page details
  $vision_data = array(
    'page_num' => 1,
    'page_name' => 'NLC Life Group Vision'
  );

  // set our initial array for unsorted pages!
  $unsorted_pages = array();

  // get all of our categories
  $params = array(
    'orderby' => 'name ASC',
    'limit' => -1
  );

  $categories = pods('categories')->find($params);
  $total_categories = $categories->total();

  // if we have categories
  if ( $total_categories > 0 ) :

    // create our table of contents
    $table_of_contents = array();

    // and add the vision page
    array_push($table_of_contents, $vision_data);

    // table of contents counter - for figuring out where categories start
    $toc_counter = 2; // starts at 2 because vision page is page 1

    // loop through our categories
    while( $categories->fetch() ) :

      // get the category name for table of contents
      $category_name = $categories->display('name');
      $category_description = $categories->display('description');

      // get all the groups for this category based on campus_permalink
      // they need to be approved!
      $params = array(
        'orderby' => 'name ASC',
        'limit' => -1,
        'where' => 'campus.permalink = "' . $campus_permalink . '" AND approval_status = 1 AND privacy = 1 AND category.name = "' . $category_name . '"'
      );
      $groups = pods('life_groups')->find($params);

      // the total amount of groups for this category
      $total_groups = $groups->total();

      // as long as we have groups :)
      if ( $total_groups > 0 ) :

        // add to table of contents ::
        $cat_data = array(
          'page_num' => $toc_counter,
          'page_name' => $category_name
        );
        array_push($table_of_contents, $cat_data);

        // get our category info for the info page
        $category_info = array(
          'name' => $category_name,
          'description' => $category_description
        );

        // our needed total is the needed total of groups to make fully fill out pages (at 4 groups / page)
        $needed_total = ceil($total_groups / 4) * 4; // this finds the max value of slots needed to fill pages

        // the + 2 is b/c we use 2 groups on a category info page (at the bottom)
        $needed_blank_groups = $needed_total - $total_groups + 2;


        // since we're not worried about categories having to start on a "fresh" page (instead of the trying to fill both sides of the spread)
        if ($needed_blank_groups >= 4 ) :
          $needed_blank_groups = 0;
        endif;

        // first pages' max group count will be 2, since the category's info takes up 2 slots (4 per page)
        // ultimately this is for placing the first 2 groups with the category info on the first page
        $first_page_group_count = 0;

        // set our generic group counter too high so that it gets set on the first non-front-page loop
        $generic_page_group_count = 99;

        // setting the page num counter
        $page_count = 1;

        // array that has all our category's pages in it
        $pages = array(); // make sure to RESET our pages array

        // add the info page off the bat
        $pages['page_' . $page_count]['info'] = $category_info;

        // loop through our groups
        while ( $groups->fetch() ) :

          // get our group's info!
          // $name = $groups->display('name');
          $group_content = lg_get_group_book_data($groups);

          // if we're still on the first page -> put a group there
          if ($first_page_group_count < 2) :

            $pages['page_' . $page_count]['groups'][$first_page_group_count] = $group_content;

            $first_page_group_count++;

          // otherwise just start pushing groups at 4/page
          else :

            // we only want 4 groups per page
            if ($generic_page_group_count > 4) :

              // increase our page count
              $page_count++;
              // reset our generic_page_count
              $generic_page_group_count = 1;

            endif;

            $pages['page_' . $page_count]['groups'][$generic_page_group_count] = $group_content;

            $generic_page_group_count++;

          endif; // end check for first page count

        endwhile; // end groups loop

        // if we have needed blank groups
        if ( $needed_blank_groups > 0 ) :

          // loop through and add them to the portions of our last page
          for ($i = 1; $i <= $needed_blank_groups; $i++) :

            $pages['page_' . $page_count]['groups'][$generic_page_group_count] = "BLANK";
            $generic_page_group_count++;

          endfor; // end loop

        endif; // end check for needed blank groups

        // place our groups pages in an array with their page number
        foreach ($pages as $page) :

          if ($page['info']) :
            $type = 'info';
          else :
            $type = 'regular_page';
          endif;

          $page_data = array(
            'page_type' => $type,
            'page_content' => $page,
            'page_num' => $toc_counter
          );
          array_push($unsorted_pages, $page_data);

          // increase the counter so we know page numbers on the
          // table of contents page
          $toc_counter++;

        endforeach; // end loop through category group pages

      endif; // end check for groups > 0

    endwhile; // end loop through categories

  endif; // end check for categories > 0

  // put in the needed pages onto the unsorted pages array
  // these pages are Table of Contents, a blank page, and Vision Page

  $vision_permalink = 'vision';
  $vision = pods('church_settings', $vision_permalink);
  $vision_content = $vision->field('specific_text');

  $vision_page_content = array(
    'page_type' => 'vision',
    'page_content' => $vision_content,
    'page_num' => 1
  );

  $blank_page_content = array(
    'page_type' => 'blank'
  );

  $table_of_contents_content = array(
    'page_type' => 'table_of_contents',
    'page_content' => $table_of_contents,
  );

  array_unshift($unsorted_pages, $vision_page_content);
  array_unshift($unsorted_pages, $blank_page_content);
  array_unshift($unsorted_pages, $table_of_contents_content);

  // get the total number of currently unsorted pages
  $total_unsorted_book_pages = count($unsorted_pages);

  // find out how many full pages we need, needs to be divisible by 4...
  $total_needed_pages = ceil($total_unsorted_book_pages / 4) * 4; // this finds the max value of pages needed

  $needed_blank_pages = $total_needed_pages - $total_unsorted_book_pages;

  // if we DO need blank pages - put them at the end of the unsorted pages
  for ($i = 1; $i <= $needed_blank_pages; $i++) :

    $blank_page_content['page_num'] = $toc_counter;

    array_push($unsorted_pages, $blank_page_content);

    $toc_counter++;

  endfor;

  $min = 0; // starting count, for determining distribution
  $max = $total_needed_pages; // max count for determining distribution
  $mid = $max / 2;

  $sorting_counter = 0;
  $min = 0;
  $max = $max - 1; // since arrays start with 0

  // make our array for sorted pages
  $sorted_pages = array();

  while ($sorting_counter < $mid) :

    // sort those mothas!

    if ($sorting_counter&1) : // check to see if count is odd.

      array_push($sorted_pages, $unsorted_pages[$min]);
      array_push($sorted_pages, $unsorted_pages[$max]);

    else : // if even :

      array_push($sorted_pages, $unsorted_pages[$max]);
      array_push($sorted_pages, $unsorted_pages[$min]);

    endif;

    $min++;
    $max--;

    $sorting_counter++;

  endwhile;


  /// YAYAYAYAYAYA! We have our pages all sorted!

  /// now we just need to group them 2 per full page spread

  $final_book_pages = array_chunk($sorted_pages, 2, true);

  /// now we just return them to be outputted by the template

  return $final_book_pages;

}

//
// returns an array of data about or Group Leader
//
function lg_get_group_leader_book_data($leader) {
  $id     = $leader['ID'];
  $name   = get_user_meta($id, 'full_name');
  $name   = $name[0];
  $avatar = lg_get_leader_book_avatar($leader['user_login']);

  $leader_data = array(
    'name'   => $name,
    'avatar' => $avatar
  );

  return $leader_data;
}

//
// Displays individual groups for pages!!
//
function lg_get_group_book_display($group, $class) {
?>
  <div class="group <?php echo $class; ?>">

    <?php if ( ! empty($group['childcare']) ) : ?>
      <img class="group-childcare" src="http://nlc.s3.amazonaws.com/lg-assets/books/books-childcare-icon-lg.jpg">
    <?php endif; ?>

    <?php if ($group != 'BLANK') : ?>

      <h2 class="group-name">
        <?php echo $group['name']; ?>
      </h2>
      <ul class="cat-demo">
        <?php if ($group['demographic']) : ?>
          <li>
            <span><?php echo $group['demographic']; ?></span>
          </li>
        <?php endif; ?>
        <?php foreach($group['category'] as $category) : ?>
          <li>
            <span>
              <?php echo $category; ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="description">
        <span>
          <?php echo $group['description']; ?>
        </span>
      </div><!-- //description -->
      <ul class="leaders">
        <?php if ($group['primary_leadership']) : ?>
          <li class="leader">
            <span class="avatar">
              <img src="<?php echo $group['primary_leadership']['avatar']; ?>">
            </span>
            <span class="leader-name">
              <?php echo $group['primary_leadership']['name']; ?>
            </span>
          </li>
        <?php endif; ?>
        <?php if ( ! empty($group['co_leadership'] ) ) : ?>
          <?php foreach($group['co_leadership'] as $co_leader) : ?>
            <li class="leader">
              <span class="avatar">
                <img src="<?php echo $co_leader['avatar']; ?>">
              </span>
              <span class="leader-name">
                <?php echo $co_leader['name']; ?>
              </span>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul><!-- //leaders -->
        <span class="group-label location">
          Life Group Location
        </span>
        <span class="group-value location">
          <?php echo $group['location']; ?>
        </span>
        <span class="group-label time">
          Meeting Time
        </span>
        <span class="group-value time">
          <?php echo $group['meeting_time']; ?>
        </span>
        <span class="group-label signup">
          Signup Page
        </span>
        <span class="group-value signup">
          <?php echo $group['signup']; ?>
        </span>

    <?php endif; // end test for blank group ?>

  </div><!-- //group -->

<?php
}

//
// Displays a book page!
//
function lg_display_book_page($full_page) {
?>
  <?php if ($full_page['page_type'] == 'regular_page') : ?>

    <?php $pages = array_chunk($full_page['page_content']['groups'], 2, true); ?>

    <?php foreach($pages as $cells) : ?>

      <div class="cell">

        <?php $left_right_counter = 1; ?>
        <?php foreach ($cells as $group) : ?>

          <?php

            if ($left_right_counter&1) :

              $class = "left";

            else :

              $class = "right";

            endif;
          ?>

          <?php lg_get_group_book_display($group, $class); ?>

          <?php $left_right_counter++; ?>

        <?php endforeach; // end loop through groups ?>

      </div><!-- //cell -->

    <?php endforeach; // end loop through pages as cells ?>

  <?php elseif ($full_page['page_type'] == 'info') : ?>

    <div class="cell">

      <div class="start-category">
        <span class="label">Category</span>
        <h2><?php echo $full_page['page_content']['info']['name']; ?></h2>
        <span class="category-description">
          <?php echo $full_page['page_content']['info']['description']; ?>
        </span>
      </div><!-- //start-category -->
      <span class="childcare-key">
        <img src="http://nlc.s3.amazonaws.com/lg-assets/books/books-childcare-icon-lg.jpg">
        = childcare provided (age and availability vary)
      </span>

    </div><!-- //cell -->

    <div class="cell">

      <?php $left_right_counter = 1; ?>
      <?php foreach ($full_page['page_content']['groups'] as $group) : ?>
        <?php

          if ($left_right_counter&1) :
            $class="left";
          else :
            $class="right";
          endif;
          $left_right_counter++;
        ?>
        <?php lg_get_group_book_display($group, $class); ?>
      <?php endforeach; ?>
    </div><!-- //cell -->

  <?php elseif ($full_page['page_type'] == 'blank') : // blank page ?>

    <div class="cell">
    </div>
    <div class="cell">
    </div>

  <?php elseif ($full_page['page_type'] == 'vision') : // vision page ?>
    <div class="vision-page">
      <?php echo $full_page['page_content']; ?>
    </div>

  <?php elseif ($full_page['page_type'] == 'table_of_contents') : // table of contents ?>

  <div class="table-of-contents">

    <h2 class="label">Table of Contents</h2>

      <ul class="table">

        <?php foreach($full_page['page_content'] as $page) : ?>
          <li>
            <span class="page"><?php echo $page['page_name']; ?></span>
            <span class="page-number"><?php echo $page['page_num']; ?></span>
          </li>
        <?php endforeach; ?>

      </ul>

  </div>

  <?php endif; // end pagetype test ?>

<?php
}

?>