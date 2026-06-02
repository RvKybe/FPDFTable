<?php

namespace FPDF\lib\parser;

class HTMLFileParser extends HTMLParser {
    function __construct($fileName){
        $fp = fopen ($fileName, "r");
        $content = "";
        while (true) {
            $data = fread ($fp, 8192);
            if (strlen($data) == 0) {
                break;
            }
            $content .= $data;
        }
        fclose ($fp);
        parent::__construct($content);
    }
}
