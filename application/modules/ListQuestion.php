<?php
class ListQuestion extends QuestionModule
{
    public function getAnswerHTML()
    {
        global $dropdownthreshold;
        global $thissurvey;
        $clang=Yii::app()->lang;
        if ($thissurvey['nokeyboard']=='Y')
        {
            includeKeypad();
            $kpclass = "text-keypad";
        }
        else
        {
            $kpclass = "";
        }

        $checkconditionFunction = "checkconditions";

        $aQuestionAttributes = $this->getAttributeValues();

        //question attribute random order set?
        if ($aQuestionAttributes['random_order']==1) {
            $ansquery = "SELECT * FROM {{answers}} WHERE qid=$this->id AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' and scale_id=0 ORDER BY ".dbRandom();
        }

        //question attribute alphasort set?
        elseif ($aQuestionAttributes['alphasort']==1)
        {
            $ansquery = "SELECT * FROM {{answers}} WHERE qid=$this->id AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' and scale_id=0 ORDER BY answer";
        }

        //no question attributes -> order by sortorder
        else
        {
            $ansquery = "SELECT * FROM {{answers}} WHERE qid=$this->id AND language='".$_SESSION['survey_'.$this->surveyid]['s_lang']."' and scale_id=0 ORDER BY sortorder, answer";
        }

        $ansresult = dbExecuteAssoc($ansquery)->readAll();  //Checked
        $anscount = count($ansresult);

        if (trim($aQuestionAttributes['display_columns'])!='') {
            $dcols = $aQuestionAttributes['display_columns'];
        }
        else
        {
            $dcols= 1;
        }

        if (trim($aQuestionAttributes['other_replace_text'][$_SESSION['survey_'.$this->surveyid]['s_lang']])!='')
        {
            $othertext=$aQuestionAttributes['other_replace_text'][$_SESSION['survey_'.$this->surveyid]['s_lang']];
        }
        else
        {
            $othertext=$clang->gT('Other:');
        }

        if ($this->isother=='Y') {$anscount++;} //Count up for the Other answer
        if ($this->mandatory != 'Y' && SHOW_NO_ANSWER == 1) {$anscount++;} //Count up if "No answer" is showing

        $wrapper = setupColumns($dcols , $anscount,"answers-list radio-list","answer-item radio-item");
        $answer = $wrapper['whole-start'];

        //Time Limit Code
        if (trim($aQuestionAttributes['time_limit'])!='')
        {
            $answer .= return_timer_script($aQuestionAttributes, $this);
        }
        //End Time Limit Code

        // Get array_filter stuff

        $rowcounter = 0;
        $colcounter = 1;
        $trbc='';

        foreach ($ansresult as $ansrow)
        {
            $myfname = $this->fieldname.$ansrow['code'];
            $check_ans = '';
            if ($_SESSION['survey_'.$this->surveyid][$this->fieldname] == $ansrow['code'])
            {
                $check_ans = CHECKED;
            }

            list($htmltbody2, $hiddenfield)=return_array_filter_strings($this, $aQuestionAttributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname, "li","answer-item radio-item");
            if(substr($wrapper['item-start'],0,4) == "\t<li")
            {
                $startitem = "\t$htmltbody2\n";
            } else {
                $startitem = $wrapper['item-start'];
            }

            $answer .= $startitem;
            $answer .= "\t$hiddenfield\n";
            $answer .='		<input class="radio" type="radio" value="'.$ansrow['code'].'" name="'.$this->fieldname.'" id="answer'.$this->fieldname.$ansrow['code'].'"'.$check_ans.' onclick="if (document.getElementById(\'answer'.$this->fieldname.'othertext\') != null) document.getElementById(\'answer'.$this->fieldname.'othertext\').value=\'\';'.$checkconditionFunction.'(this.value, this.name, this.type)" />
            <label for="answer'.$this->fieldname.$ansrow['code'].'" class="answertext">'.$ansrow['answer'].'</label>
            '.$wrapper['item-end'];

            ++$rowcounter;
            if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
            {
                if($colcounter == $wrapper['cols'] - 1)
                {
                    $answer .= $wrapper['col-devide-last'];
                }
                else
                {
                    $answer .= $wrapper['col-devide'];
                }
                $rowcounter = 0;
                ++$colcounter;
            }
        }

        if ($this->isother=='Y')
        {

            $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
            $sSeperator = $sSeperator['seperator'];

            if ($aQuestionAttributes['other_numbers_only']==1)
            {
                $oth_checkconditionFunction = 'fixnum_checkconditions';
            }
            else
            {
                $oth_checkconditionFunction = 'checkconditions';
            }


            if ($_SESSION['survey_'.$this->surveyid][$this->fieldname] == '-oth-')
            {
                $check_ans = CHECKED;
            }
            else
            {
                $check_ans = '';
            }

            $thisfieldname=$this->fieldname.'other';
            if (isset($_SESSION['survey_'.$this->surveyid][$thisfieldname]))
            {
                $dispVal = $_SESSION['survey_'.$this->surveyid][$thisfieldname];
                if ($aQuestionAttributes['other_numbers_only']==1)
                {
                    $dispVal = str_replace('.',$sSeperator,$dispVal);
                }
                $answer_other = ' value="'.htmlspecialchars($dispVal,ENT_QUOTES).'"';
            }
            else
            {
                $answer_other = ' value=""';
            }

            list($htmltbody2, $hiddenfield)=return_array_filter_strings($this, $aQuestionAttributes, $thissurvey, array("code"=>"other"), $thisfieldname, $trbc, $myfname, "li", "answer-item radio-item other-item other");

            if(substr($wrapper['item-start-other'],0,4) == "\t<li")
            {
                $startitem = "\t$htmltbody2\n";
            } else {
                $startitem = $wrapper['item-start-other'];
            }
            $answer .= $startitem;
            $answer .= "\t$hiddenfield\n";
            $answer .= '		<input class="radio" type="radio" value="-oth-" name="'.$this->fieldname.'" id="SOTH'.$this->fieldname.'"'.$check_ans.' onclick="'.$checkconditionFunction.'(this.value, this.name, this.type)" />
            <label for="SOTH'.$this->fieldname.'" class="answertext">'.$othertext.'</label>
            <label for="answer'.$this->fieldname.'othertext">
            <input type="text" class="text '.$kpclass.'" id="answer'.$this->fieldname.'othertext" name="'.$this->fieldname.'other" title="'.$clang->gT('Other').'"'.$answer_other.' onkeyup="if($.trim($(this).val())!=\'\'){ $(\'#SOTH'.$this->fieldname.'\').attr(\'checked\',\'checked\'); }; '.$oth_checkconditionFunction.'(this.value, this.name, this.type);" />
            </label>
            '.$wrapper['item-end'];

            ++$rowcounter;
            if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
            {
                if($colcounter == $wrapper['cols'] - 1)
                {
                    $answer .= $wrapper['col-devide-last'];
                }
                else
                {
                    $answer .= $wrapper['col-devide'];
                }
                $rowcounter = 0;
                ++$colcounter;
            }
        }

        if ($this->mandatory != 'Y' && SHOW_NO_ANSWER == 1)
        {
            if ((!$_SESSION['survey_'.$this->surveyid][$this->fieldname] || $_SESSION['survey_'.$this->surveyid][$this->fieldname] == '') || ($_SESSION['survey_'.$this->surveyid][$this->fieldname] == ' ' ))
            {
                $check_ans = CHECKED; //Check the "no answer" radio button if there is no answer in session.
            }
            else
            {
                $check_ans = '';
            }

            $answer .= $wrapper['item-start-noanswer'].'		<input class="radio" type="radio" name="'.$this->fieldname.'" id="answer'.$this->fieldname.'NANS" value=""'.$check_ans.' onclick="if (document.getElementById(\'answer'.$this->fieldname.'othertext\') != null) document.getElementById(\'answer'.$this->fieldname.'othertext\').value=\'\';'.$checkconditionFunction.'(this.value, this.name, this.type)" />
            <label for="answer'.$this->fieldname.'NANS" class="answertext">'.$clang->gT('No answer').'</label>
            '.$wrapper['item-end'];
            // --> END NEW FEATURE - SAVE

            ++$rowcounter;
            if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
            {
                if($colcounter == $wrapper['cols'] - 1)
                {
                    $answer .= $wrapper['col-devide-last'];
                }
                else
                {
                    $answer .= $wrapper['col-devide'];
                }
                $rowcounter = 0;
                ++$colcounter;
            }

        }
        //END OF ITEMS
        $answer .= $wrapper['whole-end'].'
        <input type="hidden" name="java'.$this->fieldname.'" id="java'.$this->fieldname."\" value=\"".$_SESSION['survey_'.$this->surveyid][$this->fieldname]."\" />\n";

        return $answer;
    }

    public function getDataEntry($idrow, &$fnames, $language)
    {
        $qidattributes=$this->getAttributeValues();
        if (isset($qidattributes['category_separator']) && trim($qidattributes['category_separator'])!='')
        {
            $optCategorySeparator = $qidattributes['category_separator'];
        }
        else
        {
            unset($optCategorySeparator);
        }

        if (substr($this->fieldname, -5) == "other")
        {
            $output .= "\t<input type='text' name='{$this->fieldname}' value='"
            .htmlspecialchars($idrow[$this->fieldname], ENT_QUOTES) . "' />\n";
        }
        else
        {
            $lquery = "SELECT * FROM {{answers}} WHERE qid={$this->id} AND language = '{$language}' ORDER BY sortorder, answer";
            $lresult = dbExecuteAssoc($lquery);
            $output .= "\t<select name='{$this->fieldname}'>\n"
            ."<option value=''";
            if ($idrow[$this->fieldname] == "") {$output .= " selected='selected'";}
            $output .= ">".$clang->gT("Please choose")."..</option>\n";

            if (!isset($optCategorySeparator))
            {
                foreach ($lresult->readAll() as $llrow)
                {
                    $output .= "<option value='{$llrow['code']}'";
                    if ($idrow[$this->fieldname] == $llrow['code']) {$output .= " selected='selected'";}
                    $output .= ">{$llrow['answer']}</option>\n";
                }
            }
            else
            {
                $defaultopts = array();
                $optgroups = array();
                foreach ($lresult->readAll() as $llrow)
                {
                    list ($categorytext, $answertext) = explode($optCategorySeparator,$llrow['answer']);
                    if ($categorytext == '')
                    {
                        $defaultopts[] = array ( 'code' => $llrow['code'], 'answer' => $answertext);
                    }
                    else
                    {
                        $optgroups[$categorytext][] = array ( 'code' => $llrow['code'], 'answer' => $answertext);
                    }
                }

                foreach ($optgroups as $categoryname => $optionlistarray)
                {
                    $output .= "<optgroup class=\"dropdowncategory\" label=\"".$categoryname."\">\n";
                    foreach ($optionlistarray as $optionarray)
                    {
                        $output .= "\t<option value='{$optionarray['code']}'";
                        if ($idrow[$this->fieldname] == $optionarray['code']) {$output .= " selected='selected'";}
                        $output .= ">{$optionarray['answer']}</option>\n";
                    }
                    $output .= "</optgroup>\n";
                }
                foreach ($defaultopts as $optionarray)
                {
                    $output .= "<option value='{$optionarray['code']}'";
                    if ($idrow[$this->fieldname] == $optionarray['code']) {$output .= " selected='selected'";}
                    $output .= ">{$optionarray['answer']}</option>\n";
                }

            }

            $oquery="SELECT other FROM {{questions}} WHERE qid={$this->id} AND {{questions}}.language = '{$language}'";
            $oresult=dbExecuteAssoc($oquery) or safeDie("Couldn't get other for list question<br />".$oquery."<br />");
            foreach($oresult->readAll() as $orow)
            {
                $fother=$orow['other'];
            }
            if ($fother =="Y")
            {
                $output .= "<option value='-oth-'";
                if ($idrow[$this->fieldname] == "-oth-"){$output .= " selected='selected'";}
                $output .= ">".$clang->gT("Other")."</option>\n";
            }
            $output .= "\t</select>\n";
        }
        return $output;
    }

    public function getTitle()
    {
        $clang=Yii::app()->lang;
        $aQuestionAttributes=$this->getAttributeValues();
        if ($aQuestionAttributes['hide_tip']==0)
        {
            return $this->text . "<br />\n<span class=\"questionhelp\">".$clang->gT('Choose one of the following answers').'</span>';
        }

        return $this->text;
    }

    public function getHelp()
    {
        $clang=Yii::app()->lang;
        $aQuestionAttributes=$this->getAttributeValues();
        if ($aQuestionAttributes['hide_tip']==0)
        {
            return $clang->gT('Choose one of the following answers');
        }

        return '';
    }

    public function createFieldmap($type=null)
    {
        $clang = Yii::app()->lang;
        $map = parent::createFieldmap($type);
        if($this->isother=='Y')
        {
            $other = $map[$this->fieldname];
            $other['fieldname'].='other';
            $other['aid']='other';
            $other['subquestion']=$clang->gT("Other");
            $q = clone $this;
            if (isset($this->defaults) && isset($this->defaults['other'])) $q->default=$other['defaultvalue']=$this->defaults['other'];
            else
            {
                unset($other['defaultvalue']);
                unset($q->default);
            }
            $q->fieldname .= 'other';
            $q->aid = 'other';
            $q->sq=$clang->gT("Other");
            $other['q']=$q;
            $map[$other['fieldname']]=$other;
        }
        return $map;
    }

    public function getExtendedAnswer($value, $language)
    {
        if ($value == "-oth-")
        {
            return $language->gT("Other")." [$value]";
        }
        $result = Answers::model()->getAnswerFromCode($this->id,$value,$language->langcode) or die ("Couldn't get answer."); //Checked
        if($result->count())
        {
            $result =array_values($result->readAll());
            return $result[count($result)-1]." [$value]";
        }
        return $value;
    }

    public function getQuotaValue($value)
    {
        return array($this->surveyid.'X'.$this->gid.'X'.$this->id => $value);
    }

    public function setAssessment()
    {
        $this->assessment_value = 0;
        if (isset($_SESSION['survey_'.$this->surveyid][$this->fieldname]))
        {
            $usquery = "SELECT assessment_value FROM {{answers}} where qid=".$this->id." and language='$baselang' and code=".dbQuoteAll($_SESSION['survey_'.$this->surveyid][$this->fieldname]);
            $usresult = dbExecuteAssoc($usquery);          //Checked
            if ($usresult)
            {
                $usrow = $usresult->read();
                $this->assessment_value=(int) $usrow['assessment_value'];
            }
        }
        return true;
    }

    public function getDBField()
    {
        if ($this->aid != 'other' && strpos($this->aid,'comment')===false && strpos($this->aid,'othercomment')===false)
        {
            return "VARCHAR(5)";
        }
        else
        {
            return "text";
        }
    }

    public function getFullAnswer($answerCode, $export, $survey)
    {
        if (mb_substr($this->fieldname, -5, 5) == 'other')
        {
            return $answerCode;
        }
        else
        {
            if ($answerCode == '-oth-')
            {
                return $export->translator->translate('Other', $export->languageCode);
            }
            else
            {
                $answers = $survey->getAnswers($this->id);
                if (array_key_exists($answerCode, $answers))
                {
                    return $answers[$answerCode]['answer'];
                }
                else
                {
                    return null;
                }
            }
        }
    }

    public function getFieldSubHeading($survey, $export, $code)
    {
        if ($this->aid == 'other')
        {
            return ' '.$export->getOtherSubHeading();
        }
        return '';
    }

    public function getSPSSAnswers()
    {
        global $language, $length_vallabel;
        if ($this->aid == 'other' || strpos($this->aid,'comment') !== false) {
            return array();
        } else {
            $query = "SELECT {{answers}}.code, {{answers}}.answer,
            {{questions}}.type FROM {{answers}}, {{questions}} WHERE";

            $query .= " {{answers}}.qid = '".$this->id."' and {{questions}}.language='".$language."' and  {{answers}}.language='".$language."'
            and {{questions}}.qid='".$this->id."' ORDER BY sortorder ASC";
            $result= Yii::app()->db->createCommand($query)->query(); //Checked
            foreach ($result->readAll() as $row)
            {
                $answers[] = array('code'=>$row['code'], 'value'=>mb_substr(stripTagsFull($row["answer"]),0,$length_vallabel));
            }
            return $answers;
        }
    }

    public function getAnswerArray($em)
    {
        $ansArray = (isset($em->qans[$this->id]) ? $em->qans[$this->id] : NULL);
        if (isset($this->other) && $this->other == 'Y')
        {
            if (preg_match('/other$/',$this->fieldname))
            {
                $ansArray = NULL;   // since the other variable doesn't need it
            }
            else
            {
                $_qattr = isset($em->qattr[$this->id]) ? $em->qattr[$this->id] : array();
                if (isset($_qattr['other_replace_text']) && trim($_qattr['other_replace_text']) != '') {
                    $othertext = trim($_qattr['other_replace_text']);
                }
                else {
                    $othertext = $em->gT('Other:');
                }
                $ansArray['0~-oth-'] = '0|' . $othertext;
            }
        }
        return $ansArray;
    }

    public function jsVarNameOn()
    {
        if (preg_match("/other$/",$this->fieldname))
        {
            return 'answer' . $this->fieldname . 'text';
        }
        else
        {
            return 'java' . $this->fieldname;
        }
    }

    public function onlyNumeric()
    {
        $attributes = $this->getAttributeValues();
        return array_key_exists('other_numbers_only', $attributes) && $attributes['other_numbers_only'] == 1 && preg_match('/other$/',$this->fieldname);
    }

    public function generateQuestionInfo()
    {
        return array(
            'q' => $this,
            'qid' => $this->id,
            'qseq' => $this->questioncount,
            'gseq' => $this->groupcount,
            'sgqa' => $this->surveyid . 'X' . $this->gid . 'X' . $this->id,
            'mandatory'=>$this->mandatory,
            'varName' => $this->getVarName(),
            'fieldname' => $this->fieldname,
            'preg' => (isset($this->preg) && trim($this->preg) != '') ? $this->preg : NULL,
            'rootVarName' => $this->title,
            'subqs' => array()
            );
    }

    public function generateSQInfo($ansArray)
    {
        $SQs = array();
        if (!is_null($ansArray))
        {
            foreach (array_keys($ansArray) as $key)
            {
                $parts = explode('~',$key);
                if ($parts[1] == '-oth-') {
                    $parts[1] = 'other';
                }
                $SQs[] = array(
                    'q' => $this,
                    'rowdivid' => $this->surveyid . 'X' . $this->gid . 'X' . $this->id . $parts[1],
                    'varName' => $this->getVarName(),
                    'sqsuffix' => '_' . $parts[1],
                    );
            }
            return $SQs;
        } else {
            return array();
        }
    }

    public function compareField($sgqa, $sq)
    {
        return $sgqa == ($sq['sgqa'] . 'other') && $sgqa == $sq['rowdivid'];
    }

    public function includeRelevanceStatus()
    {
        return false;
    }

    public function includeList()
    {
        return true;
    }

    public function getVarAttributeValueNAOK($name, $default, $gseq, $qseq, $ansArray)
    {
        if (preg_match('/_other\.value/',$name))
        {
            return LimeExpressionManager::GetVarAttribute($name,'code',$default,$gseq,$qseq);
        }
        else
        {
            $scale_id = LimeExpressionManager::GetVarAttribute($name,'scale_id','0',$gseq,$qseq);
            $which_ans = $scale_id . '~' . $code;
            if (is_null($ansArray))
            {
                return $default;
            }
            else
            {
                if (isset($ansArray[$which_ans])) {
                    $answerInfo = explode('|',$ansArray[$which_ans]);
                    $answer = $answerInfo[0];
                }
                else {
                    $answer = $default;
                }
                return $answer;
            }
        }
    }

    public function getVarAttributeShown($name, $default, $gseq, $qseq, $ansArray)
    {
        $code = LimeExpressionManager::GetVarAttribute($name,'code',$default,$gseq,$qseq);
        if (preg_match('/_other$/',$name))
        {
            return $code;
        }
        else
        {
            $scale_id = LimeExpressionManager::GetVarAttribute($name,'scale_id','0',$gseq,$qseq);
            $which_ans = $scale_id . '~' . $code;
            if (is_null($ansArray))
            {
                return $code;
            }
            else
            {
                if (isset($ansArray[$which_ans])) {
                    $answerInfo = explode('|',$ansArray[$which_ans]);
                    array_shift($answerInfo);
                    $answer = join('|',$answerInfo);
                }
                else {
                    $answer = $code;
                }
                return $answer;
            }
        }
    }

    public function getMandatoryTip()
    {
        $clang=Yii::app()->lang;
        if ($this->other == 'Y')
        {
            $attributes = $this->getAttributeValues();
            if (trim($attributes['other_replace_text']) != '') {
                $othertext = trim($qattr['other_replace_text']);
            }
            else {
                $othertext = $clang->gT('Other:');
            }
            return $clang->gT('Please check at least one item.') . "<br />\n".sprintf($clang->gT("If you choose '%s' you must provide a description."), $othertext);
        }
        else
        {
            return $clang->gT('Please check at least one item.');
        }
    }

    public function getAdditionalValParts()
    {
        $othervar = 'answer' . $this->fieldname . 'text';
        $valParts[] = "\n  if(isValidOtherComment" . $this->id . "){\n";
        $valParts[] = "    $('#" . $othervar . "').addClass('em_sq_validation').removeClass('error').addClass('good');\n";
        $valParts[] = "  }\n  else {\n";
        $valParts[] = "    $('#" . $othervar . "').addClass('em_sq_validation').removeClass('good').addClass('error');\n";
        $valParts[] = "  }\n";
        return $valParts;
    }

    public function availableOptions()
    {
        return array('other' => true, 'valid' => false, 'mandatory' => true);
    }

    public function getShownJS()
    {
        return 'if (varName.match(/_other$/)) return value;'
                . 'which_ans = "0~" + value;'
                . 'if (typeof attr.answers[which_ans] === "undefined") return value;'
                . 'answerParts = attr.answers[which_ans].split("|");'
                . 'answerParts.shift();'
                . 'return answerParts.join("|");';
    }

    public function getValueJS()
    {
        return 'if (varName.match(/_other$/)) return value;'
                . 'which_ans = "0~" + value;'
                . 'if (typeof attr.answers[which_ans] === "undefined") return "";'
                . 'answerParts = attr.answers[which_ans].split("|");'
                . 'return answerParts[0];';
    }

    public function availableAttributes($attr = false)
    {
        $attrs=array("alphasort","array_filter","array_filter_exclude","array_filter_style","display_columns","statistics_showgraph","statistics_graphtype","hide_tip","hidden","other_comment_mandatory","other_numbers_only","other_replace_text","page_break","public_statistics","random_order","parent_order","scale_export","random_group","time_limit","time_limit_action","time_limit_disable_next","time_limit_disable_prev","time_limit_countdown_message","time_limit_timer_style","time_limit_message_delay","time_limit_message","time_limit_message_style","time_limit_warning","time_limit_warning_display_time","time_limit_warning_message","time_limit_warning_style","time_limit_warning_2","time_limit_warning_2_display_time","time_limit_warning_2_message","time_limit_warning_2_style");
        return $attr?in_array($attr,$attrs):$attrs;
    }

    public function questionProperties($prop = false)
    {
        $clang=Yii::app()->lang;
        $props=array('description' => $clang->gT("List (Radio)"),'group' => $clang->gT("Single choice questions"),'subquestions' => 0,'class' => 'list-radio','hasdefaultvalues' => 1,'assessable' => 1,'answerscales' => 1);
        return $prop?$props[$prop]:$props;
    }
}
?>