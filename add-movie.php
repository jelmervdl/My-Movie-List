<?php

require_once('_includes/main.php');
require_once('_includes/imdbphp/imdb.class.php');
require_once('_includes/imdbphp/imdbsearch.class.php');

require_once '_includes/imdb.php';



if (!LOGGEDIN) {
  header('Location: login.php?requestURI=' . urlencode($_SERVER["REQUEST_URI"]));
  exit();
}




switch (STEP) {
  case 1:
  default:
    AddMovie::Step1();
    break;
  case 2:
    AddMovie::Step2();
    break;
  case 3:
    AddMovie::Step3($modernLanguagesFlipped);
    break;
  case 4:
    AddMovie::Step4($modernLanguagesFlipped);
    break;
}



class AddMovie
{
  /*
    Display the searchbox and ID inputbox.
  */
  static public function Step1($message = false) {
    $tpl = new Template(DOCUMENT_ROOT . '_templates/add-movie.tpl');
    $content = new Template(DOCUMENT_ROOT . '_templates/step-1.tpl');
    
    if ($message)
      $content->set('message', $message);
    
    $tpl->set('content', $content);
    echo $tpl->fetch();
  }
  
  
  /*
    If a search is initiated: view the results.
  */
  static public function Step2() {
    if (!isset($_GET['q']))
      return AddMovie::Step1('invalid-searchterm');
    
    if (preg_match("/^\d{7}$/", $_GET['q'])) {
      $_GET['id'] = $_GET['q'];
      return AddMovie::Step3($GLOBALS['modernLanguagesFlipped']); // GLOBALS?!
    }

    $results = imdb\imdb()->find($_GET['q']);
    
    if (isset($_REQUEST['content-type'])
      && $_REQUEST['content-type'] == 'application/json') {
      header('Content-Type: application/json');
      echo json_encode(array_map(
        function($movie) {
          return array(
            'id' => $movie->id(),
            'title' => $movie->title(),
            'year' => $movie->year(),
            'image' => $movie->image() ? $movie->image()->url : null
          );
        },
        $results));
    }
    else {
      $tpl = new Template(DOCUMENT_ROOT . '_templates/add-movie.tpl');
      $content = new Template(DOCUMENT_ROOT . '_templates/step-2.tpl');
      $content->set('results', $results);
      
      $tpl->set('content', $content);
      echo $tpl->fetch();
    }
  }
  
  
  /*
   Display the movie, including the rating
  */
  static public function Step3($modernLanguagesFlipped) {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
      $movie = new imdb($_GET['id']);
      $movie->setid($_GET['id']);

      $tpl = new Template(DOCUMENT_ROOT . '_templates/add-movie.tpl');
      $content = new Template(DOCUMENT_ROOT . '_templates/step-3.tpl');
      $content->set('title', $movie->title());
      $content->set('imdbid', $_GET['id']);
      $content->set('languages', $movie->languages());
      $content->set('modernlanguages', $modernLanguagesFlipped);
      $content->set('aka', $movie->alsoknow());
      
      if ($movie->runtime() == null)
        $content->set('runtime', 1);
    
      if (isset($_GET['rating']) && is_numeric($_GET['rating']))
        $content->set('rating', $_GET['rating']);
    
      $tpl->set('content', $content);
      echo $tpl->fetch();
    }
    else
      AddMovie::Step1('invalid-id');
  }
  
  
  /*
    Insert the movie in the database
  */
  static public function Step4($modernLanguagesFlipped) {
    if (isset($_POST['imdbid']) && is_numeric($_POST['imdbid'])) {
      $movie = new imdb($_POST['imdbid']);
      $movie->setid($_POST['imdbid']);
      
      $db = new MDB();
      
      $cast = $movie->cast();
      $i = 0;
      
      // Insert all castmemebers
      while(list($key, $value) = each($cast)) {
        $db->insertCast($_POST['imdbid'], $value['imdb'], AddMovie::utf8ify($value['name']), ++$i);
        
        if ($i > MAXNAMES)
          break;
      }
      
      $directors = $movie->director();
      $writers = $movie->writing();
      $producers = $movie->producer();
      
      $crew = array_merge($directors, $writers, $producers);
      $i = 0;
      
      // Insert all crewmemebers
      while(list($key, $value) = each($crew)) {
        $db->insertCrew($_POST['imdbid'], $value['imdb'], AddMovie::utf8ify($value['name']), ++$i);
        
        if ($i > MAXNAMES)
          break;
      }
      
      $genres = implode(',', $movie->genres());
      $year = $movie->year();
      $runtime = $movie->runtime();
      
      if ($runtime == null) {
        $runtime = 1;
        if (isset($_POST['runtime']) && is_numeric($_POST['runtime']))
          $runtime = $_POST['runtime'];
      }
      
      $title = AddMovie::utf8ify($movie->title());
      $aka = $movie->alsoknow();
      $englishTitle = '';
      $rating = 1;
      
      if (is_numeric($_POST['rating']))
        $rating = $_POST['rating'];
      
      if ($_POST['english-title'] != 'none') {
        $englishTitle = AddMovie::utf8ify($aka[$_POST['english-title']]['title']);
        //$englishTitle = trim($englishTitle, 1, strrpos($englishTitle, '"') - 1);
      }
      
      $language = 'en';
      if (strlen($_POST['language']) == 2)
        $language = $_POST['language'];
      
      $success = $db->insertMovie($_POST['imdbid'], $title, $englishTitle, $language, $genres, $year, $runtime, $rating);

      if ($success)
        header('Location: ./?message=add-success');
      else
        header('Location: ./?message=add-error');
      exit();
    }
    else
      AddMovie::Step1('invalid-id');
  }
  
  
  
  static public function utf8ify($string) {
    return html_entity_decode(strip_tags($string), ENT_QUOTES, 'UTF-8');
  }
}

?>