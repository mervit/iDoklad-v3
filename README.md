[![Open Source Love](https://badges.frapsoft.com/os/mit/mit.svg?v=102)](https://github.com/ellerbrock/open-source-badge/)
# iDoklad v3
PHP třída pro zasílání požadavků na iDoklad api v3.

[Dokumentace iDoklad api v2](https://api.idoklad.cz/Help/v2)

[Dokumentace iDoklad api v3](https://api.idoklad.cz/Help/v3/cs/)

Děkuji @malcanek za vytvoření v2, knihovnu jsem upgradoval na verzi 3 a dávám jí tímto k dispozici ostatním zájemcům

## Změny v kodu proti verzi v2 od /iDoklad-v2
 - Podstattná změna je v odpovědích, kdy bylo u listů TotalItems a TotalPages přesunuto pod parametr data. Položky listů tedy již nelze načíst metodou getData ale nově vzniklou metodou getItems. U existujících aplikací je tedy nutné při přechodu z verze 2 na verzi 3 zasáhnout do všech callu které vrací list výsledků.
 - Přepracované jsou filtry, které nyní umožňují seskupování a zanořování podmínek a kombinací logických operátorů `or` a `and`
 - Requesty GET nově podporují parametr select pro omezení dat ve výsledcích
 - Requesty GET nově podporují také parametr include který umožňuje zahrnout do odpovědi i data z připojených entit


## Vložení knihovny do projektu
Knihovnu vložíme do projektu naincludováním souboru src/iDoklad.php, nebo si knihovnu přidáme pomocí composeru. Následně se na knihovnu odkážeme pomocí use.
```php
composer require mervit/idoklad-v3
```
Zadáme naše client ID, client secret a v případě, že chceme použít OAuth2 autentifikaci i redirect URI. Nakonec si zavoláme objekt iDokladu, který zajišťuje veškerou komunikaci.
```php
include_once 'src/iDoklad.php';
            
use mervit\iDoklad\iDoklad;
use mervit\iDoklad\auth\iDokladCredentials;
use mervit\iDoklad\iDokladException;

$clientId = 'Your client ID';
$clientSecret = 'Your client secret';
$redirectUri = 'Your redirect URI for OAuth2';

$iDoklad = new iDoklad($clientId, $clientSecret, $redirectUri);
```

## Autorizace pomocí OAuth2 - Authorization code flow
Autorizace pomocí OAuth probíhá v několika krocích. Jako client ID a client secret používáme údaje získané z developer portálu.

Nejdříve nabídneme uživateli URL adresu, kde zadá své přihlašovací údaje. Tu získáme pomocí nasledující metody:
```php
echo '<a href="'.$iDoklad->getAuthenticationUrl().'">Odkaz</a>';
```

Po zadání přihlašovacích údajů je uživatel přesměrován na zadanou redirect URI i s kódem, pomocí kterého získáme jeho credentials údaje.
Kód zpracujeme následujícím způsobem:
```php
$iDoklad->requestCredentials($_GET['code']);
```

Nyní jsou v instanci objektu založeny credentials a můžeme odesílat dotazy na iDoklad api. Credentials můžeme získat 2 způsoby.
Získání credentials přímo z objektu:
```php
$credentials = $iDoklad->getCredentials();
echo $credentials->toJson();
```

### Zpracování credentials pomocí credentials callbacku:
Callback funguje tak, že knihovna zavolá callback funkci vždy, když jsou změněny credentials. To se hodí, jelikož automaticky probíhá refresh tokenu po jeho vypršení.
```php
$iDoklad->setCredentialsCallback(function($credentials){
    file_put_contents('credentials.json', $credentials->toJson());
});
```

### Nahrání credentials do iDoklad objektu
Založení objektu s již existujícími credentials
```php
$iDoklad = new iDoklad($clientId, $clientSecret, $redirectUri, $credentials);
```

Vložení credentials do již existujícího objektu
```php
$iDoklad->setCredentials($credentials);
```

Poté co objekt obsahuje credentials, lze provádět dotazy do iDoklad api.

## Autorizace pomocí OAuth2 - Client credentials flow
Tato metoda je jednodušší. Credentials získáme na základě client id a client secret, které získáme z nastavení účtu uživatele.
Po založení objektu pouze zavoláme:
```php
$iDoklad->authCCF();
```

Jako u OAuth2 - Authorization code flow i zde funguje credentials callback.

## Odesílání požadavků na iDoklad api
Pro odeslání požadavku na api slouží iDokladRequest objekt. Ten lze v nejjednodušší podobě založit pouze s jedním parametrem, který určuje akci dle dokumentace, a poté rovnou odeslat na api.
```php
$request = new iDokladRequest('IssuedInvoices');
$response = $iDoklad->sendRequest($request);
```

## Získání dat z api
Pokud požadavek proběhne úspěšně, jsou zpět vrácena data v podobě iDokladResponse objektu. Nejdříve zkontrolujeme, zda požadavek proběhl v pořádku (návratová hodnota by měla být 200):
```php
$response->getCode();
```

Poté můžeme získat samotná data v poli:
```php
$response->getData();
```

## Odchytávání chyb
Třída vyhazuje vyjímky typu iDokladException.

## Vytvoření nové faktury
```php
$request->addMethodType('POST');
$data = array(
    'PurchaserId' => 3739927,
    'IssuedInvoiceItems' => [array(
        'Name' => 'Testovaci polozka',
        'UnitPrice' => 100,
        'Amount' => 1
    )]
);
$request->addPostParameters($data);
```

Případně můžeme nově zadat method type pomocí fce, což by nyní vypadalo následovně:
```php
$data = array(
    'PurchaserId' => 3739927,
    'IssuedInvoiceItems' => [array(
        'Name' => 'Testovaci polozka',
        'UnitPrice' => 100,
        'Amount' => 1
    )]
);
$request->post()->addPostParameters($data);
```

## Použití filtru a třídění
Pro použití filtru použijeme třídu iDokladFilter. Parametry můžeme zadat hned při založení třídy, první parametr je jméno pole, které chceme filtrovat, druhý parametr je operátor, poslední parametr je hodnota.
```php
$filter = new iDokladFilter('DocumentNumber', '==', '20170013');
$request->addFilter($filter);
```

Vztahy mezi jednotlivými filtry můžeme definovat pomocí metody setFilterType.
```php
$request->setFilterType('and');
```

Filtrů můžeme přidat několik a pomocí filterGroup třídy je můžeme seskupit.  
```php
$filterGroup = new \mervit\iDoklad\request\iDokladFilterGroup('or');
$filter2 = new \mervit\iDoklad\request\iDokladFilter('Id', '!eq', '123');
$filterGroup->addFilter($filter2);
$filter3 = new \mervit\iDoklad\request\iDokladFilter('Id', 'eq', '456');
$filterGroup->addFilter($filter3);
$request->addFilter($filterGroup);
```

Pro použití třídění použijeme třídu iDokladSort. Opět můžeme hned přidávat parametry, kdy první parametr je jméno pole a druhý parametr je dobrovolný a lze zadat, zda řadit vzestupně (asc) či sestupně (desc).
```php
$sort = new iDokladSort('DocumentNumber', 'desc');
$request->addSort($sort);
```

## Stránkování a počet vrácených položek
```php
$request->setPage(2);
$request->setPageSize(5);
```

## Omezení výsledku
Pomocí metody addSelect můžeme omezit data, které API vrací a výrazně tím dobu zpracování požadavku
```php
$request->addSelect('Id');
$request->addSelect('CurrencyId');
```

Zanořená data odělujeme tečkou
```php
$request->addSelect('DeliveryAddress.Name');
```

Můžeme přidat i více proměných odělené čárkou
```php
$request->addSelect('Items.Id,Items.Name');
```

## Načtení připojených entit
Pomocí metody addInclude můžeme naopak rozšířit vrácené data o detaily připojených entit. Díky této funkci již nemusíme načítat detail připojené entity samostatným callem.
```php
$request->addInclude('Currency');
```

Zanořená data odělujeme tečkou
```php
$request->addInclude('Items.PriceListItem.Currency');
```

Můžeme přidat i více proměných odělené čárkou
```php
$request->addInclude('Currency,Items.PriceListItem.Currency');
```

## Vyhazování exception při návratových kódech vyšších nebo rovno 400
Pokud chceme zapnout vyhazování exception při http návratových kódech vyšších rovno 400, stačí nám zavolat fci 
```php
$iDoklad->httpExceptionsOn()
```

## Upload přílohy
Pokud chceme uploadovat přílohu, stačí nám použít metodu addFile nad request objektem.
```php
$request = new \mervit\iDoklad\request\iDokladRequest('Attachments/{documentId}/{documentType}');
$request->addFile(new CURLFile(path_to_your_file));
$response = $iDoklad->sendRequest($request);
```

## Jiné úpravy
Pokud potřebujeme použít metody POST, PUT, PATCH, DELETE, použijeme k tomu metodu addMethodType nad objektem iDokladRequest.

## Příklady
Příklady použití lze vidět v souborech acf.php a ccf.php. acf.php obsahuje příklad použití authorization code flow, ccf obsahuje příklad na client credentials flow, stačí doplnit vlastní client ID, client secret a redirect URI.

### Příklad vytvoření faktury
```php
// Generate company name
        $companyName = $order->getFirstname() . ' ' . $order->getLastname();
        if($order->getCompanyName()){
            $companyName = $order->getCompanyName();
        }

        // Try to find existing company in address book
        $filter = new iDokladFilter('CompanyName', '==', $companyName);
        $contactRequest = new iDokladRequest('Contacts');
        $contactRequest->addFilter($filter);
        $contactResponse = $this->sendRequest($contactRequest);
        if($contactResponse->getTotalItems() > 0){
            $contactId = $contactResponse->getItems()[0]["Id"];
        }

        // Create or update company in address book
        $contactRequest = new iDokladRequest('Contacts');
        $contactRequestPostParameters = [];
        $contactRequestPostParameters['CountryId'] = 1;
        $contactRequestPostParameters['Email'] = $order->getEmail();
        $contactRequestPostParameters['Mobile'] = $order->getPhone();
        $contactRequestPostParameters['Firstname'] = $order->getFirstname();
        $contactRequestPostParameters['Surname'] = $order->getLastname();
        $contactRequestPostParameters['CompanyName'] = $companyName;
        if($order->getCin()) {
            $contactRequestPostParameters['IdentificationNumber'] = $order->getCin();
        }
        if($order->getVat()){
            $contactRequestPostParameters['VatIdentificationNumber'] = $order->getVat();
        }
        if($order->getAddress()){
            $contactRequestPostParameters['Street'] = $order->getAddress();
        }
        if(isset($contactId)){
            $contactRequestPostParameters['Id'] = $contactId;
            $contactRequest->addMethodType('PATCH');
        } else {
            $contactRequest->addMethodType('POST');
        }
        $contactRequest->addPostParameters($contactRequestPostParameters);

        $contactResponse = $this->sendRequest($contactRequest);
        $contact = $contactResponse->getData();

        // Get default numeric sequence
        $numericSequenceRequest = new iDokladRequest('NumericSequences');
        $numericSequenceRequest->addFilter(new iDokladFilter('IsDefault', '==', 'true'));
        $numericSequenceRequest->addFilter(new iDokladFilter('DocumentType', '==', '0')); // 0 = IssuedInvoices
        $numericSequenceResponse = $this->sendRequest($numericSequenceRequest);
        $numericSequences = $numericSequenceResponse->getItems();
        $defaultNumericSequenceId = $numericSequences[0]['Id'];
        $lastDocumentSerialNumber = $numericSequences[0]['LastNumber'];

        // Get default payment option
        $paymentOptionRequest = new iDokladRequest('PaymentOptions');
        $paymentOptionResponse = $this->sendRequest($paymentOptionRequest);
        $paymentOptions = $paymentOptionResponse->getItems();
        $paymentOptionId = null;
        foreach($paymentOptions as $po){
            if($po['IsDefault'] == 'true'){
                $paymentOptionId = $po['Id'];
                break;
            }
        }

        // Create Issued Invoice
        $dateOfIssue = new \DateTime();
        $dateOfMaturity = clone $dateOfIssue;
        $dateOfMaturity->modify('+14 days');
        $invoicePostParameters = [];
        $invoicePostParameters['PartnerId'] = $contact['Id'];
        $invoicePostParameters['CurrencyId'] = 1;
        $invoicePostParameters['Description'] = 'Nákup v eshopu';
        $invoicePostParameters['DateOfIssue'] = $dateOfIssue->format('Y-m-d');
        $invoicePostParameters['DateOfMaturity'] = $dateOfMaturity->format('Y-m-d');
        $invoicePostParameters['DateOfTaxing'] = $dateOfIssue->format('Y-m-d');
        $invoicePostParameters['DocumentSerialNumber'] = (string)((int) $lastDocumentSerialNumber + 1);
        $invoicePostParameters['IsEet'] = false;
        $invoicePostParameters['IsIncomeTax'] = true;
        $invoicePostParameters['NumericSequenceId'] = $defaultNumericSequenceId;
        $invoicePostParameters['PaymentOptionId'] = $paymentOptionId;
        $invoicePostParameters['Items'] = [];

        foreach ($order->getItems() as $item) {
            $invoiceItem = [];
            $invoiceItem['Amount'] = $item->getQuantity();
            $invoiceItem['PriceType'] = 0; // Cena s daní
            $invoiceItem['VatRateType'] = 1; // Základní sazba DPH
            $invoiceItem['UnitPrice'] = $item->getPrice();
            $invoiceItem['Name'] = $item->getName();
            $invoiceItem['DiscountPercentage'] = 0;
            $invoiceItem['IsTaxMovement'] = false;
            $invoicePostParameters['Items'][] = $invoiceItem;
        }

        $invoiceRequest = new iDokladRequest('IssuedInvoices');
        $invoiceRequest->addMethodType('POST');
        $invoiceRequest->addPostParameters($invoicePostParameters);
        return $this->sendRequest($invoiceRequest);
```
