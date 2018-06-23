=== PAY by square pre WooCommerce ===
Contributors: webikon, kravco
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZJTGQMDHTEL76
Tags: bacs, qrcode, slovakia
Requires at least: 4.4
Tested up to: 4.8.2
WC requires at least: 3.0
WC tested up to: 3.2.5
Requires PHP: 5.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tento plugin pridáva QR kód PAY by square pre priamu platbu na bankový účet vo WooCommerce. Na použitie je potrebné mať účet na stránke https://app.bysquare.com, program zadarmo ponúka generovanie 100 QR kódov mesačne.

== Description ==

Plugin pridáva na stránku so sumárom o objednávke obrázok QR kódu, pomocou ktorého môže klient pohodlne zaplatiť cez mobilnú aplikáciu svojej banky. Tento QR kód je taktiež zobrazený v e-maili, ktorý po vytvorení objednávky s priamou platbou na účet štandardne posiela WooCommerce.

== Installation ==

1. Vytvorte si účet na stránke https://app.bysquare.com
1. Plugin nainštalujte cez administráciu WordPress, alebo ručne nahrajte celý adresár `wc-bacs-paybysquare` do adresára `/wp-content/plugins/`
1. Aktivujte plugin cez menu 'Pluginy' v administrácii WordPress
1. Na stránke nastavení priamej platby na účet sa objaví nová oblasť s nastaveniami pre PAY by square
1. Vyplňte v nastaveniach prijímateľa platby a prístupové údaje k https://app.bysquare.com pre generovanie QR kódov

== Frequently Asked Questions ==

= Postupoval(a) som podľa inštrukcií, avšak QR kód sa mi na sumarizačnej stránke objednávky / v sumarizačnom maili nezobrazuje =

Príčin môže byť viacero. V prvom rade odporúčame skontrolovať prístupové údaje, v druhom rade skontrolovať počet zostávajúcich generovaní kódov v administrácii služby https://app.bysquare.com

== Screenshots ==

1. Sumarizačná stránka objednávky s QR kódom.

== Changelog ==

= 1.2 =
* Pridaná podpora medzier v zadanom IBAN čísle a BIC kóde
* Doplnená informácia o verziách WP/WC, s ktorými bol plugin testovaný

= 1.1 =
* Doplnenie informácie do nastavení, že mesačný objem kódov pre účet bol vyčerpaný

= 1.0 =
* Vydanie prvej verzie pluginu

== Upgrade Notice ==

Žiadne upozornenia k aktualizácii na novú verziu.
