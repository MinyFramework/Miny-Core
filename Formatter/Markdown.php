<?php

/*
 * This file is the PHP implementation of the Markdown text-to-HTML converter.
 *
 * Copyright © 2004, John Gruber
 * http://daringfireball.net/
 * All rights reserved.
 *
 * This software is provided by the copyright holders and contributors “as is”
 * and any express or implied warranties, including, but not limited to, the
 * implied warranties of merchantability and fitness for a particular purpose
 * are disclaimed. In no event shall the copyright owner or contributors be
 * liable for any direct, indirect, incidental, special, exemplary, or
 * consequential damages (including, but not limited to, procurement of
 * substitute goods or services; loss of use, data, or profits; or business
 * interruption) however caused and on any theory of liability, whether in
 * contract, strict liability, or tort (including negligence or otherwise)
 * arising in any way out of the use of this software, even if advised of the
 * possibility of such damage.
 *
 */

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Formatter
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\Formatter;

class Markdown implements iFormatter {

    private $links;
    private $html_blocks;
    private static $char_map = array(
        '\\\\' => '\\',
        '\`'   => '`',
        '\*'   => '*',
        '\_'   => '_',
        '\{'   => '{',
        '\}'   => '}',
        '\['   => '[',
        '\]'   => ']',
        '\('   => '(',
        '\)'   => ')',
        '\#'   => '#',
        '\+'   => '+',
        '\-'   => '-',
        '\.'   => '.',
        '\!'   => '!'
    );

    public static function escape($str) {
        return strtr($str, array_flip(self::$char_map));
    }

    public static function unescape($str) {
        return strtr($str, self::$char_map);
    }

    private function escapeSpan($matches) {
        return self::escape($matches[1]);
    }

    public function formatLine($line) {
        //code
        $line = preg_replace_callback('/(?<!\\\)(`+)(.*?)(?<!\\\)\1/u', array($this, 'insertCode'), $line);
        //image, link
        $line = preg_replace_callback('/(?<!\\\)!\[(.+?)(?<!\\\)\]\((.+?)(?:\s+"(.*?)")?(?<!\\\)\)/u', array($this, 'insertImage'), $line);
        $line = preg_replace_callback('/(?<!\\\)!\[(.*?)(?<!\\\)\]\s{0,1}(?<!\\\)\[(.*?)(?<!\\\)\]/u', array($this, 'insertImageDefinition'), $line);
        $line = preg_replace_callback('/(?<!\\\)\[(.+?)(?<!\\\)\]\((.+?)(?:\s+"(.*?)")?(?<!\\\)\)/u', array($this, 'insertLink'), $line);
        $line = preg_replace_callback('/(?<!\\\)\[(.*?)(?<!\\\)\]\s{0,1}(?<!\\\)\[(.*?)(?<!\\\)\]/u', array($this, 'insertLinkDefinition'), $line);
        //autolink
        $line = preg_replace_callback('/(?<!\\\)<(\w+@(\w+[.])*\w+)>/u', array($this, 'insertEmail'), $line);
        $line = preg_replace('/(?<!\\\)<((?:http|https|ftp):\/\/.*?)(?<!\\\)>/u', '<a href="$1">$1</a>', $line);
        //bold & itallic
        $line = preg_replace('/(?<!\\\)(\*\*|__)(.+?)(?<!\\\)\1/u', '<strong>$2</strong>', $line);
        $line = preg_replace('/(?<!\\\)(\*|_)(.+?)(?<!\\\)\1/u', '<em>$2</em>', $line);
        $line = str_replace("  \n", '<br />', $line);
        return $line;
    }

    private function randomize($str) {
        $out = '';
        $strlen = strlen($str);
        for ($i = 0; $i < $strlen; $i++) {
            switch (rand(0, 2)) {
                case 0:
                    $out .= '&#' . ord($str[$i]) . ';';
                    break;
                case 1:
                    $out .= $str[$i];
                    break;
                case 2:
                    $out .= '&#x' . dechex(ord($str[$i])) . ';';
                    break;
            }
        }
        return $out;
    }

    private function insertCode($matches) {
        return '<code>' . self::escape(htmlspecialchars($matches[2])) . '</code>';
    }

    private function insertEmail($matches) {
        $mail = $this->randomize($matches[1]);
        $mailto = $this->randomize('mailto:' . $matches[1]);
        return sprintf('<a href="%s">%s</a>', $mailto, $mail);
    }

    private function insertLink($matches) {
        if (isset($matches[3])) {
            return sprintf('<a href="%s" title="%s">%s</a>', Markdown::escape($matches[2]), Markdown::escape($matches[3]), $matches[1]);
        } else {
            return sprintf('<a href="%s">%s</a>', Markdown::escape($matches[2]), $matches[1]);
        }
    }

    private function insertImage($matches) {
        $matches = array_map('Markdown::escape', $matches);
        if (isset($matches[3])) {
            return sprintf('<img src="%s" title="%s" alt="%s" />', $matches[2], $matches[3], $matches[1]);
        } else {
            return sprintf('<img src="%s" alt="%s" />', $matches[2], $matches[1]);
        }
    }

    private function insertLinkDefinition($matches) {
        if (empty($matches[2])) {
            if (isset($this->links[$matches[1]])) {
                $link = $this->links[$matches[1]];
            }
        } else {
            if (isset($this->links[$matches[2]])) {
                $link = $this->links[$matches[2]];
            }
        }
        if (!isset($link)) {
            return $matches[0];
        }
        $link[1] = $matches[1];
        return $this->insertLink($link);
    }

    private function insertImageDefinition($matches) {
        if (empty($matches[2])) {
            if (isset($this->links[$matches[1]])) {
                $link = $this->links[$matches[1]];
            }
        } else {
            if (isset($this->links[$matches[2]])) {
                $link = $this->links[$matches[2]];
            }
        }
        if (!isset($link)) {
            return $matches[0];
        }
        $link[1] = $matches[1];
        return $this->insertImage($link);
    }

    private function collectLinkDefinition($matches) {
        $arr = array(
            2 => preg_replace(
                    array(
                '/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/',
                '#<(?![a-z/?\$!])#'
                    ), array(
                '&amp;',
                '&lt;'
                    ), $matches[2])); //url
        if (isset($matches[3])) {
            $arr[3] = str_replace('"', '&quot;', $matches[3]); //title
        }
        $this->links[$matches[1]] = $arr;
        return '';
    }

    private function prepare($text) {
        $this->structure = array();
        $arr = array(
            "\r\n" => "\n",
            "\r"   => "\n",
            "\t"   => '    ',
        );
        $text = strtr($text, $arr);
        $text = preg_replace("/^\s*$/mu", '', $text);
        $text = $this->hashHTML($text);
        return preg_replace_callback('/^[ ]{0,3}\[(.*)\]:[ ]*\n?[ ]*<?(\S+?)>?[ ]*\n?[ ]*(?:(?<=\s)["(](.*?)[")][ ]*)?(?:\n+|\Z)/mu', array($this, 'collectLinkDefinition'), $text);
    }

    private function callbackHeader($str, $level) {
        return sprintf('<h%2$d>%1$s</h%2$d>' . "\n\n", $this->formatLine($str), $level);
    }

    private function callbackInsertHeader($matches) {
        return $this->callbackHeader($matches[2], strlen($matches[1]));
    }

    private function callbackInsertSetexHeader($matches) {
        switch ($matches[2]) {
            case '=':
                $level = 1;
                break;
            case '-':
                $level = 2;
                break;
        }
        return $this->callbackHeader($matches[1], $level);
    }

    private function transformHeaders($text) {
        $text = preg_replace_callback('/^(.+)[ ]*\n(=|-)+[ ]*\n+/mu', array($this, 'callbackInsertSetexHeader'), $text);
        return preg_replace_callback('/^(#{1,6})\s*(.+?)\s*#*\n+/mu', array($this, 'callbackInsertHeader'), $text);
    }

    private function transformHorizontalRules($text) {
        $text = preg_replace('/[ ]{0,2}([ ]?\*[ ]?){3,}\s*/', "<hr />\n", $text);
        $text = preg_replace('/[ ]{0,2}([ ]?_[ ]?){3,}\s*/', "<hr />\n", $text);
        $text = preg_replace('/[ ]{0,2}([ ]?-[ ]?){3,}\s*/', "<hr />\n", $text);
        return $text;
    }

    private function transformLists($text) {
        return preg_replace_callback(
                        '/^(([ ]{0,3}((?:[*+-]|\d+[.]))[ ]+)(?s:.+?)(\z|\n{2,}(?=\S)(?![ ]*(?:[*+-]|\d+[.])[ ]+)))/mu', array($this, 'transformListsCallback'), $text);
    }

    private function transformListsCallback($matches) {
        $list = preg_replace('/\n{2,}/', "\n\n\n", $matches[1]);
        $list = preg_replace('/\n{2,}$/', "\n", $list);
        $list = preg_replace_callback(
                '/(\n)?(^[ ]*)([*+-]|\d+[.])[ ]+((?s:.+?)(?:\z|\n{1,2}))(?=\n*(?:\z|\2([*+-]|\d+[.])[ ]+))/mu', array($this, 'processListItemsCallback'), $list);

        if (in_array($matches[3], array('*', '+', '-'))) {
            $element = 'ul';
        } else {
            $element = 'ol';
        }
        return '<' . $element . ">" . $list . '</' . $element . ">\n";
    }

    private function processListItemsCallback($matches) {
        $item = $matches[4];
        $leading_line = $matches[1];
        if ($leading_line || (strpos($item, "\n\n") !== false)) {
            $item = $this->formatBlock($this->outdent($item));
        } else {
            $item = $this->transformLists($this->outdent($item));
            $item = rtrim($item);
            $item = $this->formatLine($item);
        }
        return '<li>' . $item . "</li>\n";
    }

    private function transformCodeBlocksCallback($matches) {
        $matches[1] = self::escape($this->outdent($matches[1]));
        $matches[1] = ltrim($matches[1], "\n");
        $matches[1] = rtrim($matches[1]);
        $matches[1] = "\n\n<pre><code>" . $matches[1] . "\n</code></pre>\n\n";
        return $matches[1];
    }

    private function transformCodeBlocks($text) {
        return preg_replace_callback('/(?:\n\n|\A)((?:(?:[ ]{4}).*\n+)+)((?=^[ ]{0,4}\S)|\Z)/mu', array($this, 'transformCodeBlocksCallback'), $text);
    }

    private function trimBlockQuotePre($matches) {
        return preg_replace('/^  /m', '', $matches[0]);
    }

    private function transformBlockQuotesCallback($matches) {
        $bq = $matches[1];
        $bq = preg_replace('/^[ ]*>[ ]?/', '', $bq);
        $bq = '  ' . $bq;
        $bq = preg_replace_callback('#\s*<pre>.+?</pre>#s', array($this, 'trimBlockQuotePre'), $bq);
        return "<blockquote>\n" . $bq . "\n</blockquote>\n\n";
    }

    private function transformBlockQuotes($text) {
        return preg_replace_callback('/((^[ ]*>[ ]?.+\n(.+\n)*(?:\n)*)+)/mu', array($this, 'transformBlockQuotesCallback'), $text);
    }

    private function makeParagraphs($text) {
        $text = preg_replace('/\\A\n+/', '', $text);
        $text = preg_replace('/\n+\\z/', '', $text);
        $lines = preg_split('/\n{2,}/', $text);
        foreach ($lines as &$line) {
            if (!isset($this->html_blocks[$line])) {
                $line = $this->formatLine($line) . '</p>';
                $line = preg_replace('/^([ \t]*)/u', '<p>', $line);
            } else {
                $line = $this->html_blocks[$line];
            }
        }
        return implode("\n\n", $lines);
    }

    private function storeHTMLBlock($matches) {
        $key = hash('md5', $matches[1]);
        $this->html_blocks[$key] = $matches[1];
        return "\n\n" . $key . "\n\n";
    }

    private function hashHTML($text) {
        $block_tags_a = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del';
        $block_tags_b = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math';

        $text = preg_replace_callback('#(^<(' . $block_tags_a . ')\b(.*\n)*?</\2>[ \t]*(?=\n+|\Z))#mux', array($this, 'storeHTMLBlock'), $text);

        $text = preg_replace_callback('#(^<(' . $block_tags_b . ')\b(.*\n)*?.*</\2>[ \t]*(?=\n+|\Z))#mux', array($this, 'storeHTMLBlock'), $text);

        $text = preg_replace_callback('#(?:(?<=\n\n)|\A\n?)([ ]{0,3}<(hr)\b([^<>])*?/?>[ \t]*(?=\n{2,}|\Z))#mux', array($this, 'storeHTMLBlock'), $text);

        $text = preg_replace_callback('#(?:(?<=\n\n)|\A\n?)([ ]{0,3}(?s:<!(--.*?--\s*)+>)[ \t]*(?=\n{2,}|\Z))#mux', array($this, 'storeHTMLBlock'), $text);
        return $text;
    }

    private function outdent($text) {
        return preg_replace('/^([ ]{1,4})/m', '', $text);
    }

    private function formatBlock($text) {
        $text = $this->transformHeaders($text);
        $text = $this->transformHorizontalRules($text);
        $text = $this->transformLists($text);
        $text = $this->transformCodeBlocks($text);
        $text = $this->transformBlockQuotes($text);
        $text = $this->hashHTML($text);
        $text = $this->makeParagraphs($text);
        return $text;
    }

    public function format($text) {
        $this->links = array();
        $this->html_blocks = array();

        $text = $this->prepare($text);
        $text = $this->formatBlock($text);
        return self::unescape($text);
    }

}