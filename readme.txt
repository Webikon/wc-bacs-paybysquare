=== PAY by square pre WooCommerce ===
Contributors: webikon, kravco, johnnypea, martinkrcho, savione
Tags: pay by square, qr platba, qrcode, bacs, woocommerce
Requires at least: 6.0
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 3.1.0
WC requires at least: 8.0
WC tested up to: 10.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Pridá QR kód k platbe prevodom vo WooCommerce. Do objednávky aj do emailu. Podporuje PAY by square (SK) aj QR Platba (CZ).

== Description ==

Plugin PAY by square uľahčuje platby bankovým prevodom prostredníctvom QR kódov.

Po odoslaní objednávky sa zákazníkovi zobrazí QR kód na ďakovnej stránke a zároveň sa odošle v emaile s potvrdením objednávky. Zákazník následne naskenuje QR kód mobilnou aplikáciou svojej banky, v ktorej sa mu predvyplnia všetky potrebné údaje k platbe — IBAN, suma, variabilný symbol a meno príjemcu.

Podporované formáty:

* PAY by square — slovenský štandard
* QR platba — český štandard
* Automatický výber — podľa meny objednávky (EUR = slovenský, CZK = český)

Kde sa QR kód zobrazí:

* Na ďakovnej stránke po odoslaní objednávky
* V emaile s potvrdením objednávky

Na použitie je potrebné mať účet na stránke app.bysquare.com. Program zadarmo ponúka generovanie 100 QR kódov mesačne.

== Installation ==

1. Pripravte si Váš eshop na platforme WooCommerce.
2. Zaregistrujte sa na stránke app.bysquare.com.
3. Nainštalujte a aktivujte si plugin Pay by Square (Pluginy > Pridať nový > Nahrať plugin).
4. Nastavte si parametre platby na účet (WooCommerce > Platby > Priamy prevod na bankový účet):
    a) Povoľte priamy prevod na bankový účet a prejdite do nastavení (Spravovať).
    b) Vložte údaje minimálne jedného bankového účtu — údaje IBAN a BIC sú povinné.
5. Nastavte si parametre generovania QR kódu (WooCommerce > Nastavenia > Integrácia > PAY by square):
    a) Príjemca platby — meno osoby alebo organizácie prijímajúcej peniaze.
    b) Používateľské meno a heslo — údaje, pod ktorými sa prihlasujete na app.bysquare.com (používateľské meno je v tvare emailu).
    c) Ostatné položky v nastaveniach môžete upraviť podľa Vašich preferencií.
6. Vykonajte testovaciu objednávku a skontrolujte si zobrazenie QR kódu po odoslaní objednávky a v emaile.

== Frequently Asked Questions ==

= QR kód sa mi na ďakovnej stránke / v emaile nezobrazuje =

Skontrolujte si:

* Správnosť prihlasovacích údajov (používateľské meno a heslo na app.bysquare.com).
* Údaje bankového účtu — IBAN aj BIC musia byť vyplnené.
* Počet zostávajúcich generovaní QR kódov v administrácii služby app.bysquare.com.
* V nastaveniach pluginu musí byť vyplnené pole „Príjemca platby".

= Plugin nefunguje s mojím SMTP pluginom =

Plugin používa na vloženie QR kódu do emailu knižnicu PHPMailer. Ak využívate SMTP plugin, overte si, že používa PHPMailer. Podporovaný je napríklad plugin WP Mail SMTP.

= Podporuje plugin blokový checkout? =

Áno, QR kód sa zobrazí na ďakovnej stránke aj pri použití blokového checkoutu (WooCommerce 8.9+).

== Screenshots ==

1. Ďakovná stránka objednávky s QR kódom
2. Nastavenia pluginu (Integrácia > PAY by square)
3. Ďakovná stránka - plný náhľad
4. QR kód v potvrdzujúcom emaile

== Changelog ==

= 3.1.0 =
* Pridaný Live Preview na stránke pluginu na WordPress.org (WordPress Playground blueprint)
* Pridané upozornenie v nastaveniach pluginu ak nie je vyplnené pole „Príjemca platby"
* Pridané upozornenie v zozname pluginov ak nie je vyplnené pole „Príjemca platby"
* Pridaná validácia formátu IBAN a BIC
* Aktualizované info o kompatibilite s WordPress 6.9.4 a WooCommerce 10.6

= 3.0.1 =
* Pridané logovanie presnej chyby v prípade, že nie je možné vytvoriť obrázok s QR kódom
* Opravené zobrazenie odkazu na nastavenia PAY by square v nastaveniach platieb

= 3.0.0 =
* Presunutie nastavení na samostatnú stránku (Integrácia > PAY by square)

For older versions see changelog.txt.
