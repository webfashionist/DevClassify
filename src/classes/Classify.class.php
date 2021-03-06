<?php

/**
 * @author           Bob Schockweiler (https://webfashion.eu)
 * @copyright        2016 - 2020 Bob Schockweiler
 * @license          GNU AGPLv3
 */


/*
Validates:
PHP, CSS, HTML, JavaScript
XML, jQuery, SQL, JSON
Bash, Python

Todo in the future maybe:
Swift, LESS/SASS/
*/

class Classify
{

    /**
     * @var string
     */
    private $code = '';

    /**
     * @var int
     */
    private $codeLength = 0;

    /**
     * @var array
     */
    private $probabilities = [];

    /**
     * @var bool
     */
    private $forceExtension = false;

    /**
     * Classify constructor.
     */
    function __construct()
    {
    }


    /**
     * Returns the probability of each included language
     * @param string $code Code
     * @param bool $removeComments Remove comments, may result in more inaccurate results (Default: true)
     * @param bool $absoluteValues Use absolute values for probability values. Note: If JS or PHP snippets are used, the values will always be relative. (Default: false)
     * @return array
     */
    public function language(string $code, bool $removeComments = true, bool $absoluteValues = false): array
    {
        // reset parameters for each code
        $this->resetParameters();

        $this->code = $code;
        if ($removeComments === true) {
            // remove comments from code
            $this->removeComments();
        }

        // analyze the programming languages
        $this->checkLanguages();
        if ($absoluteValues === false) {
            $this->probabilities = $this->relativeValues($this->probabilities);
        }
        return $this->probabilities;
    }


    /**
     * Returns forced extension (set in logic()) if any
     * @return bool|string
     **/
    public function getForcedExtension()
    {
        return $this->forceExtension;
    }


    /**
     * Returns relative values for otherwise absolute ones
     * @param array $array Array of values
     * @return array
     **/
    private function relativeValues(array $array): array
    {
        $total = array_sum($array);
        $divider = count($array);
        if ($divider > 0) {
            foreach ($array as $key => $value) {
                $array[$key] = ($value ? (($value * 100) / $total) : 0);
            }
            return $array;
        }
        return $array;
    }


    /**
     * Reset parameters (recommended before analyzing new code)
     **/
    private function resetParameters()
    {
        // reset probabilites
        $this->probabilities = ["css" => 0, "html" => 0, "php" => 0, "js" => 0, "python" => 0, "json" => 0, "sql" => 0, "xml" => 0, "sh" => 0];
        // reset forced extension
        $this->forceExtension = false;
    }


    /**
     * Remove comments from code
     * @return bool
     **/
    private function removeComments(): bool
    {
        $this->code = preg_replace("/([^:('\"]|^)(\s*\/\/.*)/", '${1}', $this->code);
        $this->code = preg_replace('/(.*?)(?=<!--)([\s\S]*?)-->/', '${1}', $this->code);
        $this->code = preg_replace('/(.*?)(?=\/\*)([\s\S]*?)\*\//', '${1}', $this->code);
        return true;
    }


    /**
     * Initializes all the languages
     **/
    private function checkLanguages()
    {
        $this->length();
        $this->php();
        $this->css();
        $this->inLineCSS();
        $this->javascript();
        $this->python();
        if ($this->probabilities["js"] === 0 && $this->probabilities["php"] <= 65) {
            // no development tags were used - check for any logical language
            $this->logic();
        }
        $this->html();
        $this->xml();
        $this->json();
        $this->bash();
    }


    /**
     * Returns the amount of characters in the code
     * @return int
     **/
    private function length(): int
    {
        $this->codeLength = strlen($this->code);
        return $this->codeLength;
    }


    /**
     * Checks for any logical language like JavaScript or PHP
     * @return object
     **/
    private function logic()
    {
        $result = ["php" => 0, "js" => 0, "jquery" => 0, "sql" => 0];

        // functions function name() {}
        preg_match_all("/([a-z]+ )?function (.*?).*?\((.*?)\).*?{(.*?)}/is", $this->code, $functions);
        // function definitions are available - check for variables
        if (isset($functions[3]) && is_array($functions[3]) && count($functions[3]) > 0) {
            $probablyJS = false;
            $probablyPHP = false;
            $result["php"] += 1;
            $result["js"] += 1;
            if ($functions[1] && count($functions[1]) > 0) {
                foreach ($functions[1] as $f) {
                    if (in_array(trim($f), ["private", "public", "static", "protected"])) {
                        $result["php"] += 2;
                        break;
                    }
                }
            }
            $phpCount = 0;
            foreach ($functions[3] as $parameters) {
                $allParameters = explode(",", $parameters);
                foreach ($allParameters as $p) {
                    $type = explode(" ", $p); // if any data type would be set
                    if ((!isset($type[1]) && substr(trim($type[0]), 0, 1) !== "$") || (isset($type[1]) && $type[1] && substr(trim($type[1]), 0, 1) !== "$")) {
                        $probablyJS = true;
                        $result["js"] += 10;
                        break;
                    } elseif ($probablyPHP === false) {
                        $phpCount = 1;
                        $probablyPHP = true;
                    }
                }
                if ($probablyJS === true) {
                    $phpCount = 0;
                    break;
                }
            }
            $result["php"] += $phpCount;
        }

        // if clause (JS and PHP)
        $probablyJS = false;
        preg_match_all("/if\s*\((.*?)\).*?{.*?}/is", $this->code, $if);
        if (isset($if[1]) && is_array($if[1]) && count($if[1]) > 0) {
            foreach ($if[1] as $i) {
                if (strpos($i, "$") === false) {
                    $probablyJS = true;
                }
            }
            if ($probablyJS === true) {
                $result["js"] += 2;
            } else {
                $result["js"] += 1;
                $result["php"] += 2;
            }
        }


        // class (PHP)
        preg_match_all("/class [a-z]+[a-z0-9]*.*?{.*?}/is", $this->code, $class);
        if (isset($class[0]) && is_array($class[0]) && count($class[0]) > 0) {
            $result["php"] += 2;
        }

        // variable definition (with var)
        preg_match_all("/var\s+[\$]?([a-z]+[.()a-z0-9]*)\s?=/is", $this->code, $variables);
        if (isset($variables[0]) && is_array($variables[0]) && count($variables[0]) > 0) {
            $result["js"] += 10;
        }

        // variable definition (with $ WITH ; at the end)
        preg_match_all("/([\$][a-z]+[_a-z0-9]*)\s?=[^;]+;/is", $this->code, $variables2);
        if (isset($variables2[0]) && is_array($variables2[0]) && count($variables2[0]) > 0) {
            $result["php"] += 2;
            $result["js"] += 1;
        }

        // variable definition (with $ WITHOUT ; at the end)
        preg_match_all("/([\$][a-z]+[_a-z0-9]*)\s?=[^;]+$/is", $this->code, $variables2);
        if (isset($variables2[0]) && is_array($variables2[0]) && count($variables2[0]) > 0) {
            $result["js"] += 2;
        }

        // variable definition (without $)
        preg_match_all("/^\s*([^\$][a-z]+[_a-z0-9]*)\s?=/is", $this->code, $variables2);
        if (isset($variables2[0]) && is_array($variables2[0]) && count($variables2[0]) > 0) {
            $result["js"] += 3;
        }

        // check for objects (e.g. document.getElementById() or object.function())
        preg_match_all("/([[a-z]+[_a-z0-9]*]\.[a-z]+[_a-z0-9]*)+(\(\))/is", $this->code, $objects);
        if (isset($objects[0]) && is_array($objects[0]) && count($objects[0]) > 0) {
            $result["js"] += 2;
        }

        // check for objects (e.g. $object->method() or $object->key)
        preg_match_all("/([\$][a-z]+[a-z0-9]*->[\$]?[a-z]+[a-z0-9]*)(\(\))?.*?;$/is", $this->code, $objects2);
        if (isset($objects2[0]) && is_array($objects2[0]) && count($objects2[0]) > 0) {
            $result["php"] += 2;
        }

        // check for try-catch elements (PHP)
        preg_match_all("/try\s?{(.*?)}\s?catch\s?\([a-z]*Exception\s+[\$][a-z]+[_a-z0-9]*\)\s?{.*?}/is", $this->code, $trycatch);
        if (isset($trycatch[0]) && is_array($trycatch[0]) && count($trycatch[0]) > 0) {
            $result["php"] += 2;
        }

        // check for try-catch elements (JavaScript)
        preg_match_all("/try\s?{(.*?)}\s?catch\s?\([\$]?[a-z]+[_a-z0-9]*\)\s?{(.*?)}/is", $this->code, $trycatch2);
        if (isset($trycatch2[0]) && is_array($trycatch2[0]) && count($trycatch2[0]) > 0) {
            $result["js"] += 2;
        }

        // includes/echo/return/... (PHP)
        preg_match_all("/((include|require)(_once)?|echo|return|print|exit|break)[^;]*;\s*$/is", $this->code, $includes);
        if (isset($includes[0]) && is_array($includes[0]) && count($includes[0]) > 0) {
            $result["php"] += 2;
        }

        // loops (PHP)
        preg_match_all("/(while|for|foreach)\s?\(.*?[\$].*?\).*?{.*?}/is", $this->code, $loops1);
        if (isset($loops1[0]) && is_array($loops1[0]) && count($loops1[0]) > 0) {
            $result["php"] += 2;
            if (!in_array("foreach", $loops1[1])) {
                $result["js"] += 1;
            }
        }

        // loops (JavaScript)
        preg_match_all("/(while|for)\s?\([^\$]+\).*?{.*?}/is", $this->code, $loops1);
        if (isset($loops1[0]) && is_array($loops1[0]) && count($loops1[0]) > 0) {
            $result["js"] += 2;
        }

        // check for predefined JavaScript functions
        $jsCheck = [
            "/alert\(.*?\)/is",
            "/console\.log\(.*?\)/is",
            "/console\.error\(.*?\)/is",
            "/\.addEventListener\(.*?\)/is",
            "/\.getAttribute\(.*?\)/is",
            "/\.setAttribute\(.*?\)/is",
            "/\.[a-z]+[_a-z0-9]*\(.*?\)/is",
        ];
        foreach ($jsCheck as $js) {
            preg_match_all($js, $this->code, $JSfunctions);
            if (isset($JSfunctions[0]) && is_array($JSfunctions[0]) && count($JSfunctions[0]) > 0) {
                $result["js"] += 2;
            }
        }

        // check for predefined jQuery functions
        $jQueryCheck = [
            "/\.animate\(.*?\)/is",
            "/\.css\(.*?\)/is",
            "/\.on\(.*?\)/is",
            "/\.click\(.*?\)/is",
            "/\.ready\(.*?\)/is",
            "/\.load\(.*?\)/is",
            "/\.ajax\(.*?\)/is",
            "/\.post\(.*?\)/is",
            "/\.get\(.*?\)/is",
            "/\.attr\(.*?\)/is",
            "/\.submit\(.*?\)/is",
            "/\.after\(.*?\)/is",
            "/\.before\(.*?\)/is",
            "/\.next\(.*?\)/is",
            "/\.prev\(.*?\)/is",
            "/\.append\(.*?\)/is",
            "/\.prepend\(.*?\)/is",
            "/\.bind\(.*?\)/is",
        ];
        foreach ($jQueryCheck as $jQuery) {
            preg_match_all($jQuery, $this->code, $JSfunctions);
            if (isset($JSfunctions[0]) && is_array($JSfunctions[0]) && count($JSfunctions[0]) > 0) {
                $result["js"] += 3;
                $result["jquery"] += 3;
            }
        }

        // check for predefined PHP functions
        $PHPCheck = [
            "/strlen\(.*?\)/is",
            "/var_dump\(.*?\)/is",
            "/isset\(.*?\)/is",
            "/count\(.*?\)/is",
            "/preg_match\(.*?\)/is",
            "/preg_match_all\(.*?\)/is",
            "/preg_replace\(.*?\)/is",
            "/file_get_contents\(.*?\)/is",
            "/file_put_contents\(.*?\)/is",
            "/is_dir\(.*?\)/is",
            "/is_file\(.*?\)/is",
            "/mkdir\(.*?\)/is",
            "/unlink\(.*?\)/is",
            "/implode\(.*?\)/is",
            "/explode\(.*?\)/is",
        ];

        foreach ($PHPCheck as $PHP) {
            preg_match_all($PHP, $this->code, $PHPfunctions);
            if (isset($PHPfunctions[0]) && is_array($PHPfunctions[0]) && count($PHPfunctions[0]) > 0) {
                $result["php"] += 2;
            }
        }

        // check for SQL code
        $sqlCheck = [
            "/CREATE\s+(TABLE|VIEW|INDEX)\s+`?[_a-z0-9]+`?/is",
            "/SELECT\s+(DISTINCT\s+)?[ \*\.`_,a-z0-9]+\s+FROM\s+`?[_a-z0-9]+`?/is",
            "/TRUNCATE\s+`?[_a-z0-9]+`?/is",
            "/DELETE\s+FROM\s+`?[_a-z0-9]+`?/is",
            "/UPDATE\s+`?[_a-z0-9]+`?\s+SET\s+/is",
            "/DROP\s+TABLE\s+`?[_a-z0-9]+`?/is",
            "/ALTER\s+TABLE\s+`?[_a-z0-9]+`?/is",
        ];
        foreach ($sqlCheck as $sqlQuery) {
            preg_match_all($sqlQuery, $this->code, $SQL);
            if (isset($SQL[0]) && is_array($SQL[0]) && count($SQL[0]) > 0) {
                $result["sql"] += 1;
            }
        }


        $totalPoints = array_sum($result);
        if ($totalPoints > 0) {
            // reset CSS probability as it may collide with functions
            $this->probabilities["css"] = 0;

            // calculate probabilities
            foreach ($result as $language => $points) {
                $this->probabilities[$language] = ($points * 100) / $totalPoints;
            }

            if($totalPoints > $this->probabilities["py"]) {
                // force extension and ignore extension priorities
                $this->forceExtension = array_search(max($result), $result);
            }
        }

        return (object) $result;
    }


    /**
     * Checks if Bash is used
     * @return bool
     **/
    private function bash(): bool
    {
        // check if Bash code is used

        // if else clause
        preg_match_all("/if\s+\[\s+.*?\s+].*?then.*?(elif\s+\[\s+.*?\s+].*?then.*?)*fi/is", $this->code, $if);
        $length = $this->calculateLength($if[0] ?? []);

        // loops
        preg_match_all("/(until|while|for\s+.*?\s+in\s+.*?)\s+\[\s+.*?\s+].*?do.*?done/is", $this->code, $loop);
        $length += $this->calculateLength($loop[0] ?? []);
        preg_match_all("/for\s+.*?\s+in\s+.*?do.*?done/is", $this->code, $loop1);
        $length += $this->calculateLength($loop1[0] ?? []);

        // commands
        $commands = [
            "/echo\s+[\$][_a-z0-9]+[^;]$/is", // echo variable without ; at the end
            "/^[_a-z0-9]+=[^;]+$/is", // set variable (e.g. variable="test" without ; at the end)
            "/let\s+[_a-z0-9]+=/is", // define variables with let
            "/(read|touch|ls|cat|mkdir|cd|grep|rm|tar|cp|mv)\s+/is", // read input,
            "/\A#!(\/usr)?(\/local)?\/bin(\/env)?\s?(\/)?(bash|sh).*?\z/is", // Shebang (e.g.  #!/bin/bash)
        ];
        foreach ($commands as $command) {
            preg_match_all($command, $this->code, $bashCommand);
            $length += $this->calculateLength($bashCommand[0] ?? []);
        }

        // calculate probability for this code
        $this->probability($length, "sh");

        return $length > 0;
    }


    /**
     * Checks if valid JSON is used
     * @return bool
     **/
    private function json(): bool
    {
        // check if JSON code is used
        $json = json_decode($this->code);
        if ($json && (is_array($json) || is_object($json))) {
            $this->forceExtension = "json";
            $this->probability($this->codeLength, "json");
            // reset CSS probability as it may collide with JSON
            $this->probabilities["css"] = 0;
            return true;
        }
        $this->probability(0, "json");
        return false;
    }


    /**
     * Checks if PHP is used
     * @return bool
     **/
    private function php(): bool
    {
        // check if PHP code is used
        preg_match_all('/<\?([php|$| ])(.*?)(\Z|\?>)/is', $this->code, $output);
        $length = $this->calculateLength($output[2]);
        // calculate probability for this code
        $this->probability($length, "php");
        return $length > 0;
    }


    /**
     * Checks if XML is used
     * @return bool
     **/
    private function xml(): bool
    {
        // check if XML code is used
        preg_match_all('/<\?xml.*?>(.*?)\Z/is', $this->code, $output);
        $length = $this->calculateLength($output[1]);

        // as XML structure is very similar to HTML, check if the probability of HTML is high enough
        if ($this->probabilities["html"] < 80) {
            $length /= 2;
        }

        // calculate probability for this code
        $this->probability($length, "xml");
        return $length > 0;
    }

    private function python(): bool
    {
        $length = 0;

        // imports
        preg_match_all("/from [a-zA-Z.]+ import [a-zA-Z.]+\s*\r?\n/Uis", $this->code, $output);
        $length += $this->calculateLength($output[0] ?? []);
        preg_match_all("/\nimport [a-zA-Z.]+\s*\r?\n/Uis", $this->code, $output);
        $length += $this->calculateLength($output[0] ?? []);

        // if clauses
        preg_match_all("/if (not)? [^:]+:\s*\r?\n(.*?else:)/is", $this->code, $output);
        $length += $this->calculateLength($output[0] ?? []);

        // variables
        preg_match_all("/[_a-zA-Z]+[_a-zA-Z0-9]*\s+=\s+.*?\s*\r?\n/is", $this->code, $output);
        $length += $this->calculateLength($output[0] ?? []);

        // check predefined function calls
        preg_match_all("/(print|str)\([^)]*\)/is", $this->code, $output);
        $length += $this->calculateLength($output[0] ?? []);


        $non_used_characters = [
            "{", "}", ";", "->",
        ];

        $non_used_characters_used = false;
        foreach($non_used_characters as $character) {
            if(strpos($this->code, $character) !== false) {
                $non_used_characters_used = true;
                break;
            }
        }
        if($non_used_characters_used) {
            // using these parentheses and semicolons reduces the possibility like - a lot!
            $length /= 2;
        } else {
            // if all of the above characters are not used at all, it increases the possibility a lot!
            $length *= 2;
        }

        // calculate probability for this code
        $this->probability($length, "py");
        return $length > 0;
    }


    /**
     * Checks if JavaScript is used
     * @return bool
     **/
    private function javaScript(): bool
    {
        // check for JavaScript implemented through <script> tags
        preg_match_all("/<script.*?>(.*?)<\/script>/is", $this->code, $output);
        $length = $this->calculateLength($output[1] ?? []);

        // check for script src as well which obviously should include JavaScript
        preg_match_all("/<script.*?src=[\"|'](.*?)[\"|']>(.*?)<\/script>/is", $this->code, $output2);
        $length += $this->calculateLength($output2[1] ?? []);

        // check for predefined jQuery functions
        $length2 = 0;
        $jQueryCheck = [
            "/\.animate\(.*?\)/is",
            "/\.css\(.*?\)/is",
            "/\.on\(.*?\)/is",
            "/\.click\(.*?\)/is",
            "/\.ready\(.*?\)/is",
            "/\.load\(.*?\)/is",
            "/\.ajax\(.*?\)/is",
            "/\.post\(.*?\)/is",
            "/\.get\(.*?\)/is",
            "/\.attr\(.*?\)/is",
            "/\.submit\(.*?\)/is",
            "/\.after\(.*?\)/is",
            "/\.before\(.*?\)/is",
            "/\.next\(.*?\)/is",
            "/\.prev\(.*?\)/is",
            "/\.append\(.*?\)/is",
            "/\.prepend\(.*?\)/is",
            "/\.bind\(.*?\)/is",
        ];
        foreach ($jQueryCheck as $jQuery) {
            preg_match_all($jQuery, $this->code, $JSfunctions);
            if (isset($JSfunctions[0]) && is_array($JSfunctions[0]) && count($JSfunctions[0]) > 0) {
                $length2 += $this->calculateLength($JSfunctions[0] ?? []);
            }
        }


        // calculate the probability for this code
        $this->probability($length2, "jquery"); // set jQuery to 0
        $this->probability($length, "js");
        return $length > 0;
    }


    /**
     * Checks if HTML syntax has been used
     * @return bool
     **/
    private function html(): bool
    {
        // default tags
        preg_match_all("/<[^<]+>(.*)<\/[^<]+>/is", $this->code, $output);
        $length = $this->calculateLength($output[0] ?? []);

        // single tags (e.g. DOCTYPE, <link>, <img>, <br>, <hr>)
        preg_match_all("/<(!DOCTYPE|input|link|img|br|hr|meta)[^>]*>/is", $this->code, $output2);
        $length += $this->calculateLength($output2[0] ?? []);

        if (isset($output[0]) && $output[0]) {
            $code = implode("", $output[0]);
            // remove JavaScript from HTML
            preg_match_all("/<script.*?>(.*?)<\/script>/is", $code, $o);
            $length -= $this->calculateLength($o[1] ?? []);
            // remove CSS from HTML
            preg_match_all("/<style.*?>(.*?)<\/style>/is", $code, $o);
            $length -= $this->calculateLength($o[1] ?? []);
            // remove inline CSS from HTML
            preg_match_all("/style=[\"|'](.*?)[\"|']/is", $code, $o);
            $length -= $this->calculateLength($o[1] ?? []);
        }

        // calculate the probability for this code
        $this->probability($length, "html");
        return $length > 0;
    }


    /**
     * Checks if CSS syntax has been used (does not include inlineCSS())
     * @return bool
     **/
    private function css(): bool
    {
        preg_match_all("/(\w+)?(\s*>\s*)?(#[a-z0-9]+)?\s*(\.[a-z0-9]+)?\s*{(.*?)}/is", $this->code, $output);
        $length = $this->calculateLength(isset($output[0]) ? $output[0] : array());
        // calculate the probability for this code
        $this->probability($length, "css");
        return $length > 0;
    }


    /**
     * Checks if inline CSS is used
     * @return bool
     **/
    private function inLineCSS(): bool
    {
        preg_match_all("/style=[\"|'](.*?)[\"|']/is", $this->code, $output);
        $length = $this->calculateLength(isset($output[1]) ? $output[1] : array());
        // calculate the probability for this code
        $this->probability($length, "inlineCSS");
        return $length > 0;
    }


    /**
     * Calculates the length of each element of an array
     * @param string[] $array
     * @return int
     **/
    private function calculateLength($array): int
    {
        $length = 0;
        if ($array && isset($array) && is_array($array) && count($array) > 0) {
            foreach ($array as $string) {
                $length += strlen($string);
            }
        }
        return $length;
    }


    /**
     * Calculates the probability of the language only based on the amount of characters used
     * @param int $length Length of the code
     * @param string $language Language (e.g. PHP, CSS, ...)
     * @return int
     */
    private function probability($length, $language): int
    {
        $probability = (($length * 100) / $this->codeLength);
        $this->probabilities[$language] = (isset($this->probabilities[$language]) ? $probability + $this->probabilities[$language] : $probability);
        return $probability;
    }


}