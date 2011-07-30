<?php

////////////////////////
// CONFIGURE

// the list of feeds to add to the page.
$feeds = array(
   'http://www.norka.fr/v4/rss.php5',
   'http://www.twitter.com/statuses/user_timeline/26208481.rss',
   'http://www.jblezoray.fr/blog/feed/',
   'https://github.com/jblezoray.atom',
   'http://plusfeed.appspot.com/112674015772497514535' // google plus
);

// specific colors for feeds.  
$colors = array (
   // default behaviour
   0 => array(
      'must_match' => null,
      'title' => null, // do not replace default title.
      'color' => '#ffffff',
      'fullcolor' => '#cccccc'
   ),
   1 => array(
      'must_match' => 'norka.fr',
      'title' => null,
      'color' => '#ffeded',
      'fullcolor' => '#ffb3b3'
   ),
   2 => array(
      'must_match' => 'twitter.com',
      'title' => 'Tweet',
      'color' => '#eefeff',
      'fullcolor' => '#b3fbff'
   ),
   3 => array(
      'must_match' => 'github.com',
      'title' => 'Github',
      'color' => '#fffeed',
      'fullcolor' => '#fffbb3'
   ),
   4 => array(
      'must_match' => 'plus.google.com/',
      'title' => 'Google+',
      'color' => '#f6edff',
      'fullcolor' => '#d9b3ff'
   ),
);

$pageTitle="JB Lézoray";
$twitterUserName = "lezoray";

date_default_timezone_set('Europe/Paris');

////////////////////////
// SCRIPT

include_once "simplepie.inc.php5";

$feed = new SimplePie(); 
$feed->set_cache_duration (3600); // in seconds
$feed->handle_content_type(); // This makes sure that the content is sent to the browser as text/html and the UTF-8 character set (since we didn't change it).
$feed->strip_htmltags(array_merge($feed->strip_htmltags, array('div', 'blockquote', 'p', 'a', 'br', 'ul', 'li')));
$feed->set_feed_url($feeds); // Set which feed to process.
$success = $feed->init(); // Run SimplePie.

// a non deletable cache system : 
$savedItems = array();// db holder
$savedItemsFilename = 'saveditems.db';// db file name 

// load flat file db into array
if(@file_exists($savedItemsFilename) && $file = @file_get_contents($savedItemsFilename)) {
   $savedItems = unserialize($file);
   if(!$savedItems) {
      $savedItems = array();
   }
}

// Loop through items to find new ones and insert them into db
foreach($feed->get_items() as $item) {
   $id = md5($item->get_id());// make id

   // if item is already in db, skip it
   if(isset($savedItems[$id]))
      continue;
 
   // found new item, add it to db
   $i = array();
   
   // date
   $i[0] = $item->get_date('U'); 
   
   // formated_date
   $i[1] = $item->get_date('j F Y @ G:i'); 
   
   // short_title
   $i[2] = substr($item->get_title(), 0, 60) . ((strlen($item->get_title())>61)?" (...)":"");
   
   // description
   // remove username from desc (such as what is done in twitter feeds.)
   $desc = (strpos($item->get_description(), $twitterUserName . ": ") === 0) 
         ? str_replace($twitterUserName . ": ", "", $item->get_description()) 
         : $item->get_description();
   $i[3] = nl2br(substr($desc, 0, 150) . ((strlen($desc)>151)?" (...)":"")); 
   
   // link
   $i[4] = $item->get_permalink();
   
   // feed_favicon
   $i[5] = $item->get_feed()->get_favicon();
   
   // trying to gess the source of the element.
   $i[9] = 0; // 0 is the default value in tab $colors;
   foreach ($colors as $key => $val) {
   	  //echo $key . "?=" . $val ." + " . $i[4] . " --<br/> ";
      if (stristr($i[4], $colors[$key]['must_match']) !== FALSE){
      	
         // replace default title.
         if ($val['title']!==null) {
            $i[2] = $val['title'];
         }
         
         // set colors values
         $i[9] = $key;
         
         break;
      }
   }
   
   
   $savedItems[$id] = $i;
}

// sort items in reverse chronological order
function customSort($a,$b) {
   return $a[0] <= $b[0];
}
uasort($savedItems,'customSort');
 
// save db
if(!file_put_contents($savedItemsFilename,serialize($savedItems))) {
   echo ("<strong>Error: Can't save items.</strong><br>");
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
   <title><?php echo $pageTitle; ?></title>
   <link rel="stylesheet" type="text/css" media="screen" href="style.css" />
   <script type="text/javascript" src="js/prototype_1.7/prototype.js"></script>
   <script type="text/javascript" src="js/scriptaculous_1.9.0/scriptaculous.js" ></script>
   <script type="text/javascript">

   var cur_element = null;
   
   /*
    * highlights DOM element 'elt'. 
    * startColor : should be the default color of the element
    * endColor : the color of the highlight.
    */
   function highlight_link(elt, startColor, endColor) {
      // The effect is applied one node at the time.
      if (elt.isSameNode(cur_element) ) {
          return;
      }
      cur_element = elt;
      
      new Effect.Highlight(elt, { 
          duration: 0.2, 
          startcolor: startColor, 
          endcolor: endColor, 
          restorecolor: endColor,
          afterFinish: function() {
              new Effect.Highlight(elt, { 
                 duration: 0.4, 
                 startcolor: endColor, 
                 endcolor: startColor,
                 restorecolor: startColor
              });
          }
      });
   }

   </script>
   
</head>
<body>

   <h1>Activity on social networks</h1>
   
   <div class="time_separator">Recently</div>
   
<?php 

$current_timestamp = time();
$week_ts = false;
$month_ts = false;
$year_ts = false;

foreach($savedItems as $item):

   // time separator
   $post_timestamp = $item['d'];
   if (!$week_ts) {
      if ($current_timestamp - $post_timestamp > 3600 * 24) {
         echo "   <div class=\"time_separator\">This week</div>\n";
         $week_ts = true;
      }
   } else if (!$month_ts) {
      if ($current_timestamp - $post_timestamp > 3600 * 24 * 7) {
         echo "   <div class=\"time_separator\">This month</div>\n";
         $month_ts = true;
      }
   } else if (!$year_ts) {
      if ($current_timestamp - $post_timestamp > 3600 * 24 * 31) {
         echo "   <div class=\"time_separator\">This year</div>\n";
         $year_ts = true;
      }
   }
   
?>
   
   <a href="<?php echo $item[4]; ?>" style="background-color:<?php echo $colors[$item[9]]['color']; ?>;"
      onmouseover="highlight_link(this, '<?php echo $colors[$item[9]]['color']; ?>', '<?php echo $colors[$item[9]]['fullcolor']; ?>'); return false;">
      <h2><img src="<?php echo $item[5]; ?>" alt="img"/> <?php echo $item[2]; ?></h2>
      <div class="date"><?php echo $item[1]; ?></div>
      <p><?php echo $item[3]; ?></p>
   </a>
   
<?php
endforeach; 
?>
   
   <div class="footer">Creation J.-B. Lézoray, 2011.<br/>Sources publically available on <a href="https://github.com/jblezoray/MultiSocial">github</a></div>

</body>
</html>