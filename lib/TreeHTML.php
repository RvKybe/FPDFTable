<?php

namespace FPDF\lib;

use FPDF\lib\parser\HTMLParser;

class TreeHTML{
    var $type = array();
    var $name = array();
    var $value = array();
    var $attribute = array();
    var $field = array();
    var $addText='';

    /**
     * @return array
     * @desc Tao mot tree node cac phan tu cua HTML
     */
    function TreeHTML($parser, $file=true){
        $i = 0;
        if ($file){
            while ($parser->parse())
                if (strtolower($parser->iNodeName)=='body') break;
        }
        while ($parser->parse()){
            if ($parser->iNodeType == HTMLParser::NODE_TYPE_ENDELEMENT && strtolower($parser->iNodeName)=='body' && $file) break;

            $this->type[$i] = $parser->iNodeType;
            $this->name[$i] = $parser->iNodeName;
            if ($parser->iNodeType == HTMLParser::NODE_TYPE_TEXT)
                $this->value[$i] = $parser->iNodeValue;
            if ($parser->iNodeType == HTMLParser::NODE_TYPE_ELEMENT){
                $this->attribute[$i] = $parser->iNodeAttributes;
                if (isset($parser->iNodeAttributes['name'])){
                    $this->field[$i] = trim($parser->iNodeAttributes['name'],"\"' ");
                }
                if (   ($file && $parser->iNodeName == 'input' && isset($this->attribute[$i]['type']) && $this->attribute[$i]['type']=='text' && !isset($this->attribute[$i]['onkeydown']))
                    || ($file && $parser->iNodeName == 'textarea'))
                    $this->attribute[$i]['onkeyup'] = 'initTyper(this)';
            }
            $i++;
        }
    }

    /**
     * @desc Them hoac sua field co ten $name mot thuoc tinh $attr
     */
    function set($name,$attr, $value){
        $index = array_search($name, $this->field);
        if (!$index) return;
        $this->attribute[$index][$attr] = $value;
    }

    /**
     * @desc Tra ve thuoc tinh $attr cua field $name
     */
    function get($name,$attr){
        $index = array_search($name, $this->field);
        if ($index && isset($this->attribute[$index][$attr]))
            return $this->attribute[$index][$attr];
        return '';
    }

    /**
     * @desc Tra ve ten cua tag HTML ung voi field $name
     */
    function getTag($name){
        $index = array_search($name, $this->field);
        //if (!isset($this->name[$index])) {debug($name);debug($index);}
        return $this->name[$index];
    }

    /**
     * @desc Tra ve cac thuoc tinh gom tabindex, size, maxlength
     */
    function getAll($name){
        $index = array_search($name, $this->field);
        if ($index){
            $t = '';
            if (isset($this->attribute[$index]['tabindex']))
                $t .= ' tabindex='.$this->attribute[$index]['tabindex'];
            if (isset($this->attribute[$index]['size']))
                $t .= ' size='.$this->attribute[$index]['size'];
            if (isset($this->attribute[$index]['maxlength']))
                $t .= ' maxlength='.$this->attribute[$index]['maxlength'];
            return $t;
        }
        return '';
    }

    /**
     * @desc Thay doi node ten $name thanh text voi noi dung $text
     */
    function replace($name,$text){
        $index = array_search($name, $this->field);
        if (!$index) return;
        $this->removeIndex($index);
        $this->type[$index] = HTMLParser::NODE_TYPE_TEXT;
        $this->value[$index] = $text;
    }

    /**
     * @desc Thay doi node ten $name thanh text voi noi dung $text
     */
    function remove($name){
        $index = array_search($name, $this->field);
        if (!$index) return;
        if (!isset($this->name[$index])) return;//echo "Remove: $name <br>";
        $rname = $this->name[$index];
        $len = count($this->name);
        for ($end=$index+1;$end<$len;$end++){
            if (isset($this->name[$end]) && $this->name[$end] == $rname) break;
        }
        if (isset($this->type[$end]) && $this->type[$end]==HTMLParser::NODE_TYPE_ENDELEMENT){
            for ($i=$index;$i<=$end;$i++) $this->removeIndex($i);
        }else
            $this->removeIndex($index);
    }

    /**
     * @desc Private: Xoa 1 object trong tree
     */
    function removeIndex($index){
        $this->type[$index]=-1;
        unset($this->field[$index]);
        unset($this->name[$index]);
        unset($this->value[$index]);
        unset($this->attribute[$index]);
    }

    /**
     * @return string
     * @desc Create a string HTML from a tree<br>
     * An Item have format ($iNodeType, $iNodeName, $iNodeValue, $iNodeAttributes)
     */
    function toHTML(){
        global $HTML_ATTRIBUTE_STAND_ALONE;
        $result = '';
        $type = &$this->type;
        $name = &$this->name;
        $valu = &$this->value;
        $attr = &$this->attribute;

        $len = count($type);
        for ($i=0; $i<$len;$i++){
            $str = '';
            switch($type[$i]){
                case HTMLParser::NODE_TYPE_ELEMENT:
                    if ($name[$i] != 'textarea'){
                        $str .= '<'.$name[$i];
                        if (isset($attr[$i])) foreach($attr[$i] as $key => $value){
                            if (array_search($value,$HTML_ATTRIBUTE_STAND_ALONE)!==false)
                                $str .= " $key";
                            else
                                $str .= " $key=\"$value\"";
                        }
                        $str .= '>';
                    }else{//is tag ATEXTAREA
                        $content = '';
                        $str .= '<'.$name[$i];
                        if (isset($attr[$i])) foreach($attr[$i] as $key => $value){
                            if ($key == 'value')
                                $content = $value;
                            elseif (array_search($value,$HTML_ATTRIBUTE_STAND_ALONE)!==false)
                                $str .= " $key";
                            else
                                $str .= " $key=\"$value\"";
                        }
                        $str .= '>'.$content;
                    }
                    break;
                case HTMLParser::NODE_TYPE_ENDELEMENT:
                    $str .= '</'.$name[$i].'>';
                    break;
                case HTMLParser::NODE_TYPE_TEXT:
                    $str = $valu[$i];
                    break;
            }
            $result .= $str;
            //if (isset($nobu[$i])) $result .= $nobu[$i];
        }
        return ($result.$this->addText);
    }

    /**
     * @desc Set all input text to readonly
     */
    function setReadonlyAll(){
        foreach ($this->name as $i => $name){
            if ($name == 'input' && isset($this->attribute[$i]['type'])){
                switch($this->attribute[$i]['type']){
                    case 'text':
                        $this->attribute[$i]['readonly'] = 'true';
                        $this->attribute[$i]['style'] = 'border: 1 dotted #999999';
                        break;
                    case 'select':
                    case 'checkbox':
                        $this->attribute[$i]['disabled'] = 1;
                        break;
                }
            }elseif ($name == 'textarea'){
                $this->attribute[$i]['readonly'] = 'true';
                $this->attribute[$i]['style'] = 'border: 1 dotted #999999';
            }
        }

    }
    /**
     * @desc Set an input text to readonly
     */
    function setReadonly($name){
        $index = array_search($name, $this->field);
        if (!$index) return;
        $this->attribute[$index]['readonly'] = true;
        $this->attribute[$index]['style'] = 'border: 1 dotted #999999';
    }


}
