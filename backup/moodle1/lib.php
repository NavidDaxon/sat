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
 * @package    qtype
 * @subpackage satexam
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * satexam question type conversion handler
 */
class moodle1_qtype_satexam_handler extends moodle1_qtype_handler {

    /**
     * @return array
     */
    public function get_question_subpaths() {
        return array(
            'ANSWERS/ANSWER',
            'satexam',
        );
    }

    /**
     * Appends the satexam specific information to the question
     */
    public function process_question(array $data, array $raw) {

        // Convert and write the answers first.
        if (isset($data['answers'])) {
            $this->write_answers($data['answers'], $this->pluginname);
        }

        // Convert and write the satexam.
        if (!isset($data['satexam'])) {
            // This should never happen, but it can do if the 1.9 site contained
            // corrupt data.
            $data['satexam'] = array(array(
                'single'                         => 1,
                'shuffleanswers'                 => 1,
                'correctfeedback'                => '',
                'correctfeedbackformat'          => FORMAT_HTML,
                'partiallycorrectfeedback'       => '',
                'partiallycorrectfeedbackformat' => FORMAT_HTML,
                'incorrectfeedback'              => '',
                'incorrectfeedbackformat'        => FORMAT_HTML,
                'answernumbering'                => 'abc',
                'showstandardinstruction'        => 0
            ));
        }
        $this->write_satexam($data['satexam'], $data['oldquestiontextformat'], $data['id']);
    }

    /**
     * Converts the satexam info and writes it into the question.xml
     *
     * @param array $satexams the grouped structure
     * @param int $oldquestiontextformat - {@see moodle1_question_bank_handler::process_question()}
     * @param int $questionid question id
     */
    protected function write_satexam(array $satexams, $oldquestiontextformat, $questionid) {
        global $CFG;

        // The grouped array is supposed to have just one element - let us use foreach anyway
        // just to be sure we do not loose anything.
        foreach ($satexams as $satexam) {
            // Append an artificial 'id' attribute (is not included in moodle.xml).
            $satexam['id'] = $this->converter->get_nextid();

            // Replay the upgrade step 2009021801.
            $satexam['correctfeedbackformat']               = 0;
            $satexam['partiallycorrectfeedbackformat']      = 0;
            $satexam['incorrectfeedbackformat']             = 0;

            if ($CFG->texteditors !== 'textarea' and $oldquestiontextformat == FORMAT_MOODLE) {
                $satexam['correctfeedback']                 = text_to_html($satexam['correctfeedback'], false, false, true);
                $satexam['correctfeedbackformat']           = FORMAT_HTML;
                $satexam['partiallycorrectfeedback']        = text_to_html($satexam['partiallycorrectfeedback'], false, false, true);
                $satexam['partiallycorrectfeedbackformat']  = FORMAT_HTML;
                $satexam['incorrectfeedback']               = text_to_html($satexam['incorrectfeedback'], false, false, true);
                $satexam['incorrectfeedbackformat']         = FORMAT_HTML;
            } else {
                $satexam['correctfeedbackformat']           = $oldquestiontextformat;
                $satexam['partiallycorrectfeedbackformat']  = $oldquestiontextformat;
                $satexam['incorrectfeedbackformat']         = $oldquestiontextformat;
            }

            $satexam['correctfeedback'] = $this->migrate_files(
                    $satexam['correctfeedback'], 'question', 'correctfeedback', $questionid);
            $satexam['partiallycorrectfeedback'] = $this->migrate_files(
                    $satexam['partiallycorrectfeedback'], 'question', 'partiallycorrectfeedback', $questionid);
            $satexam['incorrectfeedback'] = $this->migrate_files(
                    $satexam['incorrectfeedback'], 'question', 'incorrectfeedback', $questionid);

            $this->write_xml('satexam', $satexam, array('/satexam/id'));
        }
    }
}
