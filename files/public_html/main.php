<?php

if (!defined('IN_INDEX')) { exit("This file can only be included in index.php."); }

/*
* Przykład 1: dodanie danych do tabeli za pomocą metody POST
*
* W tym przykładzie odbieramy pola "name" i "surname" z formularza, przeprowadzamy ich walidację, a następnie dodajemy
* nowy wpis do księgi gości. Na końcu pobieramy wszystkie dane z bazy danych i wyświetlamy je w formie tabeli.
*
* Krótkie przypomnienie na temat metod HTTP:
* Aby skorzystać z przekazania danych metodą POST i odebrać dane z formularza, należy ustawić atrybut "method"
* w elemencie <form> na wartość "POST". Dane nie są wówczas przekazywane w adresie URL tak jak w metodzie GET,
* ale przesyłane są w ciele zapytania, na poziomie protokołu HTTP (jest to niewidoczne dla użytkownika). Metoda
* POST według domyślnych konfiguracji serwerów www pozwala na przesyłanie większej liczby znaków niż metoda GET,
* oraz danych niestandardowych, takich jak na przykład pliki. Z tego powodu formularze HTML idealnie współgrają
* z tą metoda. Należy jednak zauważyć, że atrybut "method" możemy również ustawić na "GET", wówczas pola z formularza
* zostaną doklejone do adresu URL podanego w atrybucie "action".
*
* Dostęp do danych przekazanych metodą POST jest możliwy za pomocą tablicy superglobalnej $_POST.
*/

if (isset($_POST['name']) && isset($_POST['surname'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];

    // Podane imię i nazwisko muszą mieć od 2 do 50 znaków.
    if (mb_strlen($name) >= 2 && mb_strlen($name) <= 50 && mb_strlen($surname) >= 2 && mb_strlen($surname) <= 50) {

        /*
         * W celu wykonania zapytania do bazy danych posłużymy się wcześniej utworzonym obiektem PDO, który trzymamy
         * w singletonie DB (plik helpers.inc.php). Metoda "prepare" pozwala "przygotować" dowolne zapytanie i zwraca
         * obiekt klasy PDOStatement, który reprezentuje to zapytanie. Więcej o tej klasie możesz przeczytać na stronie:
         * https://www.php.net/manual/en/class.pdostatement.php
         * Przygotowane zapytanie możemy wykonać za pomocą metody "execute". Metoda ta nie przyjmuje argumentów
         * lub przyjmuje jako argument tablicę wszystkich danych, które mają zostać wstawione do zapytania.
         * W tym przypadku będą to :name oraz :surname.
         *
         * Proces przypisania danych do zmiennych w metodzie "execute" nazywamy bindowaniem. Możnaby więc zapytać
         * po co to robimy, jeżeli za pomocą prostej konkatenacji ciągów tekstowych (za pomocą kropki) moglibyśmy
         * wstawić zmienne $name oraz $surname prosto do ciągu zapytania w metodzie "prepare". Jeżeli dane pochodzą
         * z zewnątrz, na przykład od użytkownika strony, zachodzi ryzyko, że będą tak spreparowane, że ich wstawienie
         * do zapytania zmieni jego treść na tyle, że spowoduje ono zupełnie inne skutki w bazie danych niż planował
         * programista. Atak tego typu nazywa się SQL injection. Z tego powodu biblioteka PDO oferuje nam mechanizm
         * bindowania, w którym danych nie wstawiamy wprost w zapytaniu, a zamiast tego wymyślamy własne nazwy
         * tymczasowe poprzedzone dwukropkiem, a następnie do tych nazw przypisujemy dane w metodzie "execute".
         * Takie nazwy możemy bezpiecznie używać w zapytaniu. PDO samo zadba o to, aby oczyścić dane z niebezpiecznych
         * znaków i wstawić je w wybranych miejscach do zapytania.
         *
         * Bindowanie występuje również w innych językach programowania. Nie jest to konstrukt pochodzący z PHP.
         * Warto zrozumieć o co w nim chodzi.
         *
         * Słowo "name" jest tzw. keywordem - ma specjalne znaczenie w bazie MySQL. Z tego powodu używamy backticków `,
         * aby przekazać w zapytaniu, że chodzi nam o kolumnę o takiej nazwie.
         */
        $stmt = DB::getInstance()->prepare("INSERT INTO test (`name`, surname) VALUES (:name, :surname)");
        $stmt->execute([
            ':name' => $name,
            ':surname' => $surname
        ]);

        /*
         * Dodanie komunikatu, który wyświetli się w ramce nad treścią tej podstrony. Więcej informacji na temat
         * wyświetlania komunikatów znajdziesz w pliku helpers.inc.php.
         */
        TwigHelper::addMsg('Row has been added.', 'success');
    } else {
        TwigHelper::addMsg('Incorrect data.', 'error');
    }
}

/*
 * Pobieramy wszystkie wiersze z tabeli "test". Po wykonaniu zapytania możemy skorzystać z metody "fetchAll",
 * aby pobrać wszystkie wyniki z bazy danych na raz, w formie tablicy, a następnie przypisać je do zmiennej $example1.
 * Stała "PDO::FETCH_ASSOC", podana w argumencie, powoduje, że każdy z pobranych wierszy będzie tablicą asocjacyjną,
 * gdzie kluczem jest nazwa kolumny z bazy danych. Mamy więc tutaj do czynienia z tablicą dwuwymiarową (tablica tablic),
 * gdzie pierwszy poziom to lista wierszy, a drugi to kolumny danego wiersza.
 */
$stmt = DB::getInstance()->prepare("SELECT id, `name`, surname FROM test");
$stmt->execute();
$example1 = $stmt->fetchAll(PDO::FETCH_ASSOC);



/*
 * Przykład 2: pobranie z bazy danych tylko osób o wskazanym imieniu i powiększenie wszystkich liter nazwiska
 *
 * Na początku $example2 jest pustą tablicą. Jeżeli otrzymaliśmy z formularza pole "search" to wykonujemy zapytanie,
 * które szuka rekordów z podanym imieniem. Na końcu, za pomocą prostej pętli while i metody "fetch" pobieramy każdy
 * rekord oddzielnie. Każdy wiersz trafia do tablicy $row. Specjalna stała PDO::FETCH_ASSOC podana jako argument
 * powoduje, że każdy wiersz w pętli zostanie zwrócony jako tablica asocjacyjna, gdzie klucze będą nazwami kolumn.
 *
 * Przed dodaniem wiersza do tablicy wynikowej $example2, korzystamy z funkcji "strtoupper", aby zamienić w nazwisku
 * wszystkie małe litery na wielkie.
*/

$example2 = [];

if (isset($_POST['search'])) {
    $stmt = DB::getInstance()->prepare("SELECT id, `name`, surname FROM test WHERE `name` = :search");
    $stmt->execute([':search' => $_POST['search']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['surname'] = strtoupper($row['surname']);
        $example2[] = $row;
    }
}



/*
 * Przykład 3: dane z bazy psują (kolorują) tabelę za pomocą niechcianego kodu JS
 *
 * W tym przypadku wykonujemy zapytanie, które pobierze osobę o imieniu Jim. Mamy pewność, że jest to bezpieczna nazwa,
 * statyczny ciąg, więc nie musimy używać bindowania tylko wprost wstawiamy to imię do zapytania.
 *
 * Dlaczego tabela jest pomarańczowa jeśli w jej kodzie nigdzie takie stylowanie nie występuje?
 * Zajrzyj do bazy danych oraz w kod źródłowy w przeglądarce. Okazuje się, że do nazwiska Beam wstrzyknięto złośliwy kod
 * JavaScript, który koloruje tabelę. Jest to typowy przykład ataku XSS.
 *
 * Zobacz na poprzedni (drugi) przykład w źródle strony w przeglądarce - wszystkie znaki specjalne zostały zmienione
 * na tak zwane encje HTML, nieinterpretowane przez przeglądarkę:
 * <td>Beam&lt;script&gt;$(&quot;#my-table3&quot;).css(&quot;background-color&quot;, &quot;orange&quot;);&lt;/script&gt;</td>
 *
 * Natomiast w tym przykładzie, w źródle strony w przeglądarce, widać czysty kod JS w bloku script:
 * <td>Beam<script>$("#my-table3").css("background-color", "orange");</script></td>
 *
 * Otwórz plik main.html i porównaj jak wyświetlana jest kolumna nazwiska w drugim i trzecim przykładzie:
 * <td>{{ row.surname }}</td>
 * <td>{{ row.surname|raw }}</td>
 *
 * Różnica polega na użyciu filtra "raw" pochodzącego z biblioteki Twig.
 * Dane trafiające do bazy mogą zawierać złośliwy kod. Po stronie programisty leży zadbanie o to, aby po ich pobraniu,
 * wyświetliły się w przeglądarce w taki sposób, aby nie zagrażać użytkownikom strony. Twig domyślnie ma włączone
 * escapowanie - tj. dane wstawione w szablonie w podwójnych nawiasach dziubkowych przechodzą przez filtr, który
 * zamienia znaki specjalne na tak zwane encje HTML, czyli ciągi znaków (kody), które przeglądarka zamienia sobie
 * na określony symbol. W ten sposób złośliwy kod nie zostanie wykonany, a jedynie wypisany na stronie.
 * Czasem jednak nie chcemy, aby filtrowanie zadziałało. Wówczas możemy wyłączyć filtrowanie globalnie w konfiguracji
 * Twiga, co nie jest zalecane, lub zrobić to lokalnie tylko w wybranym miejscu za pomocą filtra "raw".
 *
 * Śmiało edytuj kod w pliku main.html i usuń filtr |raw. Teraz blok script powinien zostać wypisany w tabeli,
 * a nie zinterpretowany przez przeglądarkę.
 *
 * Opis wszystkich dostępnych filtrów Twiga znajdziesz na stronie:
 * https://twig.symfony.com/doc/3.x/filters/index.html
*/

$stmt = DB::getInstance()->prepare("SELECT id, `name`, surname FROM test WHERE `name` = 'Jim'");
$stmt->execute();
$example3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lokalizacja pliku przechowującego liczbę wyświetleń
$counter_file = "counter.txt";

// Sprawdzanie, czy plik istnieje
if(!file_exists($counter_file)) {
    // Jeżeli plik nie istnieje, tworzy go z początkową wartością 0
    file_put_contents($counter_file, "0");
}

// Pobieranie aktualnej liczby wyświetleń
$page_views = file_get_contents($counter_file);

// Zwiększanie licznika o 1
$page_views += 1;

// Zapisywanie nowej liczby wyświetleń
file_put_contents($counter_file, $page_views);

if (isset($_GET['status']) && $_GET['status'] == 'fail' && isset($_GET['reason']) && $_GET['reason'] == 'timeout') {
    TwigHelper::addMsg("Your connection timed out!", 'error');
}

/*
 * Wyrenderowanie podstrony z przekazaniem do niej tablic wynikowych z trzech przykładów.
 */
print TwigHelper::getInstance()->render('main.html', [
    'example1' => $example1,
    'example2' => $example2,
    'example3' => $example3,
    'page_views' => $page_views,
]);
?>

