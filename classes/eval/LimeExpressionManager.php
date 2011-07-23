<?php
/**
 * Description of LimeExpressionManager
 * This is a wrapper class around ExpressionManager that implements a Singleton and eases
 * passing of LimeSurvey variable values into ExpressionManager
 *
 * @author Thomas M. White
 */
include_once('ExpressionManager.php');

class LimeExpressionManager {
    private static $instance;
    private $em;    // Expression Manager
    private $relevanceInfo;
    private $tailorInfo;
    private $groupNum;
    private $debugLEM = true;   // set this to false to turn off debugging
    private $knownVars;
    
    // A private constructor; prevents direct creation of object
    private function __construct() 
    {
        $this->em = new ExpressionManager();
    }

    // The singleton method
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
    
    // Prevent users to clone the instance
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    /**
     * Return the dynamic function for making the current question visible or invisible
     */
    public function GetJavaScriptFunctionForRelevance($questionNum, $eqn)
    {
        $jsParts = array();
        $jsParts[] = "\n// Process Relevance for Question " . $questionNum . ": { " . $eqn . " }\n";
        $jsParts[] = "if (\n";
        $jsParts[] = $this->em->GetJavaScriptEquivalentOfExpression();
        $jsParts[] = "\n)\n{\n";
        $jsParts[] = "document.getElementById('question" . $questionNum . "').style.display='';\n";
        $jsParts[] = "document.getElementById('display" . $questionNum . "').value='on';\n";
        $jsParts[] = "}\n else {\n";
        $jsParts[] = "document.getElementById('question" . $questionNum . "').style.display='none';\n";
        $jsParts[] = "document.getElementById('display" . $questionNum . "').value='';\n";
        $jsParts[] = "}\n";
        return implode('',$jsParts);
    }

    /**
     * Create the arrays needed by ExpressionManager to process LimeSurvey strings.
     * The long part of this function should only be called once per page display (e.g. only if $fieldMap changes)
     *
     * @param <type> $forceRefresh
     * @param <type> $anonymized
     * @return boolean - true if $fieldmap had been re-created, so ExpressionManager variables need to be re-set
     */

    public function setVariableAndTokenMappingsForExpressionManager($forceRefresh=false,$anonymized=false)
    {
        global $surveyid;

        //checks to see if fieldmap has already been built for this page.
//        if (isset($globalfieldmap[$surveyid]['expMgr_varMap'][$clang->langcode])&& !$forceRefresh) {
//            return false;   // means the mappings have already been set and don't need to be re-created
//        }

        $fieldmap=createFieldMap($surveyid,$style='full',$forceRefresh);
        if (!isset($fieldmap)) {
            return false; // implies an error occurred
        }

        $sgqaMap = array();  // mapping of SGQA to Value
        $knownVars = array();   // mapping of VarName to Value
        $debugLog = array();    // array of mappings among values to confirm their accuracy
        foreach($fieldmap as $fielddata)
        {
            $code = $fielddata['fieldname'];
            if (!preg_match('#^\d+X\d+X\d+#',$code))
            {
                continue;   // not an SGQA value
            }
            $fieldNameParts = explode('X',$code);
            $groupNum = $fieldNameParts[1];
            $isOnCurrentPage = ($groupNum != NULL && $groupNum == $this->groupNum) ? 'Y' : 'N';

            $questionId = $fieldNameParts[2];
            $questionAttributes = getQuestionAttributes($questionId,$fielddata['type']);
            $relevance = (isset($questionAttributes['relevance'])) ? $questionAttributes['relevance'] : 1;
            switch($fielddata['type'])
            {
                case '!': //List - dropdown
                case '5': //5 POINT CHOICE radio-buttons
                case 'D': //DATE
                case 'G': //GENDER drop-down list
                case 'I': //Language Question
                case 'L': //LIST drop-down/radio-button list
                case 'N': //NUMERICAL QUESTION TYPE
                case 'O': //LIST WITH COMMENT drop-down/radio-button list + textarea
                case 'S': //SHORT FREE TEXT
                case 'T': //LONG FREE TEXT
                case 'U': //HUGE FREE TEXT
                case 'X': //BOILERPLATE QUESTION
                case 'Y': //YES/NO radio-buttons
                case '|': //File Upload
                case '*': //Equation
                    $varName = $fielddata['title'];
                    $question = $fielddata['question'];
                    break;
                case '1': //Array (Flexible Labels) dual scale
                    $varName = $fielddata['title'] . '.' . $fielddata['aid'] . '.' . $fielddata['scale_id'];
                    $question = $fielddata['question'] . ': ' . $fielddata['subquestion'] . ': ' . $fielddata['scale'];
                    break;
                case 'A': //ARRAY (5 POINT CHOICE) radio-buttons
                case 'B': //ARRAY (10 POINT CHOICE) radio-buttons
                case 'C': //ARRAY (YES/UNCERTAIN/NO) radio-buttons
                case 'E': //ARRAY (Increase/Same/Decrease) radio-buttons
                case 'F': //ARRAY (Flexible) - Row Format
                case 'H': //ARRAY (Flexible) - Column Format
                case 'K': //MULTIPLE NUMERICAL QUESTION
                case 'M': //Multiple choice checkbox
                case 'P': //Multiple choice with comments checkbox + text
                case 'Q': //MULTIPLE SHORT TEXT
                case 'R': //RANKING STYLE
                    $varName = $fielddata['title'] . '.' . $fielddata['aid'];
                    $question = $fielddata['question'] . ': ' . $fielddata['subquestion'];
                    break;
                case ':': //ARRAY (Multi Flexi) 1 to 10
                case ';': //ARRAY (Multi Flexi) Text
                    $varName = $fielddata['title'] . '.' . $fielddata['aid'];
                    $question = $fielddata['question'] . ': ' . $fielddata['subquestion1'] . ': ' . $fielddata['subquestion2'];
                    break;
            }
            switch($fielddata['type'])
            {
                case 'R': //RANKING STYLE
                    if ($isOnCurrentPage=='Y')
                    {
                        $jsVarName = 'fvalue_' . $fieldNameParts[2];
                    }
                    else
                    {
                        $jsVarName = 'java' . $code;
                    }
                    break;
                case 'D': //DATE
                case 'N': //NUMERICAL QUESTION TYPE
                case 'S': //SHORT FREE TEXT
                case 'T': //LONG FREE TEXT
                case 'U': //HUGE FREE TEXT
                case 'Q': //MULTIPLE SHORT TEXT
                case 'K': //MULTIPLE NUMERICAL QUESTION
                    if ($isOnCurrentPage=='Y')
                    {
                        $jsVarName = 'answer' . $code;
                    }
                    else
                    {
                        $jsVarName = 'java' . $code;
                    }
                    break;
                case '!': //List - dropdown
                case '5': //5 POINT CHOICE radio-buttons
                case 'G': //GENDER drop-down list
                case 'I': //Language Question
                case 'L': //LIST drop-down/radio-button list
                case 'O': //LIST WITH COMMENT drop-down/radio-button list + textarea
                case 'X': //BOILERPLATE QUESTION
                case 'Y': //YES/NO radio-buttons
                case '|': //File Upload
                case '*': //Equation
                case '1': //Array (Flexible Labels) dual scale
                case 'A': //ARRAY (5 POINT CHOICE) radio-buttons
                case 'B': //ARRAY (10 POINT CHOICE) radio-buttons
                case 'C': //ARRAY (YES/UNCERTAIN/NO) radio-buttons
                case 'E': //ARRAY (Increase/Same/Decrease) radio-buttons
                case 'F': //ARRAY (Flexible) - Row Format
                case 'H': //ARRAY (Flexible) - Column Format
                case 'M': //Multiple choice checkbox
                case 'P': //Multiple choice with comments checkbox + text
                case ':': //ARRAY (Multi Flexi) 1 to 10
                case ';': //ARRAY (Multi Flexi) Text
                    $jsVarName = 'java' . $code;
                    break;
            }
            $readWrite = 'N';
            if (isset($_SESSION[$code]))
            {
                $codeValue = $_SESSION[$code];
                $displayValue= retrieve_Answer($code, $_SESSION['dateformats']['phpdate']);
                $varInfo_Code = array(
                    'codeValue'=>$codeValue,
                    'jsName'=>$jsVarName,
                    'readWrite'=>$readWrite,
                    'isOnCurrentPage'=>$isOnCurrentPage,
                    'displayValue'=>$displayValue,
                    'question'=>$question,
                    'relevance'=>$relevance,
                    );
                $varInfo_DisplayVal = array(
                    'codeValue'=>$displayValue,
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>$isOnCurrentPage,
                    );
                $varInfo_Question = array(
                    'codeValue'=>$question,
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>$isOnCurrentPage,
                    );
                $knownVars[$varName] = $varInfo_Code;
                $knownVars[$varName . '.shown'] = $varInfo_DisplayVal;
                $knownVars[$varName . '.question']= $varInfo_Question;
                $knownVars['INSERTANS:' . $code] = $varInfo_DisplayVal;
            }
            else
            {
                unset($codeValue);
                unset($displayValue);
                $knownVars['INSERTANS:' . $code] = array(
                    'codeValue'=>'',
                    'jsName'=>'',
                    'readWrite'=>$readWrite,
                    'isOnCurrentPage'=>$isOnCurrentPage,
                );
            }
            if ($this->debugLEM)
            {
                $debugLog[] = array(
                    'code' => $code,
                    'type' => $fielddata['type'],
                    'varname' => $varName,
                    'jsName' => $jsVarName,
                    'question' => $question,
                    'codeValue' => isset($codeValue) ? $codeValue : '&nbsp;',
                    'displayValue' => isset($displayValue) ? $displayValue : '&nbsp;',
                    'readWrite' => $readWrite,
                    'isOnCurrentPage' => $isOnCurrentPage,
                    'relevance' => $relevance,
                    );
            }

        }

        // Now set tokens
        if (isset($_SESSION['token']) && $_SESSION['token'] != '')
        {
            //Gather survey data for tokenised surveys, for use in presenting questions
            $_SESSION['thistoken']=getTokenData($surveyid, $_SESSION['token']);
        }
        if (isset($_SESSION['thistoken']))
        {
            foreach (array_keys($_SESSION['thistoken']) as $tokenkey)
            {
                if ($anonymized)
                {
                    $val = "";
                }
                else
                {
                    $val = $_SESSION['thistoken'][$tokenkey];
                }
                $key = "TOKEN:" . strtoupper($tokenkey);
                $knownVars[$key] = array(
                    'codeValue'=>$val,
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>'N',
                    );

                if ($this->debugLEM)
                {
                    $debugLog[] = array(
                        'code' => $key,
                        'type' => '&nbsp;',
                        'varname' => '&nbsp;',
                        'jsName' => '&nbsp;',
                        'question' => '&nbsp;',
                        'codeValue' => '&nbsp;',
                        'displayValue' => $val,
                        'readWrite'=>'N',
                        'isOnCurrentPage'=>'N',
                        'relevance'=>'',
                    );
                }
            }
        }
        else
        {
            // Explicitly set all tokens to blank
            $blankVal = array(
                    'codeValue'=>'',
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>'N',
                    );
            $knownVars['TOKEN:FIRSTNAME'] = $blankVal;
            $knownVars['TOKEN:LASTNAME'] = $blankVal;
            $knownVars['TOKEN:EMAIL'] = $blankVal;
            $knownVars['TOKEN:USESLEFT'] = $blankVal;
            for ($i=1;$i<=100;++$i) // TODO - is there a way to know  how many attributes are set?  Looks like max is 100
            {
                $knownVars['TOKEN:ATTRIBUTE_' . $i] = $blankVal;
            }
        }

        if ($this->debugLEM)
        {
            $debugLog_html = "<table border='1'>";
            $debugLog_html .= "<tr><th>Code</th><th>Type</th><th>VarName</th><th>CodeVal</th><th>DisplayVal</th><th>JSname</th><th>Writable?</th><th>Set On This Page?</th><th>Relevance</th><th>Question</th></tr>";
            foreach ($debugLog as $t)
            {
                $debugLog_html .= "<tr><td>" . $t['code']
                    . "</td><td>" . $t['type']
                    . "</td><td>" . $t['varname']
                    . "</td><td>" . $t['codeValue']
                    . "</td><td>" . $t['displayValue']
                    . "</td><td>" . $t['jsName']
                    . "</td><td>" . $t['readWrite']
                    . "</td><td>" . $t['isOnCurrentPage']
                    . "</td><td>" . $t['relevance']
                    . "</td><td>" . $t['question']
                    . "</td></tr>";
            }
            $debugLog_html .= "</table>";
            file_put_contents('/tmp/LimeExpressionManager-page.html',$debugLog_html);
        }
        
        $this->knownVars = $knownVars;

        return true;
    }

    /**
     * Translate all Expressions, Macros, registered variables, etc. in $string
     * @param <type> $string - the string to be replaced
     * @param <type> $replacementFields - optional replacement values
     * @param boolean $debug - if true,write translations for this page to html-formatted log file
     * @param <type> $numRecursionLevels - the number of times to recursively subtitute values in this string
     * @param <type> $whichPrettyPrintIteration - if want to pretty-print the source string, which recursion  level should be pretty-printed
     * @return <type> - the original $string with all replacements done.
     */

    static function ProcessString($string, $replacementFields=array(), $debug=false, $numRecursionLevels=1, $whichPrettyPrintIteration=1)
    {
        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;

        if (isset($replacementFields) && is_array($replacementFields) && count($replacementFields) > 0)
        {
            $replaceArray = array();
            foreach ($replacementFields as $key => $value) {
                $replaceArray[$key] = array(
                    'codeValue'=>$value,
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>'N',
                );
            }
            $em->RegisterVarnamesUsingMerge($replaceArray);   // TODO - is it safe to just merge these in each time, or should a refresh be forced?
        }
        $result = $em->sProcessStringContainingExpressions(htmlspecialchars_decode($string),$numRecursionLevels, $whichPrettyPrintIteration);

        if ($debug && $lem->debugLEM)
        {
            $debugLog_html = '<tr><td>' . $lem->groupNum . '</td><td>' . $string . '</td><td>' . $em->GetLastPrettyPrintExpression() . '</td><td>' . $result . "</td></tr>\n";
            file_put_contents('/tmp/LimeExpressionManager-Debug-ThisPage.html',$debugLog_html,FILE_APPEND);
        }

        return $result;
    }


    /**
     * Compute Relevance, processing $string to get a boolean value.  If there are syntax errors, currently returns true.  My change to returning null so can look for errors?
     * @param <type> $string
     * @return <type>
     */
    static function ProcessRelevance($string,$questionNum=NULL)
    {
        if (!isset($string) || trim($string=='') || trim($string)=='1')
        {
            return true;
        }
        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $result = $em->ProcessBooleanExpression(htmlspecialchars_decode($string));
        if (is_null($questionNum))
        {
            return $result;
        }
        $jsVars = $em->GetJSVarsUsed();
        if (count($jsVars) > 0)
        {
            $relevanceVars = implode('|',$em->GetJSVarsUsed());
            $relevanceJS = $lem->GetJavaScriptFunctionForRelevance($questionNum,$string);
            $lem->relevanceInfo[] = array(
                'qid' => $questionNum,
                'result' => $result,
                'relevancejs' => $relevanceJS,
                'relevanceVars' => $relevanceVars,
            );
        }
        return $result;
    }

    static function StartProcessingGroup($groupNum=NULL,$anonymized=false)
    {
        global $surveyid;

        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $em->StartProcessingGroup();
        if (!is_null($groupNum))
        {
            $lem->groupNum = $groupNum;

            if (!is_null($surveyid) && $lem->setVariableAndTokenMappingsForExpressionManager(true,$anonymized))
            {
                // means that some values changed, so need to update what was registered to ExpressionManager
                $em->RegisterVarnamesUsingReplace($lem->knownVars);

                if ($lem->debugLEM)
                {
                    $debugLog_html = '<tr><th>Group</th><th>Source</th><th>Pretty Print</th><th>Result</th></tr>';
                    file_put_contents('/tmp/LimeExpressionManager-Debug-ThisPage.html',$debugLog_html); // replace the value
                }
            }
        }
        $lem->relevanceInfo = array();
        $lem->tailorInfo = array();
    }

    static function FinishProcessingGroup()
    {
        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $lem->tailorInfo[] = $em->GetCurrentSubstitutionInfo();
    }

    /*
     * Generate JavaScript needed to do dynamic relevance and tailoring
     * Also create list of variables that need to be declared
     */
    static function GetRelevanceAndTailoringJavaScript()
    {
        global $rooturl;

        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;

        $knownVars = $lem->knownVars;

        $jsParts=array();
        $allJsVarsUsed=array();
        $jsParts[] = '<script type="text/javascript" src="' . $rooturl . '/classes/eval/ExpressionManager_CoreJSFunctions.js"></script>';
        $jsParts[] = "<script type='text/javascript'>\n<!--\n";
        $jsParts[] = "function ExprMgr_process_relevance_and_tailoring(){\n";
        // Which should come first - relevance or tailoring (or both)?
        foreach ($lem->tailorInfo as $tailor)
        {
            if (is_array($tailor))
            {
                foreach ($tailor as $sub)
                {
                    $jsParts[] = $sub['js'];
                    $vars = explode('|',$sub['vars']);
                    if (is_array($vars))
                    {
                        $allJsVarsUsed = array_merge($allJsVarsUsed,$vars);
                    }
                }
            }
        }
        if (is_array($lem->relevanceInfo))
        {
            foreach ($lem->relevanceInfo as $arg)
            {
                $jsParts[] = $arg['relevancejs'];
                $vars = explode('|',$arg['relevanceVars']);
                if (is_array($vars))
                {
                    $allJsVarsUsed = array_merge($allJsVarsUsed,$vars);
                }
            }
        }
        $jsParts[] = "}\n";
        $jsParts[] = "//-->\n</script>\n";

        // Now figure out which variables have not been declared (those not on the current page)
        $undeclaredJsVars = array();
        $undeclaredVal = array();
        $allJsVarsUsed = array_unique($allJsVarsUsed);
        foreach ($knownVars as $knownVar)
        {
            foreach ($allJsVarsUsed as $jsVar)
            {
                if ($jsVar == $knownVar['jsName'])
                {
                    if ($knownVar['isOnCurrentPage']=='N')
                    {
                        $undeclaredJsVars[] = $jsVar;
                        $undeclaredVal[$jsVar] = $knownVar['codeValue'];
                        break;
                    }
                }
            }
        }
        $undeclaredJsVars = array_unique($undeclaredJsVars);
        foreach ($undeclaredJsVars as $jsVar)
        {
            // TODO - is different type needed for text?  Or process value to striphtml?
            $jsParts[] = "<input type='hidden' id='" . $jsVar . "' name='" . $jsVar . "' value='" . htmlspecialchars($undeclaredVal[$jsVar]) . "'/>\n";
        }
        
        return implode('',$jsParts);
    }

    /**
     * Unit test
     */
    static function UnitTestProcessStringContainingExpressions()
    {
        $vars = array(
'name' => array('codeValue'=>'Sergei', 'jsName'=>'java61764X1X1', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
'age' => array('codeValue'=>45, 'jsName'=>'java61764X1X2', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
'numKids' => array('codeValue'=>2, 'jsName'=>'java61764X1X3', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
'numPets' => array('codeValue'=>1, 'jsName'=>'java61764X1X4', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
// Constants
'INSERTANS:61764X1X1'   => array('codeValue'=> 'Sergei', 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'Y'),
'INSERTANS:61764X1X2'   => array('codeValue'=> 45, 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'Y'),
'INSERTANS:61764X1X3'   => array('codeValue'=> 2, 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'N'),
'INSERTANS:61764X1X4'   => array('codeValue'=> 1, 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'N'),
'TOKEN:ATTRIBUTE_1'     => array('codeValue'=> 'worker', 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'N'),
        );

        $tests = <<<EOD
{name}
{age}
{numKids}
{numPets}
{INSERTANS:61764X1X1}
{INSERTANS:61764X1X2}
{INSERTANS:61764X1X3}
{INSERTANS:61764X1X4}
{TOKEN:ATTRIBUTE_1}
{name}, you said that you are {age} years old, and that you have {numKids} {if((numKids==1),'child','children')} and {numPets} {if((numPets==1),'pet','pets')} running around the house. So, you have {numKids + numPets} wild {if((numKids + numPets ==1),'beast','beasts')} to chase around every day.
Since you have more {if((numKids > numPets),'children','pets')} than you do {if((numKids > numPets),'pets','children')}, do you feel that the {if((numKids > numPets),'pets','children')} are at a disadvantage?
{INSERTANS:61764X1X1}, you said that you are {INSERTANS:61764X1X2} years old, and that you have {INSERTANS:61764X1X3} {if((INSERTANS:61764X1X3==1),'child','children')} and {INSERTANS:61764X1X4} {if((INSERTANS:61764X1X4==1),'pet','pets')} running around the house.  So, you have {INSERTANS:61764X1X3 + INSERTANS:61764X1X4} wild {if((INSERTANS:61764X1X3 + INSERTANS:61764X1X4 ==1),'beast','beasts')} to chase around every day.
Since you have more {if((INSERTANS:61764X1X3 > INSERTANS:61764X1X4),'children','pets')} than you do {if((INSERTANS:61764X1X3 > INSERTANS:61764X1X4),'pets','children')}, do you feel that the {if((INSERTANS:61764X1X3 > INSERTANS:61764X1X4),'pets','children')} are at a disadvantage?
{name2}, you said that you are {age + 5)} years old, and that you have {abs(numKids) -} {if((numKids==1),'child','children')} and {numPets} {if((numPets==1),'pet','pets')} running around the house. So, you have {numKids + numPets} wild {if((numKids + numPets ==1),'beast','beasts')} to chase around every day.
{INSERTANS:61764X1X1}, you said that you are {INSERTANS:61764X1X2} years old, and that you have {INSERTANS:61764X1X3} {if((INSERTANS:61764X1X3==1),'child','children','kiddies')} and {INSERTANS:61764X1X4} {if((INSERTANS:61764X1X4==1),'pet','pets')} running around the house.  So, you have {INSERTANS:61764X1X3 + INSERTANS:61764X1X4} wild {if((INSERTANS:61764X1X3 + INSERTANS:61764X1X4 ==1),'beast','beasts')} to chase around every day.
This line should throw errors since the curly-brace enclosed functions do not have linefeeds after them (and before the closing curly brace): var job='{TOKEN:ATTRIBUTE_1}'; if (job=='worker') { document.write('BOSSES') } else { document.write('WORKERS') }
This line has a script section, but if you look at the source, you will see that it has errors: <script type="text/javascript" language="Javascript">var job='{TOKEN:ATTRIBUTE_1}'; if (job=='worker') {document.write('BOSSES')} else {document.write('WORKERS')} </script>.
Substitions that begin or end with a space should be ignored: { name} {age }
EOD;
        $alltests = explode("\n",$tests);

        $javascript1 = <<<EOST
                    var job='{TOKEN:ATTRIBUTE_1}';
                    if (job=='worker') {
                    document.write('BOSSES')
                    } else {
                    document.write('WORKERS')
                    }
EOST;
        $javascript2 = <<<EOST
var job='{TOKEN:ATTRIBUTE_1}';
    if (job=='worker') {
       document.write('BOSSES')
    } else { document.write('WORKERS')  }
EOST;
        $alltests[] = 'This line should have no errors - the Javascript has curly braces followed by line feeds:' . $javascript1;
        $alltests[] = 'This line should also be OK: ' . $javascript2;
        $alltests[] = 'This line has a hidden script: <script type="text/javascript" language="Javascript">' . $javascript1 . '</script>';
        $alltests[] = 'This line has a hidden script: <script type="text/javascript" language="Javascript">' . $javascript2 . '</script>';

        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $em->StartProcessingGroup();

        $em->RegisterVarnamesUsingMerge($vars);

        print '<table border="1"><tr><th>Test</th><th>Result</th><th>VarName(jsName, readWrite, isOnCurrentPage)</th></tr>';
        for ($i=0;$i<count($alltests);++$i)
        {
            $test = $alltests[$i];
            $result = $em->sProcessStringContainingExpressions($test,2,1);
            $prettyPrint = $em->GetLastPrettyPrintExpression();
            print "<tr><td>" . $prettyPrint . "</td>\n";
            print "<td>" . $result . "</td>\n";
            $varsUsed = $em->getAllVarsUsed();
            if (is_array($varsUsed) and count($varsUsed) > 0) {
                $varDesc = array();
                foreach ($varsUsed as $v) {
                    $varInfo = $em->GetVarInfo($v);
                    $varDesc[] = $v . '(' . $varInfo['jsName'] . ',' . $varInfo['readWrite'] . ',' . $varInfo['isOnCurrentPage'] . ')';
                }
                print '<td>' . implode(',<br/>', $varDesc) . "</td>\n";
            }
            else {
                print "<td>&nbsp;</td>\n";
            }
            print "</tr>\n";
        }
        print '</table>';
    }

    static function UnitTestRelevance()
    {
        // Tests:  varName~relevance~inputType~message
        $tests = <<<EOT
name~1~text~What is your name?
age~1~text~How old are you?
badage~1~expr~{badage=((age<16) || (age>80))}
agestop~badage~message~Sorry, {name}, you are too {if((age<16),'young',if((age>80),'old','young-or-old'))} for this test.
kids~!badage~yesno~Do you have children?
parents~1~expr~{parents = (!badage && kids=='Y')}
numKids~parents~text~How many children do you have?
kid1~parents && numKids >= 1~text~How old is your first child?
kid2~parents && numKids >= 2~text~How old is your second child?
kid3~parents && numKids >= 3~text~How old is your third child?
kid4~parents && numKids >= 4~text~How old is your fourth child?
kid5~parents && numKids >= 5~text~How old is your fifth child?
sumage~1~expr~{sumage=sum(kid1,kid2,kid3,kid4,kid5)}
report~parents~yesno~{name}, you said you are {age} and that you have {numKids}.  The sum of ages of your first {min(numKids,5)} kids is {sum(kid1,kid2,kid3,kid4,kid5)}.
EOT;

        $vars = array();
        $varSeq = array();
        $testArgs = array();
        $argInfo = array();

        // collect variables
        foreach(explode("\n",$tests) as $test)
        {
            $args = explode("~",$test);
            $vars[$args[0]] = array('codeValue'=>'', 'jsName'=>'java_' . $args[0], 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y');
            $varSeq[] = $args[0];
            $testArgs[] = $args;
        }

        LimeExpressionManager::StartProcessingGroup();

        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $em->RegisterVarnamesUsingMerge($vars);

        // collect relevance
        for ($i=0;$i<count($testArgs);++$i)
        {
            $testArg = $testArgs[$i];
            $var = $testArg[0];
            LimeExpressionManager::ProcessRelevance(htmlspecialchars_decode($testArg[1]),$i);
            $question = LimeExpressionManager::ProcessString($testArg[3], NULL, true, 1, 1);

            $argInfo[] = array(
                'num' => $i,
                'name' => 'java_' . $testArg[0],
                'type' => $testArg[2],
                'question' => $question,
            );
        }
        LimeExpressionManager::FinishProcessingGroup();

        print LimeExpressionManager::GetRelevanceAndTailoringJavaScript();

        // Print Table of questions
        print "<table border='1'><tr><td>";
        foreach ($argInfo as $arg)
        {
            print "<input type='hidden' id='display" . $arg['num'] . "' value=''/>\n";
            print "<div id='question" . $arg['num'] . "'>\n";
            if ($arg['type'] == 'expr')
            {
                print "<div style='display: none' name='" . $arg['name'] . "' id='" . $arg['name'] . "'>" . $arg['question'] . "</div>\n";
            }
            else {
                print "<table border='1' width='100%'><tr><td>[Q" . $arg['num'] . "] " . $arg['question'] . "</td>";
                switch($arg['type'])
                {
                    case 'yesno':
                    case 'text':
                        print "<td><input type='text' name='" . $arg['name'] . "' id='" . $arg['name'] . "' value='' onchange='ExprMgr_process_relevance_and_tailoring()'/></td>\n";
                        break;
                    case 'message':
                        print "";
                        break;
                }
                print "</tr></table></div>\n";
            }
        }
        print "</table>";
    }
}
?>
