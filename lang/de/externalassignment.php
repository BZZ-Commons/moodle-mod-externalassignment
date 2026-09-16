<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * German translation.
 *
 * @package     mod_externalassignment
 * @category    string
 * @copyright   2024 Marcel Suter <marcel.suter@bzz.ch>
 * @copyright   2024 Kevin Maurizi <kevin.maurizi@bzz.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addinstance'] = 'Instanz hinzufügen';
$string['allowsubmissionsfromdate'] = 'Abgabe erlauben ab';
$string['allowsubmissionsfromdate_help'] = 'Wenn aktiviert, können Studierende erst ab diesem Datum eine Abgabe einreichen. Wenn deaktiviert, können Studierende sofort mit der Abgabe beginnen.';
$string['allstatuses'] = 'Alle';
$string['alwaysshowdescription'] = 'Beschreibung immer anzeigen';
$string['alwaysshowdescription_help'] = 'Wenn deaktiviert, wird die obige Beschreibung der Aufgabe den Studierenden erst ab dem Datum "Abgabe erlauben ab" angezeigt.';
$string['alwaysshowlink'] = 'Link immer anzeigen';
$string['alwaysshowlink_help'] = 'Wenn deaktiviert, wird der obige Link zur Aufgabe den Studierenden erst ab dem Datum "Abgabe erlauben ab" angezeigt.';
$string['assignmentisdue'] = 'Die Aufgabe ist fällig';
$string['assignmentname'] = 'Name der Aufgabe';
$string['availability'] = 'Verfügbarkeit';

$string['changeuser'] = 'Nutzer wechseln';
$string['completiongradesgroup'] = 'Gruppe für Abschlussbedingungen';
$string['completiongradesgroup_help'] = 'Regeln für den automatischen Abschluss, basierend auf den erreichten Bewertungen';
$string['configintro'] = 'Die hier festgelegten Werte werden vom Plugin "External Assignment" verwendet';
$string['cutoffdate'] = 'Abgabeschluss';
$string['cutoffdate_help'] = 'Wenn festgelegt, werden nach diesem Datum ohne Fristverlängerung keine Abgaben mehr akzeptiert. Wenn nicht festgelegt, werden Abgaben immer akzeptiert.';
$string['cutoffdatefromdatevalidation'] = 'Der Abgabeschluss darf nicht vor dem Datum "Abgabe erlaubt ab" liegen.';
$string['cutoffdatevalidation'] = 'Der Abgabeschluss darf nicht vor dem Abgabetermin liegen.';

$string['description'] = 'Beschreibung';
$string['done'] = 'erledigt';
$string['duedate'] = 'Abgabetermin';
$string['duedate_help'] = 'Dies ist der Termin, an dem die Aufgabe fällig ist. Abgaben sind auch nach diesem Datum noch möglich. Legen Sie einen Abgabeschluss fest, um Abgaben nach einem bestimmten Datum zu verhindern.';
$string['duedateaftersubmissionvalidation'] = 'Der Abgabetermin muss nach dem Datum "Abgabe erlauben ab" liegen.';
$string['duedatevalidation'] = 'Der Abgabetermin darf nicht vor dem Datum "Abgabe erlauben ab" liegen.';
$string['duplicatenamevalidation'] = 'In diesem Kurs existiert bereits eine Aufgabe mit diesem externen Namen. Bitte wählen Sie einen anderen Namen.';

$string['extensiongranted'] = ' / Fristverlängerung gewährt: ';
$string['external'] = 'Extern';
$string['externalassignment:addinstance'] = 'Neue externe Aufgabe hinzufügen';
$string['externalassignment:grade'] = 'Externe Aufgabe bewerten';
$string['externalassignment:grantextension'] = 'Fristverlängerung für externe Aufgabe gewähren';
$string['externalassignment:manage'] = 'Externe Aufgabe verwalten';
$string['externalassignment:managegrades'] = 'Bewertungen für externe Aufgabe verwalten';
$string['externalassignment:override'] = 'Externe Aufgabe überschreiben';
$string['externalassignment:reviewgrades'] = 'Bewertungen für externe Aufgabe überprüfen';
$string['externalassignment:submit'] = 'Externe Aufgabe abgeben';
$string['externalassignment:view'] = 'Externe Aufgabe ansehen';
$string['externalassignment:viewgrades'] = 'Bewertungen für externe Aufgabe ansehen';
$string['externalfeedback'] = 'Rückmeldung vom externen System';
$string['externalgrade'] = 'Externe Bewertung';
$string['externalgrademax'] = 'Externe Bewertung max.';
$string['externalgrademax_help'] = 'Maximal erreichbare Bewertung der externen Aufgabe';
$string['externalgrademaxnegativevalidation'] = 'Die maximale externe Bewertung darf nicht negativ sein.';
$string['externalgrading'] = 'Bewertung durch externes System';
$string['externallink'] = 'Link zur Aufgabe';
$string['externallink_help'] = 'Der Link zur Aufgabe im externen System';
$string['externallinkinvalidvalidation'] = 'Der Link zur Aufgabe muss eine gültige URL sein, z. B. https://www.example.com/assignment.';
$string['externalname'] = 'Externe Aufgabe';
$string['externalname_help'] = 'Der Name der Aufgabe im externen System';
$string['externalusername'] = 'Externer Benutzername';
$string['externalusername_desc'] = 'Das Benutzerprofilfeld, das den externen Benutzernamen enthält';

$string['feedback'] = 'Rückmeldung';
$string['finalgrade'] = 'Endbewertung';
$string['findduplicates'] = 'Aufgaben mit doppeltem externem Namen suchen';

$string['grade'] = 'Bewertung';
$string['gradecomponent'] = 'Bewertungskomponente';
$string['graded'] = 'Bewertet';
$string['grading'] = 'Bewertung';
$string['gradingoverview'] = 'Bewertungsübersicht';
$string['gradingstatus'] = 'Bewertungsstatus';
$string['grantextension'] = 'Fristverlängerung gewähren';

$string['isdue'] = 'ist fällig';

$string['mandatory'] = 'Erforderlich';
$string['manual'] = 'Manuell';
$string['manualfeedback'] = 'Manuelle Rückmeldung';
$string['manualgrademax'] = 'Manuelle Bewertung max.';
$string['manualgrademax_help'] = 'Maximal erreichbare Bewertung der manuellen Bewertung';
$string['manualgrademaxnegativevalidation'] = 'Die maximale manuelle Bewertung darf nicht negativ sein.';
$string['manualgrading'] = 'Manuelle Bewertung';
$string['modulename'] = 'Externe Aufgabe';
$string['modulename_help'] = 'Mit der Aktivität "Externe Aufgabe" können Sie Ihren Studierenden eine Aufgabe in einem externen System stellen (z. B. GitHub Classroom).\nSie enthält einen Webservice, um die Bewertung der Studierenden aus der externen Beurteilung zu aktualisieren';
$string['modulenameplural'] = 'Externe Aufgaben';

$string['needspassinggrade'] = 'Eine genügende Bewertung erreichen';
$string['needspassinggradedesc'] = 'Studierende müssen eine genügende Bewertung erreichen, um die Aufgabe abzuschliessen';
$string['nextuser'] = 'Nächster Nutzer';
$string['notsubmitted'] = 'nicht abgegeben';

$string['open'] = 'offen';
$string['overdue'] = 'überfällig';
$string['override'] = 'Überschreiben';

$string['passed'] = 'bestanden';
$string['passinggrade'] = 'Für eine genügende Bewertung benötigte Punkte';
$string['passingpercentage'] = 'Prozentsatz für eine genügende Bewertung';
$string['passingpercentage_help'] = 'Welcher Prozentsatz der maximalen Bewertung (extern + manuell) erreicht werden muss, um zu genügen';
$string['pending'] = 'ausstehend';
$string['percentage'] = 'Prozentsatz';
$string['pluginadministration'] = 'Externe Aufgabe';
$string['pluginname'] = 'Externe Aufgabe';
$string['previoususer'] = 'Vorheriger Nutzer';
$string['privacy:export:externalassignment:grades'] = 'Bewertungen für externe Aufgabe';
$string['privacy:metadata:allowsubmissionsfromdate'] = 'Das überschriebene Datum "Abgabe erlauben ab"';
$string['privacy:metadata:cutoffdate'] = 'Der überschriebene Abgabeschluss';
$string['privacy:metadata:duedate'] = 'Der überschriebene Abgabetermin';
$string['privacy:metadata:externalassignment_grades'] = 'Informationen zu Bewertungen und Rückmeldungen für externe Aufgaben.';
$string['privacy:metadata:externalassignment_overrides'] = 'Informationen zu individuellen Terminüberschreibungen für Nutzer bei externen Aufgaben.';
$string['privacy:metadata:externalfeedback'] = 'Die Rückmeldung aus dem externen System';
$string['privacy:metadata:externalgrade'] = 'Die Bewertung aus dem externen System';
$string['privacy:metadata:externallink'] = 'Der Link zur Aufgabe im externen System';
$string['privacy:metadata:grader'] = 'Die Nutzer-ID der bewertenden Person';
$string['privacy:metadata:manualfeedback'] = 'Die manuelle Rückmeldung der Lehrperson';
$string['privacy:metadata:manualgrade'] = 'Die manuelle Bewertung der Lehrperson';
$string['privacy:metadata:userid'] = 'Die Nutzer-ID der/des Studierenden';

$string['scoremaximum'] = 'Maximale Punktzahl';
$string['scorereached'] = 'Erreichte Punktzahl';
$string['seefeedback'] = 'Rückmeldung ansehen';
$string['selectedusers'] = 'Ausgewählte Nutzer';
$string['statusfilter'] = 'Nach Status filtern';
$string['studentlink'] = 'Ihre Aufgabe';
$string['submissions'] = 'Abgaben';
$string['submissionsdue'] = 'Fällig:';
$string['submissionsopen'] = 'Öffnet:';
$string['submissionsopened'] = 'Geöffnet:';
$string['submissionstatus'] = 'Abgabestatus';
$string['submitandnext'] = 'Speichern und nächsten anzeigen';

$string['taskduplicatenames'] = 'Auf doppelte externe Aufgabennamen für alle Studierenden prüfen';
$string['timeremaining'] = 'Verbleibende Zeit';
$string['timeremainingcolon'] = 'Verbleibende Zeit: {$a}';
$string['totalgrade'] = 'Gesamtpunktzahl';

$string['view'] = 'Ansehen';
