<?php
/*
 * Copyright (C) 2020 David Blanchard
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Reed\Web\UI;

use Exception;
use Reed\Cache\TCache;
use Reed\Registry\TRegistry;
use Reed\Xml\TXmlDocument;
use Reed\Template\TCustomTemplate;
use Reed\Template\TPartialTemplate;

/**
 * Description of code_generator
 *
 * @author David
 */
trait TCodeGenerator
{
    private $_reservedDeclarationsKeywords = ['page', 'echo', 'exec', 'type', 'block', 'extends'];
    private $_reservedHtmlKeywords = ['echo', 'exec', 'render', 'block'];

    public function writeDeclarations(TXmlDocument $doc, TCustomTemplate $parentTemplate)
    {

        $result = '';
        $dictionary = $parentTemplate->getDictionary();
        $matches = $doc->getDepthsOfMatches();
        $docList = $doc->getList();
        $count = count($docList);
        $uid = $parentTemplate->getUID();

        $code = '';
        $uses = [];
        $requires = [];
        $creations = [];
        $additions = [];
        $afterBinding = [];

        $childName = [];
        $childrenIndex = [];

        self::getLogger()->debug('DOC LIST');
        self::getLogger()->debug($docList);

        $isFirst = true;
        foreach ($docList as $control) {
            if (in_array($control['name'], $this->_reservedDeclarationsKeywords) || $control['method'] == 'render') {
                continue;
            }

            $className = '';
            $nameSpace = '';
            $classPath = '';
            $templatePath = '';
            $j = $control['id'];

            if (isset($control['properties'])) {
                $parentId = $control['parentId'];
                $parentControl = ($parentId > -1) ? $docList[$parentId] : [];
                if (isset($parentControl['childName'])) {
                    $parentChildName = $parentControl['childName'];
                    $controlName = $control['name'];

                    if ($parentChildName == $controlName) {
                        if (!isset($childrenIndex["$parentId"])) {
                            $childrenIndex[$parentId] = 0;
                        } else {
                            $childrenIndex[$parentId] = $childrenIndex[$parentId] + 1;
                        }
                    }
                }

                $properties = $control['properties'];

                try {
                    $controlId = isset($properties['id']) ? $properties['id'] : null;
                    if ($controlId === null) {
                        throw new Exception("This control has no 'id' attribute. Please provide one.");
                    }
                } catch (Exception $ex) {
                    self::getLogger()->exception($ex);
                }

                //$className = ucfirst($control['name']);
                $className = $control['name'];
                $fqcn = '';
                $code  = '';
                $info = TRegistry::classInfo($className);
                //self::$logger->dump('REGISTRY INFO ' . $className, $info);
                if ($info) {
                    if (!$info->isAutoloaded) {
                        array_push($requires, '\\Reed\\TCustomTemplate::import($this, "' . $className . '");');
                        //                        array_push($requires, '$this->import("' . $className . '");');
                    }
                    $fqcn = $info->namespace . '\\' . $className;
                } elseif ($className !== 'this') {
                    $viewName = TCustomTemplate::userClassNameToFilename($className);
                    $view = new TPartialTemplate($parentTemplate, [], $className);
                    $view->setNames();
                    $fullClassPath = $view->getControllerFileName();
                    $fullJsClassPath = $view->getJsControllerFileName();

                    $fullJsCachePath = TCache::cacheJsFilenameFromView($viewName, $parentTemplate->isInternalComponent());
                    array_push($requires, '\\Reed\\TCustomTemplate::import($this, "' . $className . '");');

                    self::getLogger()->dump('FULL_CLASS_PATH', $fullClassPath);

                    list($file, $fqcn, $code) = TCustomTemplate::includeClass($fullClassPath, RETURN_CODE);

                    self::getLogger()->dump('FULL_QUALIFIED_CLASS_NAME: ', $fqcn);

                    if (file_exists(DOCUMENT_ROOT . $fullJsCachePath)) {
                        $parentTemplate->getResponse()->addScriptFirst($fullJsCachePath);
                    }
                    if (file_exists(SRC_ROOT . $fullJsClassPath)) {
                        copy(SRC_ROOT . $fullJsClassPath, DOCUMENT_ROOT . $fullJsCachePath);
                        $parentTemplate->getResponse()->addScriptFirst($fullJsCachePath);
                    }
                    if (file_exists(SITE_ROOT . $fullJsClassPath)) {
                        copy(SITE_ROOT . $fullJsClassPath, DOCUMENT_ROOT . $fullJsCachePath);
                        $parentTemplate->getResponse()->addScriptFirst($fullJsCachePath);
                    }
                    TRegistry::setCode($view->getUID(), $code);
                }

                $canRender = ($info && $info->canRender || !$info);
                $notThis = ($className != 'this');
                $serialize = $canRender && $notThis;
                $serialize = false;
                $index = '';
                if (isset($childrenIndex[$parentId])) {
                    $index = '[' . $childrenIndex[$parentId] . ']';
                }

                $thisControl = '$this' . (($notThis) ? '->' . $controlId . $index : '');

                $creations[$j] = [];
                $additions[$j] = [];
                $afterBinding[$j] = [];
                if ($isFirst) {
                    array_push($creations[$j], '$this->setId("' . $this->getViewName() . '"); ');
                    $isFirst = false;
                }

                foreach ($properties as $key => $value) {
                    if ($key == 'id') {
                        if ($serialize) {
                            array_push($creations[$j], 'if(!' . $thisControl . ' = \Reed\Core\TObject::wakeUp("' . $value . '")) {');
                        }
                        if ($notThis) {
                            array_push($creations[$j], $thisControl . ' = new \\' . $fqcn . '($this); ');
                        }

                        array_push($creations[$j], $thisControl . '->set' . ucfirst($key) . '("' . $value . '"); ');

                        if ($serialize) {
                            array_push($creations[$j], '}');
                            array_push($additions[$j], 'if(' . $thisControl . ' && !' . $thisControl . '->isAwake()) {');
                        }
                        continue;
                    }
                    if (is_numeric($value)) {
                        array_push($additions[$j], $thisControl . '->set' . ucfirst($key) . '(' . $value . '); ');
                        continue;
                    }
                    if (strpos($value, ':') > -1) {
                        $sa = explode(':', $value);
                        $member = $sa[1];
                        if ($sa[0] == 'var') {
                            // if ($key == 'for') {
                            //     array_push($afterBinding[$j], $thisControl . '->set' . ucfirst($key) . '($this->' . $member . '); ');
                            // } else {
                            array_push($additions[$j], $thisControl . '->set' . ucfirst($key) . '($this->' . $member . '); ');
                            // }
                        } elseif ($sa[0] == 'prop') {
                            array_push($additions[$j], $thisControl . '->set' . ucfirst($key) . '($this->get' . ucfirst($member) . '()); ');
                        }
                        continue;
                    }
                    if (!empty(strstr($value, '!#base64#'))) {
                        $plaintext = substr($value, 9);
                        $plaintext = \base64_decode($plaintext);
                        array_push($additions[$j], $thisControl . '->set' . ucfirst($key) . '(<<<PLAIN_TEXT' . PHP_EOL . $plaintext . PHP_EOL . 'PLAIN_TEXT' . PHP_EOL . '); ');
                        unset($value);
                        continue;
                    }
                    // if ($key == 'command') {
                    //     array_push($afterBinding[$j], $thisControl . '->set' . ucfirst($key) . '("' . $value . '"); ');
                    // } else {
                    array_push($additions[$j], $thisControl . '->set' . ucfirst($key) . '("' . $value . '"); ');
                    // }
                }
                if ($serialize) {
                    array_push($additions[$j], $thisControl . '->sleep(); ');
                    array_push($additions[$j], '} ');
                }
                array_push($additions[$j], '$this->addChild(' . $thisControl . ');');
                if ($canRender && $className !== 'this') {
                    array_push($additions[$j], '$html = ' .  $thisControl . '->getHtml();');
                    array_push($additions[$j], '\\Reed\\Registry\\TRegistry::push("' . $uid . '", "' . $controlId . '", $html);');
                }

                $creations[$j] = implode(PHP_EOL, $creations[$j]);
                $additions[$j] = implode(PHP_EOL, $additions[$j]);
                $afterBinding[$j] = implode(PHP_EOL, $afterBinding[$j]);
            }


            $method = $docList[$j]['method'];
            if ((TRegistry::classInfo($method) && TRegistry::classCanRender($method)) || !TRegistry::classInfo($method)) {
                $doc->fieldValue($j, 'method', 'render');
            }
        }

        $requires = array_unique($requires);
        $requires = implode(PHP_EOL, $requires);
        $uses = array_unique($uses);
        $uses = implode(PHP_EOL, $uses);

        $objectCreation = PHP_EOL;
        $objectCreation .= $requires . PHP_EOL;
        $objectCreation .= $uses . PHP_EOL;
        foreach ($matches as $matchIndex) {
            if (!isset($creations[$matchIndex])) {
                continue;
            }
            $objectCreation .= $creations[$matchIndex] . PHP_EOL;
        }

        $objectAdditions = PHP_EOL;
        foreach ($matches as $matchIndex) {
            if (!isset($additions[$matchIndex])) {
                continue;
            }
            $objectAdditions .= $additions[$matchIndex] . PHP_EOL;
        }

        $objectAfterBiding = PHP_EOL;
        foreach ($matches as $matchIndex) {
            if (!isset($afterBinding[$matchIndex])) {
                continue;
            }
            $objectAfterBiding .= $afterBinding[$matchIndex] . PHP_EOL;
        }

        return (object) ['creations' => $objectCreation, 'additions' => $objectAdditions, 'afterBinding' => $objectAfterBiding];
    }

    public function writeHTML(TXmlDocument $doc, TCustomTemplate $parentTemplate)
    {
        $dictionary = $parentTemplate->getDictionary();
        $viewHtml = $parentTemplate->getViewHtml();
        $uid = $parentTemplate->getUID();

        $count = $doc->getCount();
        $matchesByDepth = $doc->getDepthsOfMatches();
        $matchesById = $doc->getIDsOfMatches();
        $matchesByKey = $doc->getKeysOfMatches();

        for ($i = $count - 1; $i > -1; $i--) {
            $j = $matchesById[$i];
            $match = $doc->getMatchById($j);

            $tag = $match->getMethod();
            $name = $match->getName();

            if (!in_array($tag, $this->_reservedHtmlKeywords)) {
                continue;
            }

            $type = $match->properties('type');
            $class = $match->properties('class');
            $id = $match->properties('id');

            $const = $match->properties('const');
            $var = $match->properties('var');
            $prop = $match->properties('prop');
            $stmt = $match->properties('stmt');
            $params = $match->properties('params');
            $content = $match->properties('content');

            if (!$type || $type == 'this') {
                $type = '$this->';
            } elseif ($type == 'none') {
                $type = '';
            } else {
                $type = $type . '::' . (($tag == 'exec') ? '' : '$');
            }

            if ($tag == 'echo' && $const) {
                $declare = '<?php echo ' . $const . '; ?>';
            } elseif ($tag == 'echo' && $var) {
                /** $declare = '<?php echo ' . $type . $var . '; ?>';  */

                $declare = '<?php echo \\Reed\\Registry\\TRegistry::read("template", "' . $uid . '")["' . $var . '"];?>';
            } elseif ($tag == 'echo' && $prop) {
                $declare = '<?php echo ' . $type . 'get' . ucfirst($prop) . '(); ?>';
            } elseif ($tag == 'exec') {
                $declare = '<?php echo ' . $type . $stmt . '(); ?>';
                if ($params != null) {
                    $declare = '<?php echo ' . $type . $stmt . '(' . $params . '); ?>';
                }
            } elseif ($tag == 'block' && false !== $content) {
                $plaintext = substr($content, 9);
                $plaintext = \base64_decode($plaintext);
                $declare = $plaintext;
            } elseif ($tag == 'render') {
                if ($name == 'this') {
                    $declare = '<?php $this->renderHtml(); $this->renderedHtml(); ?>';
                } else {
                    /** $declare = '<?php ' . $type . $id . '->render(); ?>'; */
                    $declare = '<?php echo \\Reed\\Registry\\TRegistry::read("' . $uid . '", "' . $id . '")[0];?>';
                }
            }

            $viewHtml = $doc->replaceThisMatch($match, $viewHtml, $declare);
        }
        return $viewHtml;
    }
}
