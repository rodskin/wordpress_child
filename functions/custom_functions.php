<?php
// Here, you can add your own functions

// get encoded string, might be used if you want to zip something
function get_encoded_string ($str) {
    setlocale(LC_ALL, 'fr_FR.UTF-8');
    return iconv('UTF-8', 'ASCII//TRANSLIT', $str);
}


/**
 * $zip_name (string)
 * $array_to_zip (Array)
 *      $array['attachement'] = array(1,2,3,4...);
 *      $array['post'] = array(1,2,3,4...);
 */
function makeZip ($zip_name, $array_to_zip) {
    global $wpdb;
    $upload_dir = wp_upload_dir();
    $zip = new ZipArchive();
    // On crée l’archive.
    $archive_name = $zip_name . '.zip';
    
    $return = '';
    $error = '';
    foreach ($array_to_zip as $post_type => $post) {
        if ('attachement' == $post_type) {
            $sql = 'SELECT `ID`, `guid`
                FROM `' . $wpdb->prefix . 'posts`
                WHERE `ID` IN (' . implode(',', $array_to_zip[$post_type]) . ')
                AND `post_type` = "' . $post_type . '"';
            $results = $wpdb->get_results($sql);
            
            if (0 < $wpdb->num_rows) {
                if ($zip->open($upload_dir['basedir'] . '/' . $archive_name, ZipArchive::CREATE) == TRUE) {
                    // echo 'Zip.zip ouvert';
                    foreach ($results as $result) {
                        $file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $result->guid);
                        $file = str_replace('\\', '/', $file);
                        $file_title = basename($file);
                        setlocale(LC_ALL, 'fr_FR.UTF-8');
                        $file_title = iconv('UTF-8', 'ASCII//TRANSLIT', $file_title);
                        $zip->addFile($file, $file_title);
                    }
                    $zip->close();

                    $return = $upload_dir['baseurl'] . '/' . $archive_name;
                } else {
                    $error .= 'no-open-archive';
                    // Traitement des erreurs avec un switch(), par exemple.
                }
            } else {
                $error .= 'no-files-in-db';
            }
        } else {
            $sql = 'SELECT `ID`, `post_title`, `post_content`, `guid`
                    FROM `' . $wpdb->prefix . 'posts`
                    WHERE `ID` IN (' . implode(',', $array_to_zip[$post_type]) . ')
                    AND `post_type` = "' . $post_type . '"';
            $results = $wpdb->get_results($sql);
            
            if (0 < $wpdb->num_rows) {
                if (file_exists($upload_dir['basedir'] . '/' . $archive_name)) {
                    $open_zip = $zip->open($upload_dir['basedir'] . '/' . $archive_name);
                } else {
                    $open_zip = $zip->open($upload_dir['basedir'] . '/' . $archive_name, ZipArchive::CREATE);
                }
                if ($open_zip == TRUE) {
                    foreach ($results as $result) {
                        $url = ajouterParametreGET($result->guid, 'is_print', 'yes');
                        $html = file_get_contents(html_entity_decode($url));
                        setlocale(LC_ALL, 'fr_FR.UTF-8');
                        $title = iconv('UTF-8', 'ASCII//TRANSLIT', $result->post_title);
                        $title = preg_replace("#['\"\r\n]#", ' ', $title);
                        $title = preg_replace("#\s+#", ' ', $title);
                        $zip->addFromString($result->ID . ' - ' . $title . '.html', $html);
                    }
                    $zip->close();
                    $return = $upload_dir['baseurl'] . '/' . $archive_name;
                    // error_log($return);
                } else {
                    $error .= 'no-open-archive';
                    // Traitement des erreurs avec un switch(), par exemple.
                }
            }else {
                $error .= 'no-articles-in-db';
            }
        }
    }
    if ('' == $error) {
        echo $return;
    } else {
        echo $error;
    }
}

/**
 * truncate : cuts a string to the length of $length and replaces the last
 *            characters with the ending if the text is longer than length.
 *            credits goes to CakePHP for this wonder
 *
 * @param string $text : string to truncate
 * @param int $length : length of returned string, including ellipsis
 * @param array $options : an array of html attributes and options :
 *     'ending' will be used as Ending and appended to the trimmed string
 *     'exact' if false, $text will not be cut mid-word
 *     'html' if true, HTML tags would be handled correctly
 * @access public
 * @link http://book.cakephp.org/view/1469/Text#truncate-1625
 * @return string : trimmed string
 */
function truncate ($text, $length = 100, $options = array()) {
    $default = array(
        'ending' => '...', 'exact' => true, 'html' => false
    );
    $options = array_merge($default, $options);
    extract($options);
    if ($html) {
        if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        $totalLength = mb_strlen(strip_tags($ending));
        $openTags = array();
        $truncate = '';
        preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
        foreach ($tags as $tag) {
            if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                    array_unshift($openTags, $tag[2]);
                } else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                    $pos = array_search($closeTag[1], $openTags);
                    if ($pos !== false) {
                        array_splice($openTags, $pos, 1);
                    }
                }
            }
            $truncate .= $tag[1];
            $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
            if ($contentLength + $totalLength > $length) {
                $left = $length - $totalLength;
                $entitiesLength = 0;
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                    foreach ($entities[0] as $entity) {
                        if ($entity[1] + 1 - $entitiesLength <= $left) {
                            $left--;
                            $entitiesLength += mb_strlen($entity[0]);
                        } else {
                            break;
                        }
                    }
                }
                $truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
                break;
            } else {
                $truncate .= $tag[3];
                $totalLength += $contentLength;
            }
            if ($totalLength >= $length) {
                break;
            }
        }
    } else {
        if (mb_strlen($text) <= $length) {
            return $text;
        } else {
            $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
        }
    }
    if (!$exact) {
        $spacepos = mb_strrpos($truncate, ' ');
        if (isset($spacepos)) {
            if ($html) {
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags)) {
                    foreach ($droppedTags as $closingTag) {
                        if (!in_array($closingTag[1], $openTags)) {
                            array_unshift($openTags, $closingTag[1]);
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);
        }
    }
    $truncate .= $ending;
    if ($html) {
        foreach ($openTags as $tag) {
            $truncate .= '</'.$tag.'>';
        }
    }
    return $truncate;
}

// -----------------------------------------
// Ajoute/Modifie un parametre à un URL.
// -----------------------------------------
function ajouterParametreGET ($url, $paramNom, $paramValeur){
    $urlFinal = "";
    if ($paramNom == "") {
        $urlFinal = $url;
    } else {
        $t_url = explode("?", $url);
        if (count($t_url) == 1) {
            // pas de queryString
            $urlFinal .= $url;
            if(substr($url, strlen($url) - 1, strlen($url)) != "/"){
                $t_url2 = explode("/", $url);
                if(preg_match("/./", $t_url2[count($t_url2) - 1]) == false){
                    $urlFinal .= "/";
                }
            }
            $urlFinal .= "?".$paramNom."=".$paramValeur;
        } else if(count($t_url) == 2) {
            // il y a une queryString
            $paramAAjouterPresentDansQueryString = "non";
            $t_queryString = explode("&", $t_url[1]);
            foreach ($t_queryString as $cle => $coupleNomValeur) {
                $t_param = explode("=", $coupleNomValeur);
                if($t_param[0] == $paramNom){
                    $paramAAjouterPresentDansQueryString = "oui";
                }
            }
            if ($paramAAjouterPresentDansQueryString == "non") {
                // le parametre à ajouter n'existe pas encore dans la queryString
                $urlFinal = $url . "&" . $paramNom . "=" . $paramValeur;
            } else if($paramAAjouterPresentDansQueryString == "oui") {
                // le parametre à ajouter existe déjà dans la queryString
                // donc on va reconstruire l'URL
                $urlFinal = $t_url[0]."?";
                foreach ($t_queryString as $cle => $coupleNomValeur) {
                    if ($cle > 0) {
                        $urlFinal .= "&";
                    }
                    $t_coupleNomValeur = explode("=", $coupleNomValeur);
                    if ($t_coupleNomValeur[0] == $paramNom) {
                        $urlFinal .= $paramNom . "=" . $paramValeur;
                    } else {
                        $urlFinal .= $t_coupleNomValeur[0] . "=" . $t_coupleNomValeur[1];
                    }
                }
            }
        }
    }
    return $urlFinal;
}



/**
 * DEBUG FUNCTIONS
 */
function print_r_net($array)
{
    _xDumpVar($array);
}

/**
 * Dump a var
 *
 * @access private
 * @param mixed $data
 * @return string
 */
function _xDumpVar($data)
{
    $B_echo = true;
    ob_start();
    var_dump($data);
    $c = ob_get_contents();
    ob_end_clean();
    $c = preg_replace("/\r\n|\r/", "\n", $c);
    $c = str_replace("]=>\n", '] = ', $c);
    $c = preg_replace('/= {2,}/', '= ', $c);
    $c = preg_replace("/\[\"(.*?)\"\] = /i", "[$1] = ", $c);
    $c = preg_replace('/    /', "        ", $c);
    $c = preg_replace("/\"\"(.*?)\"/i", "\"$1\"", $c);
    $c = htmlspecialchars($c, ENT_NOQUOTES);
    // Expand numbers (ie. int(2) 10 => int(1) 2 10, float(6) 128.64 => float(1) 6 128.64 etc.)
    $c = preg_replace("/\(int|float\)\(([0-9\.]+)\)/ie", "'($1)('." . strlen('$2') . ".') <span class=\"number\">$2</span>'", $c);
    // Syntax Highlighting of Strings. This seems cryptic, but it will also allow non-terminated strings to get parsed.
    $c = preg_replace("/(\[[\w ]+\] = string\([0-9]+\) )\"(.*?)/sim", "$1<span class=\"string\">\"", $c);
    $c = preg_replace("/(\"\n{1,})( {0,}\})/sim", "$1</span>$2", $c);
    $c = preg_replace("/(\"\n{1,})( {0,}\[)/sim", "$1</span>$2", $c);
    $c = preg_replace("/(string\([0-9]+\) )\"(.*?)\"\n/sim", "$1<span class=\"string\">\"$2\"</span>\n", $c);
    $regex = array(// Numberrs
                   'numbers' => array('/(^|] = )(array|float|int|string|resource|object\(.*\)|\&amp;object\(.*\))\(([0-9\.]+)\)/i', '$1$2(<span class="number">$3</span>)'),
                   // Keywords
                   'null' => array('/(^|] = )(null)/i', '$1<span class="keyword">$2</span>'),
                   'bool' => array('/(bool)\((true|false)\)/i', '$1(<span class="keyword">$2</span>)'),
                   // Types
                   'types' => array('/(of type )\((.*)\)/i', '$1(<span class="type">$2</span>)'),
                   // Objects
                   'object' => array('/(object|\&amp;object)\(([\w]+)\)/i', '$1(<span class="object">$2</span>)'),
                   // Function
                   'function' => array('/(^|] = )(array|string|int|float|bool|resource|object|\&amp;object)\(/i', '$1<span class="function">$2</span>('));
    foreach ($regex as $x) {
            $c = preg_replace($x[0], $x[1], $c);
    }
    $style = '
    /* outside div - it will float and match the screen */
    .dumpr {
            margin: 2px;
            padding: 2px;
            background-color: #fbfbfb;
            float: left;
            clear: both;
    }
    /* font size and family */
    .dumpr pre {
            color: #000000;
            text-align:left;
            font-size: 9pt;
            font-family: "Courier New",Courier,Monaco,monospace;
            margin: 0px;
            padding-top: 5px;
            padding-bottom: 7px;
            padding-left: 9px;
            padding-right: 9px;
    }
    /* inside div */
    .dumpr div {
            background-color: #fcfcfc;
            border: 1px solid #d9d9d9;
            float: left;
            clear: both;
    }
    /* syntax highlighting */
    .dumpr span.string {color: #c40000;}
    .dumpr span.number {color: #ff0000;}
    .dumpr span.keyword {color: #007200;}
    .dumpr span.function {color: #0000c4;}
    .dumpr span.object {color: #ac00ac;}
    .dumpr span.type {color: #0072c4;}
    .legenddumpr {
        background-color: #fcfcfc;
        border: 1px solid #d9d9d9;
        padding: 2px;
    }
    ';
    $style = preg_replace("/ {2,}/", "", $style);
    $style = preg_replace("/\t|\r\n|\r|\n/", "", $style);
    $style = preg_replace("/\/\*.*?\*\//i", '', $style);
    $style = str_replace('}', '} ', $style);
    $style = str_replace(' {', '{', $style);
    $style = trim($style);
    $c = trim($c);
    $c = preg_replace("/\n<\/span>/", "</span>\n", $c);
    $S_from = '';
    // Nom du fichier appelant la fonction
    $A_backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    if (is_array($A_backTrace) && array_key_exists(0, $A_backTrace)) {
        $S_from = <<< BACKTRACE
            {$A_backTrace[1]{'file'}}, ligne {$A_backTrace[1]{'line'}}
BACKTRACE;
    } else {
        $S_from = basename($_SERVER['SCRIPT_FILENAME']);
    }
    $S_out  = '';
    $S_out .= "<style type=\"text/css\">" . $style . "</style>\n";
    $S_out .= '<fieldset class="dumpr">';
    $S_out .= '<legend class="legenddumpr">' . $S_from . '</legend>';
    $S_out .= '<pre>' . $c . '</pre>';
    $S_out .= '</fieldset>';
    $S_out .= "<div style=\"clear:both;\">&nbsp;</div>";
    if ($B_echo) {
        echo $S_out;
    } else {
        return $S_out;
    }
}