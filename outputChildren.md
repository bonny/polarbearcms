
```
/**
 * Loops through all, or some children (according to $options) and outputs template
 * Great for outputing lists with news or stuff like that 
 * Has two special formatting keywords that output() does not have: 
 * {$cssFirst} and {$cssLast}. Good for styling
 * Also {$editAdd} will only be added for the first item
 *
 * @param string $format
 * @param array $options Like polarbear_article->children(). Default = null.
 */
function outputChildren($format, $options = null) {
```