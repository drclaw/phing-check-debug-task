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

    // Open the input and output files
    $handle = fopen($this->inputFile, 'r');
    $writer = new FileWriter($this->outputFile, $append = TRUE);

    // If we have a pattern and file we can start reading
    if ($handle && $pattern) {
      while (($line = fgets($handle)) !== false) {
        // process the line read.
        $line = trim($line);
        if (preg_match("/$pattern/", $line)) {
          // Check if it's commented
          if (preg_match("/\/\/.*$pattern|\/\*.*$pattern.*\*\/|\*.*$pattern/", $line)) {
            $this->log("Commented debug code detected: $line [line:$line_number]");
            $writer->write("<error line=\"$line_number\" column=\"1\" severity=\"warning\" message=\"Commented debug code detected: $line\" source=\"CheckDebugTask\"/>\n");
          }
          else {
            $this->log("Uncommented debug code detected: $line [line:$line_number]");
            $writer->write("<error line=\"$line_number\" column=\"1\" severity=\"error\" message=\"Uncommented debug code detected: $line\" source=\"CheckDebugTask\"/>\n");
          }
        }
        $line_number++;
      }
    }
    else {
      throw new BuildException("Unable to read file: {$this->filepath}");
    }

    // Close the files
    fclose($handle);
    $writer->close();
  }
}

?>
