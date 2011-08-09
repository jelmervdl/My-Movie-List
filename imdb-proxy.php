<?php

class Image
{
  public $width;
  
  public $height;
  
  public $resource;
  
  static public function fromJPEG($url)
  {
    return new Image(imagecreatefromjpeg($url));
  }
  
  static public function create($width, $height)
  {
    return new Image(imagecreatetruecolor($width, $height));
  }
  
  public function __construct($resource)
  {
    $this->resource = $resource;
    $this->width = imagesx($resource);
    $this->height = imagesy($resource);
  }
  
  public function __destruct()
  {
    imagedestroy($this->resource);
  }
  
  public function render()
  {
    imagejpeg($this->resource);
  }
}

if (!isset($_GET['url']))
  die('Missing url parameter');

$width = 100;

$source = Image::fromJPEG($_GET['url']);
$dest = Image::create($width, $width / $source->width * $source->height);

imagecopyresampled($dest->resource, $source->resource,
  0, 0,
  0, 0, 
  $dest->width, $dest->height,
  $source->width, $source->height);

header('Content-type: image/jpeg');
$dest->render();
