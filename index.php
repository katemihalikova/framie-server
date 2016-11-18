<?php

require "config.php";
require BULLETPROOF;

$files = file("./files.txt", FILE_IGNORE_NEW_LINES) ?: [];

$image = new Bulletproof\Image($_FILES);
$image
  ->setLocation(".")
  ->setSize(1, 1e9)
  ->setDimension(1e4, 1e4);

if ($image["framie"] && $_POST["secret"] === SECRET) {
  $upload = $image->upload();

  if ($upload) {
    resize($image);

    if (count($files) >= NUMBER_OF_IMAGES) {
      $fileToBeUnlinked = array_shift($files);
    }
    $files[] = $image->getFullPath();

    $filesAsString = join("\n", $files);

    file_put_contents("./files.txt", $filesAsString);

    if (UNLINK_OLD_FILES && $fileToBeUnlinked) {
      unlink($fileToBeDeleted);
    }

    http_response_code(201);
    echo $filesAsString;
  } else {
    http_response_code(400);
    echo $image["error"];
  }
} else {
  http_response_code(404);
}

function resize($image) {

  $file = $image->getFullPath();
  $mime = $image->getMime();

  switch ($mime) {
    case "jpeg":
    case "jpg":
      $originalImage = imagecreatefromjpeg($file);
      break;
    case "png":
      $originalImage = imagecreatefrompng($file);
      break;
    case "gif":
      $originalImage = imagecreatefromgif($file);
      break;
    default:
      return;
  }

  $originalWidth = $image->getWidth();
  $originalHeight = $image->getHeight();

  if ($originalWidth <= IMAGE_WIDTH && $originalHeight <= IMAGE_HEIGHT) {
    return;
  }

  $newWidth = IMAGE_WIDTH;
  $newHeight = intval($newWidth / $originalWidth * $originalHeight);

  if ($newHeight > IMAGE_HEIGHT) {
    $newHeight = IMAGE_HEIGHT;
    $newWidth = intval($newHeight / $originalHeight * $originalWidth);
  }

  $newImage = imagecreatetruecolor($newWidth, $newHeight);

  imagecopyresampled($newImage, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

  switch ($mime) {
    case "jpeg":
    case "jpg":
      imagejpeg($newImage, $file, 85);
      break;
    case "png":
      imagepng($newImage, $file, 3);
      break;
    case "gif":
      imagegif($newImage, $file);
      break;
  }
}
