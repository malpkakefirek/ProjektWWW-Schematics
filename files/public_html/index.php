<?php
/*
 * Już to wiesz, ale przypomnijmy, że każdy blok kodu PHP otwieramy tagiem <?php, a zamykamy tagiem ?>.
 * Jeżeli dany plik zawiera tylko i wyłącznie kod PHP to nie musimy używać tagu zamykającego (jest to dobra praktyka).
 * Funkcja session_start() aktywuje obsługę sesji. Dzięki temu możesz rozpoznawać użytkowników ponieważ w ich
 * przeglądarce zapisywane jest ciasteczko zawierające ID sesji. Wejdź do narzędzi deweloperskich (F12), a następnie
 * w zależności od przeglądarki:
 * - Firefox: zakładka "Dane"/"Data" i wybierz z listy "Ciasteczka"/"Cookies"
 * - Chrome: zakładka "Application" i wybierz z listy "Storage" -> "Cookies"
 * Zobaczysz, że Twoja strona zapisała w przeglądarce ciasteczko o nazwie PHPSESSID. Za każdym razem kiedy przeglądarka
 * ponownie odpytuje stronę, podaje w nagłówkach HTTP to ciasteczko. PHP zapisuje dane sesji w folderze tymczasowym
 * na dysku. Do tych danych mamy dostęp za pomocą tablicy superglobalnej $_SESSION.
 * Do sesji wrócimy kiedy będziemy implementować rejestrację i logowanie.
 *
 * Przy okazji, wiesz już, jak tworzyć bloki komentarzy w kodzie PHP :)
 */
session_start();

/*
 * Funkcje "ini_set" oraz "error_reporting" posłużyły do tymczasowej zmiany konfiguracji PHP, tak, aby wprost na stronie
 * pokazywało wszystkie błędy wykonania. Dzięki temu nie musisz szukać błędów w logach httpd. Taka konfiguracja
 * nie powinna być stosowana w środowiskach produkcyjnych! Wówczas do wyszukiwania błędów korzysta się z logów serwera!
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
 * Zauważ, że podobnie jak w przypadku pliku test.php, możemy wywołać dowolne pliki PHP wpisując ich nazwę w pasku
 * przeglądarki. Zazwyczaj jest to niepożądane ponieważ ich przeznaczeniem jest bycie wczytanym przez inne, nadrzędne
 * pliki PHP i nie powinny być wykonywane samodzielnie. Z tego powodu definiujemy w projekcie stałą o przykładowej
 * nazwie IN_INDEX. W podplikach możemy później sprawdzić czy stała jest zdefiniowana i jeśli nie jest to oznaczać
 * będzie, że taki plik został wczytany bezpośrednio w przeglądarce - możemy wówczas zareagować zakończeniem pracy
 * skryptu.
 */
define("IN_INDEX", true);

/*
 * Manager pakietów Composer zachowuje zainstalowane biblioteki w katalogu vendor. Nie są one od razu załadowane
 * w projekcie i bezpośrednie odnoszenie się do klas w nich zawartych spowoduje błąd. Composer oferuje skrypt
 * vendor/autoload.php, który rejestruje w PHP tak zwany autoloader. Dzięki temu w momencie odnoszenia się do nazw
 * jednej z klas pochodzącej z zainstalowanych bibliotek, interpreter PHP będzie wiedział gdzie ona jest na dysku
 * i załaduje ją automatycznie.
 *
 * Instrukcje require/include formalnie nie są funkcją tylko wyrażeniem języka PHP. Za ich pomocą możemy włączyć
 * kod html/php z innego pliku tak jakby był on integralną częścią pliku, który go włącza. Jeżeli w innym pliku
 * zostały utworzone jakieś zmienne/obiekty/itp... to staną się one widoczne w pliku, który go włącza do siebie.
 * Znajdź w dokumentacji PHP różnicę między require i include.
 */
require __DIR__ . '/vendor/autoload.php';

/*
 * Includujemy plik z konfiguracją danych logowania do bazy danych. Zauważ, że w pliku config.inc.php konfigurację
 * zapisaliśmy w stałej o nazwie CONFIG, do której przypisaliśmy tablicę asocjacyjną (odpowiednik pythonowego
 * słownika).
 */
include("config.inc.php");

/*
 * Dołączamy plik helpers.inc.php, który będzie zawierać zdefiniowane przez nas funkcje, klasy i instrukcje przydatne
 * do inicjalizacji projektu.
 *
 * Znajduje się tam funkcja get_domain(), która nie przyjmuje żadnych argumentów i zwraca adres bieżącej domeny,
 * który później wyświetlamy w tytule strony i navbarze.
 *
 * W tym pliku znajdują się jeszcze dwie klasy wykorzystujące wzorzec programowania o nazwie singleton (poczytaj o nim
 * w Internecie).
 *
 * W przyszłości możesz używać pliku helpers.inc.php do definiowania własnych funkcji, które ułatwią Ci realizacje
 * zadań.
 */
include("helpers.inc.php");

/*
 * Logowanie
 */
// if (isset($_POST['email']) && isset($_POST['password'])) {

//     $stmt = DB::getInstance()->prepare("SELECT * FROM users WHERE email = :email");
//     $stmt->execute([':email' => $_POST['email']]);
//     $user = $stmt->fetch(PDO::FETCH_ASSOC);

//     if ($user) {
//         if (password_verify($_POST['password'], $user['password'])) {
//             $_SESSION['id'] = $user['id'];
//             $_SESSION['email'] = $user['email'];
//             TwigHelper::getInstance()->addGlobal('_session', $_SESSION);
//             TwigHelper::addMsg('User has been logged in.', 'success');
//         } else {
//             TwigHelper::addMsg('Incorrect login credentials.', 'error');
//         }
//     } else {
//         TwigHelper::addMsg('Incorrect login credentials.', 'error');
//     }

// }

/*
 * Wylogowywanie
 */
if (isset($_GET['page']) && $_GET['page'] == 'logout') {
    unset($_SESSION['id']);
    unset($_SESSION['email']);
    unset($_SESSION['username']);
    if (isset($_GET['redirect'])) {
        $redirect = urldecode($_GET['redirect']);
    } else {
        $redirect = '/';
    }
    header('Location: ' . $redirect);
    exit();
}

/*
 * Wybierając daną pozycję w górnym menu, użytkownikowi powinniśmy pokazać (zaincludować) treść wybranego pliku
 * (np. main.php, guestbook.php). Zobacz co się stanie kiedy w menu wybierzesz inną podstronę, np. Guestbook.
 * Interesujące jest to, co widać w pasku adresu przeglądarki, a znajduje się tam ciąg index.php?page=guestbook.
 *
 * Już to wiesz, ale przypomnijmy, że wybrane zmienne możemy przekazywać od użytkownika do strony za pomocą parametrów
 * metody GET. Każde "zwykłe" wczytanie strony jest zapytaniem HTTP typu GET, możesz to sprawdzić otwierając konsolę,
 * a w niej narzędzie sieci. Zapytania GET mogą nieść dane od użytkownika w adresie strony. Do adresu dopisuje się
 * zmienne oddzielając je znakiem &, wyjątek stanowi tylko pierwsza zmienna, przed którą stawia się znak zapytania.
 *
 * Zauważ, że w pliku templates/base.html, do adresu podstrony Guestbook w navbarze celowo w zmiennej przekazaliśmy
 * o jaką stronę nam chodzi. Tak więc wczytujemy index.php, ale mówimy mu w zmiennej "page", że chodzi nam
 * o "guestbook".
 * Wiemy już, że użytkownik przekaże nam w zmiennej "page" (lub nie) jaką podstronę chce otrzymać (a dokładnie plik
 * o podanej przez niego nazwie i rozszerzeniu .php, np. guestbook.php). Nie byłoby to zbyt bezpieczne, gdyby użytkownik
 * mógł nam przekazać dowolną nazwę strony, a my wczytalibyśmy ją bez sprawdzenia. Zastanów się co by się stało gdyby
 * użytkownik przekazał index.php?page=index. Stworzyłoby to nieskończone wczytanie rekurencyjne, które skutecznie
 * "posadziłoby" stronę (możesz to później przetestować zmieniając kod) :)))
 *
 * Z tego powodu ręcznie definiujemy tablicę dozwolonych nazw ($allowed_pages). Następnie musimy sprawdzić
 * czy użytkownik przekazał nam zmienną "page" i czy jej wartość znajduje się w tablicy. Dostęp do zmiennych GET można
 * uzyskać za pomocą tablicy superglobalnej $_GET.
 *
 * W warunku "if" pojawiły się funkcje "isset", "in_array" oraz "file_exists".
 *
 * Za pomocą funkcji "isset" sprawdzamy, czy zmienna $_GET['page'] (a dokładnie element tablicy) istnieje. Zawsze
 * sprawdzaj czy zmienna istnieje jeżeli nie masz pewności, że została ona zadeklarowana w innym pliku lub jeśli
 * pochodzi z zewnątrz (od użytkownika).
 *
 * Za pomocą funkcji "in_array" sprawdzamy czy w tablicy istnieje dany element, a dokładnie czy podana przez użytkownika
 * strona znajduje się na liście stron dozwolonych,
 *
 * Następnie sprawdzamy czy taki plik z rozszerzeniem php istnieje za pomocą "file_exists" i jeśli tak to go wczytujemy.
 *
 * Domyślnie wczytujemy main.php w przypadku kiedy użytkownik nie przekaże nazwy podstrony.
 *
 * Jeśli użytkownik przekazał nieprawidłową nazwę podstrony to wyświetlamy komunikat błędu bez wczytywania żadnej
 * podstrony.
 *
 * Opis wszystkich tablic superglobalnych w PHP znajdziesz na stronie:
 * https://www.php.net/manual/en/language.variables.superglobals.php
 */
$allowed_pages = ['main', 'guestbook', 'register', 'articles', 'schematics', 'redstone', 'resourcepacks', 'mods', 'login', 'test1'];

if (isset($_GET['page'])
    && $_GET['page']
    && in_array($_GET['page'], $allowed_pages)
    && file_exists($_GET['page'] . '.php')
) {
    // Użytkownik podał prawidłową nazwę podstrony.
    include($_GET['page'] . '.php');
} elseif (!isset($_GET['page'])) {
    // Użytkownik nie przekazał żadnej nazwy podstrony.
    include('main.php');
} else {
    // Użytkownik podał nieprawidłową nazwę podstrony. Następuje dodanie komunikatu błędu, który wyświetli się w ramce.
    // Więcej informacji na temat wyświetlania komunikatów znajdziesz w pliku helpers.inc.php.
    TwigHelper::addMsg('Page "' . $_GET['page'] . '" not found.', 'error');
    // Renderujemy tylko główny szablon bez podstrony.
    print TwigHelper::getInstance()->render('base.html');
}
