<?php

////////////////////////
// CONFIGURE

// the list of feeds to add to the page.
$feeds = array(
   'http://www.norka.fr/v4/rss.php5',
   'http://twitter.com/statuses/user_timeline/26208481.rss',
   'http://www.jblezoray.fr/blog/feed/',
   'https://github.com/jblezoray.private.actor.atom?token=c1faa006c9b038a39f4f9f5336f3f25d',
   'http://plusfeed.appspot.com/112674015772497514535' // google plus
);

// specific colors for feeds.  
$colors = array (
   'default_bg' => '#ffffff',
   'default_full' => '#cccccc',
   'norka.fr' => array(
      'title' => null, // do not replace default title.
      'color' => '#ffeded',
      'fullcolor' => '#ffb3b3'
   ),
   'twitter.com' => array(
      'title' => 'Tweet',
      'color' => '#eefeff',
      'fullcolor' => '#b3fbff'
   ),
   'github.com' => array(
      'title' => 'Github',
      'color' => '#fffeed',
      'fullcolor' => '#fffbb3'
   ),
   'plus.google.com/' => array(
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
$feed->set_cache_duration (600); // in seconds
$feed->handle_content_type(); // This makes sure that the content is sent to the browser as text/html and the UTF-8 character set (since we didn't change it).
$feed->strip_htmltags(array_merge($feed->strip_htmltags, array('div', 'blockquote', 'p', 'a', 'br', 'ul', 'li')));
$feed->set_feed_url($feeds); // Set which feed to process.
$success = $feed->init(); // Run SimplePie.

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

foreach ($feed->get_items() as $item):

   // time separator
   $post_timestamp = $item->get_date("U");
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

   $title = $item->get_title();
   $desc = $item->get_description();
   $title = substr($title, 0, 60) . ((strlen($title)>61)?" (...)":"");
   $desc = substr($desc, 0, 150) . ((strlen($desc)>151)?" (...)":"");
   $desc = nl2br($desc);
   
   // remove username from desc (such as what is done in twitter feeds.)
   if (strpos($desc, $twitterUserName . ": ") === 0) {
      $desc = str_replace($twitterUserName . ": ", "", $desc); 
   }
   
   // add colors.
   $bgcolor = $colors['default_bg'];
   $fullcolor = $colors['default_full'];
   foreach ($colors as $key => $val) {
      if (stristr($item->get_permalink(), $key) !== FALSE) {
         if ($val['title']!==null) {
            $title = $val['title'];
         }
         $bgcolor = $val['color'];
         $fullcolor = $val['fullcolor'];
         break;
      }
   }
   
   // image
   $locfeed = $item->get_feed(); 
   $url_img = $locfeed->get_favicon();
?>
   
   <a href="<?php echo $item->get_permalink(); ?>" style="background-color:<?php echo $bgcolor; ?>;"
      onmouseover="highlight_link(this, '<?php echo $bgcolor; ?>', '<?php echo $fullcolor; ?>'); return false;">
      <h2><img src="<?php echo $url_img; ?>" alt="img"/> <?php echo $title; ?></h2>
      <div class="date"><?php echo $item->get_date('j F Y @ G:i'); ?></div>
      <p><?php echo $desc; ?></p>
   </a>
   
<?php
endforeach; 
?>
   
   <div class="footer">Creation J.-B. Lézoray, 2011.<br/>Sources publically available on <a href="https://github.com/jblezoray/MultiSocial">github</a></div>

</body>
</html>