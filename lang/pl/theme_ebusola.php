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
 * Polish language strings.
 *
 * @package   theme_ebusola
 * @copyright 2026 Szkoła Żeglarstwa Busola
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'eBusola';
$string['choosereadme'] = 'eBusola to motyw dedykowany dla Szkoły Żeglarstwa Busola.';
$string['configtitle'] = 'eBusola';
$string['generalsettings'] = 'Ustawienia ogólne';
$string['advancedsettings'] = 'Zaawansowane';
$string['brandcolor'] = 'Kolor marki';
$string['brandcolor_desc'] = 'Główny kolor akcentujący motywu.';
$string['secondarymenucolor'] = 'Kolor dodatkowy menu';
$string['secondarymenucolor_desc'] = 'Kolor paska podrzędnego menu.';
$string['examurl'] = 'Link do informacji o egzaminach';
$string['examurl_desc'] = 'Własny adres URL dla przycisku w sekcji Informacje o egzaminach. Jeśli pozostanie pusty, motyw automatycznie wyszuka odpowiednią stronę w serwisie.';
$string['termsurl'] = 'Link do Regulaminu';
$string['termsurl_desc'] = 'Adres URL strony z Regulaminem serwisu.';
$string['privacyurl'] = 'Link do Polityki prywatności';
$string['privacyurl_desc'] = 'Adres URL strony z Polityką prywatności.';
$string['rodourl'] = 'Link do RODO';
$string['rodourl_desc'] = 'Adres URL strony z klauzulą informacyjną RODO.';

$string['contactus'] = 'Kontakt';
$string['followus'] = 'Obserwuj nas';
$string['website'] = 'Strona internetowa';
$string['websitedesc'] = 'Główna strona internetowa firmy';
$string['mail'] = 'Napisz do nas';
$string['mobile'] = 'Telefon';
$string['facebook'] = 'Facebook URL';
$string['instagram'] = 'Instagram URL';
$string['tiktok'] = 'TikTok URL';
$string['themedevelopedby'] = 'Motyw przygotowany dla';

// Napisy interfejsu Huba eBusola.
$string['hero_greeting'] = 'Ahoj, {$a}!';
$string['hero_last_learned'] = 'Ostatnio uczyłeś się kursu <strong>{$a}</strong>. Wracasz dokładnie tam, gdzie skończyłeś.';
$string['hero_welcome_default'] = 'Witaj w platformie e-learningowej Szkoły Żeglarstwa Busola. Wybierz kurs i zacznij naukę.';
$string['hero_continue'] = 'KONTYNUUJ';
$string['hero_resume'] = 'Wróć do nauki';
$string['mycourses_heading'] = 'Moje kursy';
$string['mycourses_all'] = 'Wszystkie kursy';
$string['mycourses_not_enrolled'] = 'Nie jesteś jeszcze zapisany na żaden kurs.';
$string['mycourses_browse'] = 'Przeglądaj ofertę kursów';
$string['course_status_completed'] = 'Kurs ukończony (100%)';
$string['course_status_progress'] = '{$a}% ukończono';
$string['course_status_not_started'] = 'Jeszcze nie zacząłeś';
$string['course_action_continue'] = 'Kontynuuj kurs';
$string['course_action_start'] = 'Rozpocznij kurs';
$string['course_status_done'] = 'Ukończony';
$string['exam_title'] = 'Egzaminy dla dorosłych';
$string['exam_desc'] = 'Wszystko, co trzeba wiedzieć przed egzaminem: zasady, terminy i wymagane dokumenty.';
$string['exam_btn'] = 'Informacje o egzaminach';
$string['discover_heading'] = 'Odkryj kolejne kursy';
$string['edit_zone_title'] = 'Zarządzanie stroną główną';
$string['edit_zone_desc'] = 'Sekcja widoczna dla administratorów w trybie edycji (dodawanie i konfiguracja modułów).';
$string['edit_zone_badge'] = 'Tryb edycji aktywny';
$string['colourmode:auto'] = 'Systemowy';
$string['colourmode:dark'] = 'Ciemny';
$string['colourmode:light'] = 'Jasny';
$string['colourmodeselected'] = 'Tryb kolorów: {$a}';
$string['course_cat_sail'] = 'Kursy żeglarskie';
$string['course_cat_motor'] = 'Kursy motorowodne';
$string['course_cat_extra'] = 'Kursy dodatkowe';
$string['course_cat_default'] = 'Kurs';
$string['all_category_courses_enrolled'] = 'Masz już wszystkie kursy z tej kategorii.';

// Napisy widoku strony głównej dla gości / niezalogowanych użytkowników.
$string['guest_hero_title'] = 'Ahoj, żeglarzu!';
$string['guest_hero_subtitle'] = 'Kursy żeglarskie i motorowodne online, w Twoim tempie. Zaloguj się, aby wrócić do nauki i testów.';
$string['guest_hero_btn_login'] = 'Zaloguj się';
$string['guest_hero_btn_browse'] = 'Przeglądaj kursy';
$string['guest_howitworks_title'] = 'JAK TO DZIAŁA';
$string['guest_howitworks_step1_title'] = 'Ucz się działami';
$string['guest_howitworks_step1_desc'] = 'Materiały, filmy i prezentacje PDF dla każdego działu.';
$string['guest_howitworks_step2_title'] = 'Rozwiązuj testy';
$string['guest_howitworks_step2_desc'] = 'Za każdym razem 15 losowych pytań z bazy. Powtarzaj do skutku.';
$string['guest_howitworks_step3_title'] = 'Zalicz dział';
$string['guest_howitworks_step3_desc'] = 'Postęp widzisz jako liczbę zaliczonych działów.';
$string['guest_courses_title'] = 'Dostępne kursy';
$string['guest_course_requires_login'] = 'Wymaga logowania';
$string['guest_course_view'] = 'Zobacz kurs';
$string['guest_nav_aria'] = 'Nawigacja mobilna';
$string['guest_nav_home'] = 'Start';
$string['guest_nav_courses'] = 'Kursy';
$string['guest_nav_exams'] = 'Egzaminy';
$string['guest_nav_login'] = 'Logowanie';

// Napisy widoku Kokpit (/my/).
$string['kokpit_title'] = 'Kokpit';
$string['kokpit_subtitle'] = 'Twoje wyniki i najbliższe wydarzenia.';
$string['kokpit_continue_tag'] = 'KONTYNUUJ';
$string['kokpit_btn_retry'] = 'Rozwiąż ponownie';
$string['kokpit_btn_continue'] = 'Kontynuuj naukę';
$string['kokpit_results_title'] = 'Ostatnie wyniki';
$string['kokpit_results_threshold'] = 'Próg zaliczenia: 75%';
$string['kokpit_results_threshold_mobile'] = 'Próg: 75%';
$string['kokpit_results_passed'] = 'Zaliczony';
$string['kokpit_results_failed'] = 'Poniżej progu';
$string['kokpit_results_empty'] = 'Brak rozwiązanych testów. Rozpocznij naukę w wybranym kursie!';
$string['kokpit_calendar_title'] = 'Kalendarz';
$string['kokpit_calendar_full'] = 'Pełny kalendarz';
$string['kokpit_calendar_prev_month'] = 'Poprzedni miesiąc';
$string['kokpit_calendar_next_month'] = 'Następny miesiąc';
$string['kokpit_event_section_title'] = 'Najbliższe wydarzenie';
$string['kokpit_event_title'] = 'NAJBLIŻSZE WYDARZENIE';
$string['kokpit_event_info_link'] = 'Informacje o egzaminach';
$string['kokpit_edit_zone_title'] = 'Zarządzanie kokpitem';
$string['kokpit_edit_zone_desc'] = 'Sekcja widoczna dla administratorów w trybie edycji (dodawanie i konfiguracja bloków kokpitu).';
