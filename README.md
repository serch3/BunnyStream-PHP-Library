# Bunny Stream PHP Library
A simple PHP library to interact with the Bunny Stream [API](https://docs.bunny.net/reference/api-overview).

### Requires
In order to interact with the API you need the API Access Information (Stream->API)

## Installation

```shell
composer require serch3/bunnystream-php-library
```

## How to use: 

### Quick start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Bunny\Stream\BunnyStream;


// Initiate the class:
$BunnyStream = new BunnyStream("{{Read/Write Key}}", "{{Video Library ID}}");
```
---
## Videos: 


Listing Videos:
```php
$BunnyStream->List();

$BunnyStream->List($Collection, $Search, $OrderBy, $Items, $Page); //filtered results
```
Optional:

`$Collection` If set, the response will only contain videos that belong to this collection ID  `string`

`$Search` if set, the response will be filtered to only contain videos that contain the search term `string`

`$OrderBy` 	Determines the ordering of the result within the response. 1(date) or 2(title) `int`

`$Items` 	Number of results per page. Default is 100 `int`

`$Page` 	Page number. Default is 1 `int`

returns `Json string`

---

Upload Video
```php
$BunnyStream->upload($filename, $title);'
```
`$filename` File to upload `string`

`$title` The title of the video `array`

Optional:

`$collectionId` The ID of the collection where the video will be put `string`

`$videoId` If set uploads the video for the given video ID instead of creating it `string`

returns `VideoId`

---

Delete Video
```php
$BunnyStream->delete($guid);
```

`$guid` ID of the video `string`

---

Update Video Info
```php
$BunnyStream->update($guid, $name);
```

`$guid` ID of the video `string`

`$title` Updated title of the video `string`

optional: 

`$collectionId` ID of the Collection `string`

---

Get Video Details
```php
$BunnyStream->get($guid);

$BunnyStream->get($guid)->framerate; //returns video framerate
```

`$guid` ID of the video `string`

returns `PHP Object`

---

Upload Thumbnail 
```php
$BunnyStream->uploadThumbnail($videoId, $url);
```

`$videoId` ID of the video `string`

`$url` path of the thumbnail `string`

---

Fetch Video
```php
$BunnyStream->fetch($url, $videoId);
```

`$url` External video URL `string`

`$videoId` ID of the video. If not set, it will be automatically generated `string`

optional: 

`$headers` The headers that will be sent together with the fetch request `array`

---

Add Caption
```php
$BunnyStream->addCaption($source, $videoId, $srclang, $label);
```

`$source` path of the captions file `string`

`$videoId` ID of the video `string`

`$srclang` The unique srclang shortcode for the caption (e.g. en) `string`

`$label` The text description label for the caption `string`

---
---

Delete Caption
```php
$BunnyStream->deleteCaption($videoId, $srclang);
```

`$videoId` ID of the video `string`

`$srclang` The unique srclang shortcode for the caption (e.g. en) `string`

---
## Collections: 


Listing Collections:
```php
$BunnyStream->ListCollections();

$BunnyStream->ListCollections($Search, $OrderBy, $Items, $Page); //filtered results
```
Optional:

`$Search` if set, the response will be filtered to only contain collections that contain the search term `string`

`$OrderBy`  Determines the ordering of the result within the response. 1(date) or 2(title) `int`

`$Items` 	Number of results per page. Default is 100 `int`

`$Page` 	Page number. Default is 1 `int`

returns `Json string`

---

Create Collection
```php
$BunnyStream->createCollection($name);
```
`$name` The name of the collection `string`

returns `collectionId`

---

Delete Collection
```php
$BunnyStream->deleteCollection($collectionId);
```

`$collectionId` ID of the Collection `string`

---

Update Collection
```php
$BunnyStream->updateCollection($collectionId, $name);
```

`$collectionId` ID of the Collection `string`

`$name` Updated name for the collection `string`

---

Get Collection Details
```php
$BunnyStream->getCollection($collectionId);

$BunnyStream->getCollection($collectionId)->name; //returns name
```

`$collectionId` ID of the Collection `string`

returns `PHP Object`

---
