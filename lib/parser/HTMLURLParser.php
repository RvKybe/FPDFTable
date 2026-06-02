<?php

namespace FPDF\lib\parser;

class HTMLURLParser extends HTMLParser {
    function __construct($url){
        $fp = fopen ($url, "r");
        $content = "";
        while (true) {
            $data = fread ($fp, 8192);
            if (strlen($data) == 0) {
                break;
            }
            $content .= $data;
        }
        fclose ($fp);
        parent::__construct(file_get_contents($content));
    }
}
