<?php
class Speech{
    public  $codepage = 'UTF-8';
    private $resultFiles = array();
    private $apiUrl = 'http://apifree.forvo.com/key/[KEY]/format/xml/action/words-search/language/ru/search/[WORD]';
    private $text = '';
    private $outputFile = 'output.mp3';
    private $wordsDir;
    
    public function __construct($text = '', $outputFile = '') {
        $this->text = $text;
        $this->wordsDir = __DIR__.'/words/';
        if(strlen($outputFile)){
            $this->outputFile = $outputFile;
        }
    }
    
    /**
     * Sets a text to process
     * @param  string $text
     * @return Speech
     */
    public function setText($text){
        $this->text = $text;
        return $this;
    }

    /**
     * Sets API key for Forvo service
     * @param  string $key
     * @return Speech
     */
    public function setApiKey($key){
        $this->apiUrl = str_replace('[KEY]', $key, $this->apiUrl);
        return $this;
    }

    /**
     * Proceed generate a mp3 file
     * @return boolean
     */
    public function generate() {
        $words = $this->parseText();
        if($words){
            foreach ($words as $word){
                $wordFile = $this->wordsDir.$word.'.mp3';
                if(!is_file($wordFile)){
                    if(!$this->getWord($word, $wordFile)){
                        echo 'Error. Can\'t get file for word "'. $word .'"'. PHP_EOL;
                        continue;
                    }
                };
                $this->resultFiles[] = $wordFile;
            };
        };
        if($this->resultFiles && $this->composeFiles()){
            return true;
        }
        return false;
    }

    /**
     * Run ffmpeg tool
     * @return boolean
     */
    private function composeFiles(){
        if($this->resultFiles){
            $cmd = '`which` ffmpeg -y -i "concat:'. implode('|', $this->resultFiles) .'" -acodec copy output.mp3 > /dev/null 2>&1';
            $handler = proc_open($cmd, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes);
            $code = proc_close($handler);
            if($code === 0){
                return true;
            };
        }
        return false;
    }

    /**
     * Downloads and store word file
     * @param string $word word, which to download
     * @param string $path path to save a file
     * @return boolean
     */
    private function getWord($word, $path){
        $xml = $this->getWordXml($word);
        if($xml && ($xml->attributes()['total'] > 0)){
            $fileUrl = $xml->item[0]->standard_pronunciation->pathmp3;
            $file = file_get_contents($fileUrl);
            file_put_contents($path, $file);
            return true;
        }
        return false;
    }

    /**
     * Download file info
     * @param string $word
     * @return SimpleXMLElement
     */
    private function getWordXml($word){
        $url = str_replace('[WORD]', $word, $this->apiUrl);
        $xmlStr = file_get_contents($url);
        $xml = false;
        try{
            $xml = simplexml_load_string($xmlStr);
        } catch (Exception $e) {
            echo 'Error parsing XML for word "'. $word .'"'. $e->getMessage().PHP_EOL;
        }
        return $xml;
    }

    /**
     * Parse text to words only
     * @return array
     */
    private function parseText(){
        $words = explode(' ', mb_strtolower($this->text, $this->codepage));
        if($words){
            foreach($words as &$word){
                $word = trim($word,',.!?- \'"');
            }
        }
        return $words;
    }
}