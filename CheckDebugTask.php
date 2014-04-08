<?php

require_once "phing/Task.php";

class CheckDebugTask extends Task {

  /**
   * The message passed in the buildfile.
   */
  private $inputFile = null;

  private $outputFile = null;

  private $pattern = null;

  // Supposed to do this according to phing docs...?
  // private $taskname = 'checkdebug';

  /**
   * The setter for the attribute "filepath"
   */
  public function setInputFile($path) {
    $this->inputFile = $path;
  }

  public function setOutputFile($path) {
    $this->outputFile = $path;
  }

  public function setPattern($pattern) {
    $this->pattern = (string) $pattern;
  }

  /**
   * The init method: Do init steps.
   */
  public function init() {
    // nothing to do here
  }

  /**
   * The main entry point method.
   */
  public function main() {

    // Prepare some variables
    $pattern = $this->pattern;
    $line_number = 1;
    $errors = array();

    // Open the input and output files
    $handle = fopen($this->inputFile, 'r');

    // If we have a pattern and file we can start reading
    if ($handle && $pattern) {
      while (($line = fgets($handle)) !== false) {
        // process the line read.
        $line = trim($line);
        if (preg_match("/$pattern/", $line)) {
          // Check if it's commented
          if (preg_match("/\/\/.*$pattern|\/\*.*$pattern.*\*\/|\*.*$pattern/", $line)) {
            $this->log("Commented debug code detected: $line [line:$line_number]");
            $errors[] = array(
              'line' => $line_number,
              'column' => 1,
              'severity' => 'warning',
              'message' => "Commented debug code detected: $line",
              'source' => 'CheckDebugTask',
            );
          }
          else {
            $this->log("Uncommented debug code detected: $line [line:$line_number]");
            $errors[] = array(
              'line' => $line_number,
              'column' => 1,
              'severity' => 'error',
              'message' => "Uncommented debug code detected: $line",
              'source' => 'CheckDebugTask',
            );
          }
        }
        $line_number++;
      }
    }
    else {
      throw new BuildException("Unable to read file: {$this->inputFile}");
    }

    // If there are errors we need to add them to our checkstyle output xml
    if ($errors) {
      // First get the SimpleXML Document
      $xml = $this->getSimpleXML();
      // We'll add a new <file> element
      $output = $xml->addChild('file');
      $output->addAttribute('name', $this->inputFile);
      // Loop through the errors and add the error element
      foreach ($errors as $error) {
        $item = $output->addChild('error');
        foreach ($error as $attribute => $value) {
          $item->addAttribute($attribute, $value);
        }
      }
      $this->outputXML($xml);
    }

    // Close the file
    fclose($handle);
  }

  /**
   * Prepares the XML output
   * @return [type] [description]
   */
  private function getSimpleXML() {
    // First check if the outputFile exists
    if (file_exists($this->outputFile)) {
      $xml = simplexml_load_file($this->outputFile);
    }
    else {
      $xml = simplexml_load_string("<?xml version=\"1.0\" encoding=\"UTF-8\"?><checkstyle></checkstyle>");
    }
    return $xml;
  }

  /**
   * Helper function to format the xml output and save the file
   */
  private function outputXML($simpleXML) {
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($simpleXML->asXML());
    $dom->saveXML();
    $dom->save($this->outputFile);
  }
}

?>
