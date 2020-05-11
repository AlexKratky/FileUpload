# FileUpload

Class to process file upload

### Installation

`composer require alexkratky/fileupload`

### Usage

```php
require 'vendor/autoload.php';
use AlexKratky\FileUpload;
use AlexKratky\db;

db::connect('localhost', 'root', '', 'db');

FileUpload::_setUploadDirectory($_SERVER['DOCUMENT_ROOT'] . '/random/'); // You can set static upload directory, so every instance will have this directory
$fu = new FileUpload();
$fu->setUploadDirectory($_SERVER['DOCUMENT_ROOT'] . '/uploads/'); // But you can override every settings for each instance

var_dump($fu->process('files'));
```

```html
<form method="POST" enctype="multipart/form-data">
    <input name="files[]" type="file" multiple="multiple" /><br />
    <input type="submit" value="Send files" />
</form>
```