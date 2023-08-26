<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the editing form for the SAT Exam question type.
 *
 * @package    qtype
 * @subpackage satexam
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * SAT Exam editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_satexam_edit_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {
        $menu = array(
            get_string('answersingleno', 'qtype_satexam'),
            get_string('answersingleyes', 'qtype_satexam'),
        );
        $mform->addElement('select', 'single',
                get_string('answerhowmany', 'qtype_satexam'), $menu);
        $mform->setDefault('single', $this->get_default_value('single',
            get_config('qtype_satexam', 'answerhowmany')));

        $mform->addElement('advcheckbox', 'shuffleanswers',
                get_string('shuffleanswers', 'qtype_satexam'), null, null, array(0, 1));
        $mform->addHelpButton('shuffleanswers', 'shuffleanswers', 'qtype_satexam');
        $mform->setDefault('shuffleanswers', $this->get_default_value('shuffleanswers',
            get_config('qtype_satexam', 'shuffleanswers')));

        $mform->addElement('select', 'answernumbering',
                get_string('answernumbering', 'qtype_satexam'),
                qtype_satexam::get_numbering_styles());
        $mform->setDefault('answernumbering', $this->get_default_value('answernumbering',
            get_config('qtype_satexam', 'answernumbering')));

        $mform->addElement('selectyesno', 'showstandardinstruction',
            get_string('showstandardinstruction', 'qtype_satexam'), null, null, [0, 1]);
        $mform->addHelpButton('showstandardinstruction', 'showstandardinstruction', 'qtype_satexam');
        $mform->setDefault('showstandardinstruction', $this->get_default_value('showstandardinstruction',
                get_config('qtype_satexam', 'showstandardinstruction')));

        $this->add_per_answer_fields($mform, get_string('choiceno', 'qtype_satexam', '{no}'),
                question_bank::fraction_options_full(), max(5, QUESTION_NUMANS_START));

        $this->add_combined_feedback_fields(true);
        $mform->disabledIf('shownumcorrect', 'single', 'eq', 1);

        $this->add_interactive_settings(true, true);

    }

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
                $qid=1;
                $mform->addElement('html', '<div class="qbox" style="display: flex;flex-wrap: wrap;">');
                for ($i = 0; $i < 200; $i++) {
                    
                    $radioarray=array();
                    $mform->addElement('html', '<div class="qheader" style="
                    width: 210px;
                    border: 1px solid black;
                "
                ">');
                    $mform->addElement('static', 'description',$qid );        
                    $attributes=array('width'=>'20px');            
                    $radioarray[] = $mform->createElement('radio', $qid, '', "1", 1, $attributes);
                    $radioarray[] = $mform->createElement('radio', $qid, '', "2", 2, $attributes);
                    $radioarray[] = $mform->createElement('radio', $qid, '', "3", 3, $attributes);
                    $radioarray[] = $mform->createElement('radio', $qid, '', "4", 4, $attributes);
                    $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
                    $mform->addElement('html', '</div>');
                    $qid=$qid+1;
                }
                $mform->addElement('html', '</div>');
    }

    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        list($repeated, $repeatedoptions) = parent::get_hint_fields($withclearwrong, $withshownumpartscorrect);
        $repeatedoptions['hintclearwrong']['disabledif'] = array('single', 'eq', 1);
        $repeatedoptions['hintshownumcorrect']['disabledif'] = array('single', 'eq', 1);
        return array($repeated, $repeatedoptions);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question, true);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (!empty($question->options)) {
            $question->single = $question->options->single;
            $question->shuffleanswers = $question->options->shuffleanswers;
            $question->answernumbering = $question->options->answernumbering;
            $question->showstandardinstruction = $question->options->showstandardinstruction;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
    
        $answers = $data['answer'];
       
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;

        foreach ($answers as $key => $answer) {
            // Check no of choices.
            $trimmedanswer = trim($answer['text']);
            $fraction = (float) $data['fraction'][$key];
            if ($trimmedanswer === '' && empty($fraction)) {
                continue;
            }
            if ($trimmedanswer === '') {
                $errors['fraction['.$key.']'] = get_string('errgradesetanswerblank', 'qtype_satexam');
            }

            $answercount++;

            // Check grades.
            if ($data['fraction'][$key] > 0) {
                $totalfraction += $data['fraction'][$key];
            }
            if ($data['fraction'][$key] > $maxfraction) {
                $maxfraction = $data['fraction'][$key];
            }
        }

        // if ($answercount == 0) {
        //     $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_satexam', 2);
        //     $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_satexam', 2);
        // } else if ($answercount == 1) {
        //     $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_satexam', 2);

        // }

        // // Perform sanity checks on fractional grades.
        // if ($data['single']) {
        //     if ($maxfraction != 1) {
        //         $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_satexam',
        //                 $maxfraction * 100);
        //     }
        // } else {
        //     $totalfraction = round($totalfraction, 2);
        //     if ($totalfraction != 1) {
        //         $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_satexam',
        //                 $totalfraction * 100);
        //     }
        // }
        // echo json_encode($errors);
        // die(1);
        return $errors;
    }

    public function qtype() {
        return 'satexam';
    }
}
