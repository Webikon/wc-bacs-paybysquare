=== PAY by square pre WooCommerce ===
Contributors: webikon, kravco, johnnypea, martinkrcho
Tags: bacs, qrcode, slovakia
Requires at least: 4.4
Tested up to: 6.9.1
Requires PHP: 7.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tento plugin pridáva QR kód PAY by square pre priamu platbu na bankový účet vo WooCommerce. Na použitie je potrebné mať účet na stránke https://app.bysquare.com, program zadarmo ponúka generovanie 100 QR kódov mesačne.

== Description ==

Platobná brána Pay by Square uľahčuje platby prevodom na účet prostredníctvom QR kódov.

Plugin vygeneruje QR kód, ktorý sa vloží na stránku na stránku so sumárom objednávky a zárove sa QR kód odošle prostredníctvom emailu s informáciami o platbe. Zákazník následne naskenuje QR kód pomocou mobilnej aplikácie svojej banky, v ktorej sa mu predvyplnia všetky potrebné údaje k platbe.

Plugin Pay by Square podporuje slovenský formát QR kódu pre platbu a aj český formát – QR platba.

== Installation ==

1. Pripravte si Váš eshop na platforme WooCommerce.
2. Zaregistrujte sa na stránke app.bysquare.com.
3. Nainštalujte a aktivujte si plugin Pay by Square (Pluginy -> Pridať nový -> Nahrať plugin)
4. Nastavte si parametre platby na účet (WooCommerce -> Platby -> Offline platby):
	a) Povoľte priamy prevod na bankový účet a prejdite do nastavení (Spravovať)
	b) Vložte údaje minimálne jedného bankového účtu – údaje IBAN a BIC sú povinné
5. Nastavte si parametre generovania QR kódu (WooCommerce -> Nastavenia -> Integrácia -> PAY by square)
	a) Príjemca platby – meno, ktoré ste pri registrácii uviedli ako Meno kontaktnej osoby
	b) Používateľské meno a heslo – údaje, pod ktorými sa prihlasujete na app.bysquare.com – používateľské meno je v tvare emailu
	c) Ostatné položky v nastaveniach môžete upraviť podľa Vašich preferencií
5. Vykonajte testovaciu objednávku a skontrolujte si zobrazenie QR kódu po odoslaní objednávky a v emaile, ktorý príde zákazníkovi.

== Frequently Asked Questions ==

= Postupoval(a) som podľa inštrukcií, avšak QR kód sa mi na sumarizačnej stránke objednávky / v sumarizačnom maili nezobrazuje =

Ak Vám plugin QR kódy negeneruje, skontrolujte si správnosť prihlasovacích údajov a údajov bankového účtu. Taktiež si skontrolujte počet zostávajúcich generovaní kódov v administrácii služby app.bysquare.com.

Ak využívate na odosielanie emailov nejaký SMTP plugin, overte si, že používa pre odosielanie knižnicu PHPmailer (php_mail), v opačnom prípade s ním plugin Pay by Square nemusí správne fungovať. Podporovaný je napríklad plugin WP mail SMTP.

== Screenshots ==

1. Sumarizačná stránka objednávky s QR kódom.
2. Pay by Square plugin - Nastavenia
3. Pay by Square plugin - ďakovná stránka
4. Pay by Square plugin - Email

== Changelog ==

= 3.0.1 =
* Pridané logovanie presnej chyby v prípade, že nie je možné vytvoriť obrázok s QR kódom
* Opravené zobrazenie odkazu na nastavenia PAY by square v nastaveniach platieb

= 3.0.0 =
* Presunutie nastavení na samostatnú stránku (Integrácia > PAY by square)

= 2.0.0 =
* Deklarovanie podpory pre WooCommerce High-Performace Order Storage (HPOS)
* Zmena logovania na štandardné logovanie, ktoré je súčasťou WooCommerce
* Použitie nástrojov na automatické odhaľovanie chýb v kóde a oprava nájdených chýb

= 1.4.2 =
* Pridanie hlásenia chyby, ak neuspeje vloženie súboru s QR kódom do mailu

= 1.4.1 =
* Upozornenie na nepovolené znaky v nastavení „príjemca platby“ pre český štandard QR Platba.
* Preferovanie účtu podľa meny objednávky, pre eurách preferuje IBANy začínajúce na SK, pri českých korunách na CZ

= 1.4 =
* Pridanie nastavení a podpory pre generovanie českého štandardu QR Platba.

= 1.3.3 =
* Zaktualizované info o kompatibilite s novšími verziami WordPress a WooCommerce.

= 1.3.2 =
* Upravené inštrukcie pre inštaláciu a aktualizované info o kompatibilite s novšími verziami WordPress a WooCommerce.

= 1.3.1 =
* Zaktualizované info o kompatibilite s novšími verziami WordPress a WooCommerce.

= 1.3 =
* Opravená kompatibilita tvorenia e-mailov s niektorými pluginmi

= 1.2 =
* Pridaná podpora medzier v zadanom IBAN čísle a BIC kóde
* Doplnená informácia o verziách WP/WC, s ktorými bol plugin testovaný

= 1.1 =
* Doplnenie informácie do nastavení, že mesačný objem kódov pre účet bol vyčerpaný

= 1.0 =
* Vydanie prvej verzie pluginu

== Upgrade Notice ==

Žiadne upozornenia k aktualizácii na novú verziu.
