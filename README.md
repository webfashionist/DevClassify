# DevClassify
DevClassify analyzes the source code of several programming languages and returns the probabilities for each language with the recommended file extension.


## Usage
To implement DevClassify in your project, you only need to `include` the `src/classes/ProgrammingLanguage.class.php` class and call the `check()` method. You can find an example in `tests/checkLanguage.php`.

```php
<?php
include "src/classes/ProgrammingLanguage.class.php";

$ProgrammingLanguage = new ProgrammingLanguage();
$result = $ProgrammingLanguage->check($yourCode); // set your code as parameter of this method

// probabilities:
var_dump($result->probabilities);

echo '<br>';
// recommended file extension:
echo $result->extension;
?>
```


## Languages

- [x] HTML
- [X] XML (XML snippets will be identified as HTML - the `<?xml >` tag must be set)
- [x] CSS
- [X] JavaScript
- [X] PHP
- [X] SQL
- [X] JSON
- [X] Bash
- [X] Python
- [ ] ...

