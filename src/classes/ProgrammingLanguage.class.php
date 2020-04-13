<?php

/**
 * @author           Bob Schockweiler (https://webfashion.eu)
 * @copyright        2016 - 2020 Bob Schockweiler
 * @license          GNU AGPLv3
 */


require_once "Classify.class.php";

class ProgrammingLanguage extends Classify
{


    /**
     * Checks the code and returns the probability and the file extension
     * @param string $code Code
     * @param bool $removeComments Remove comments, may result in more inaccurate results (Default: true)
     * @param bool $absoluteValues Use absolute values for probability values. Note: If JS or PHP snippets are used, the values will always be relative. (Default: false)
     * @return object
     **/
    public function check(string $code, bool $removeComments = true, bool $absoluteValues = false)
    {
        // analyze code
        $probabilities = $this->language($code, $removeComments, $absoluteValues);
        return (object) [
            "probabilities" => (object) $probabilities,
            "extension" => $this->calcPriority($probabilities),
        ];
    }


    /**
     * Calculates the priority (extension) of the code
     * @param array $probabilities Probabilities returned from Classify->language()
     * @return string
     **/
    private function calcPriority(array $probabilities): string
    {
        if ($this->getForcedExtension() !== false) {
            // extension forced - use it
            return $this->getForcedExtension();
        }

        // no extension was forced, use the extension based on the probabilities and priorities (see $this->priority())
        $priorities = $this->priority();
        foreach ($priorities as $priority) {
            if (isset($probabilities[$priority]) && $probabilities[$priority]) {
                return $priority;
            }
        }

        // anything else might be plain text?
        return "txt";
    }


    /**
     * Returns the priority for each language (lower index = high priority)
     * (e.g. if HTML is used in PHP, the extension used for the file must be PHP)
     * @return array
     **/
    private function priority(): array
    {
        return [
            "php",
            "xml",
            "html",
            "js",
            "css",
            "json",
            "sql",
            "sh",
        ];
    }


}