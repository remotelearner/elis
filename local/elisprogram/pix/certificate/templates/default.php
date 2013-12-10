<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 *
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from certificate.php in /local/elisprogram/certificate.php
}

//add the header
$pdf->Ln(1.25);
$pdf->SetFont($font, '', $largefontsize);
$pdf->Cell(0, 1, get_string('certificate_title', 'local_elisprogram'), $borders, 1, 'C');

$pdf->Ln(0.25);

$pdf->SetFont($font, '', $smallfontsize);
$pdf->Cell(0, 0.5, get_string('certificate_certify', 'local_elisprogram'), $borders, 1, 'C');

//person's name
$pdf->SetFont($font, '', $largefontsize);
$pdf->Cell(0, 1, $person_fullname, $borders, 1, 'C');

$pdf->SetFont($font, '', $smallfontsize);
$pdf->Cell(0, 0.5, get_string('certificate_has_completed', 'local_elisprogram'), $borders, 1, 'C');

//entity's name
$pdf->SetFont($font, '', $largefontsize);
$pdf->Cell(0, 1, $entity_name, $borders, 1, 'C');

//time issued
$pdf->SetFont($font, '', $smallfontsize);
$pdf->Cell(0, 0.5, get_string('certificate_date', 'local_elisprogram', $date_string), $borders, 1, 'C');

// Expiry date (if applicable)
if (!empty($expirydate)) {
    $pdf->SetFont($font, '', 11);
    $pdf->Cell(0, 0.5, get_string('certificate_expires', 'local_elisprogram'), $borders, 1, 'C');
    $pdf->Cell(0, 0.05, $expirydate, $borders, 1, 'C');
}
