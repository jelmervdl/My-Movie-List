<?php
if (count($results) > 0) :
?>

          <ul>
          <?php foreach ($results as $result): ?>
            <li>
              <a href="add-movie.php?step=3&amp;id=<?php echo substr($result->id(), 2); ?>">
                <?php if ($result->image()): ?>
                <img class="poster" width="100" src="imdb-proxy.php?url=<?php echo urlencode($result->image()->url) ?>">
                <?php endif ?>
                <?php echo $result->title(); ?>
                (<?php echo $result->year(); ?>)
              </a>
            </li>    
          <?php endforeach; ?>
          </ul>

<?php
else:
?>

          <p>No results found. <a href="./add-movie.php">Try again?</a></p>

<?php
endif;
?>