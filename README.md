# eBusola - Motyw dla Moodle (Szkoła Żeglarstwa Busola)

Dedykowany motyw dla platformy e-learningowej [kursybusola.pl](https://kursybusola.pl) Szkoły Żeglarstwa Busola, bazujący na motywie **Moove** (kompatybilny z **Moodle 5.3+**).

---

## 🎨 Kluczowe cechy i modyfikacje
- **Komponent Moodle:** `theme_ebusola` (folder: `theme/ebusola`).
- **Identyfikacja wizualna Busoli:**
  - Główny kolor marki: `#15195D` (granatowy)
  - Kolor akcentowy menu: `#FF751F` (pomarańczowy)
  - Krój pisma: `Open Sans`
  - Preset: `plain.scss`
  - Domyślnie skonfigurowane linki (Facebook, Instagram, TikTok, strona WWW, e-mail wsparcia).
- **Custom SCSS (`scss/busola/_busola.scss`):**
  - Nowoczesny, wyśrodkowany panel logowania na pełnym tle.
  - Sticky footer (stopka zawsze na dole okna).
  - Płynny flex-układ kolumny stopki z linkami społecznościowymi.
  - Zaokrąglone przełączniki szuflad bocznych (drawers).
  - Poprawka responsywności (RWD) dla kafelków kursów na urządzeniach mobilnych.

---

## 🚀 Bezpieczne wdrożenie na serwerze (HestiaCP)

Klonujemy repozytorium do **osobnego podkatalogu** wewnątrz `theme/`:

```bash
# Wejdź do katalogu motywów Moodle (obok działającego moove):
cd /home/busola/web/kursybusola.pl/public_html/public/theme

# Sklonuj forka jako ebusola:
git clone https://github.com/Qu4X/Busola_Moove.git ebusola

# Uruchom rejestrację wtyczki i wyczyść pamięć podręczną:
cd /home/busola/web/kursybusola.pl/public_html/public
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

### 🧪 Testowanie bez wpływu na kursantów:
W przeglądarce dopisz do adresu:
`https://kursybusola.pl/?theme=ebusola`

Dopiero po zweryfikowaniu wyglądu możesz przełączyć domyślny motyw w:
*Administracja serwisu -> Wygląd -> Motywy -> Wybór motywów* -> **eBusola**.

---

## 🔄 Aktualizacje z oficjalnego Moove (przed / po sezonie)

Gdy autor Moove wyda nową wersję motywu:

```bash
# 1. Pobierz zmiany z oficjalnego repozytorium Moove:
git fetch https://github.com/willianmano/moodle-theme_moove.git main

# 2. Scal zmiany:
git merge FETCH_HEAD

# 3. Uruchom skrypt synchronizujący identyfikatory z Moove na eBusola:
python scripts/sync_upstream.py

# 4. Sprawdź, zatwierdź i wypchnij:
git commit -am "chore: sync with upstream Moove"
git push origin main
```
Twój plik `scss/busola/_busola.scss` pozostaje w pełni nienaruszony!
