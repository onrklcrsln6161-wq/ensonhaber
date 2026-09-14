# Bilsenneoldu — PHP Haber Portalı

PHP ve MySQL tabanlı haber sitesi. Yönetici, editör ve yazar panelleri; haber onay akışı; revizyon geçmişi; manuel RSS/Atom aktarımı ve isteğe bağlı yapay zekâ editör yardımcısı içerir.

## Özellikler

- Mobil uyumlu ana sayfa, slider, kategori sayfaları, arama ve yorumlar.
- Yönetici: kullanıcılar, site ayarları, sosyal hesaplar, kurumsal sayfalar ve piyasa ayarları.
- Editör: haber inceleme/yayın, kategoriler, slider, yorumlar ve medya yönetimi.
- Yazar: kendi taslaklarını hazırlama ve editöre gönderme; doğrudan yayın yetkisi yoktur.
- Haber revizyonları, taslak olarak geri alma, çöp kutusu ve işlem günlüğü.
- Yazar biyografileri; RSS, sitemap ve NewsArticle yapılandırılmış verisi.
- RSS 2.0/Atom kaynaklarını elle çekme; bağlantı üzerinden tekilleştirme; yalnızca onay bekleyen kayıt oluşturma.
- AI başlık/spot/dil önerilerini inceleme, alana uygulama ve geri alma; otomatik kayıt veya yayın yoktur.

## Kurulum

PHP 8.2+ ve MySQL gerekir. PDO MySQL, mbstring, DOM/SimpleXML, fileinfo ve OpenSSL uzantılarını etkinleştirin. SQLite tabanlı testler için PDO SQLite da gereklidir.

```sh
git clone https://github.com/onrklcrsln6161-wq/ensonhaber.git
cd ensonhaber
cp config/config.example.php config/config.php
mysql -u root -p < config/database.sql
```

`config/config.php` içindeki ayarları veya PHP işleminin ortam değişkenlerini yapılandırın:

| Değişken | Kullanım |
| --- | --- |
| `DB_HOST` | MySQL sunucusu |
| `DB_NAME` | Veritabanı adı; kurulum SQL'inde `haber_portali` |
| `DB_USER` | Uygulamanın veritabanı kullanıcısı |
| `DB_PASS` | Veritabanı parolası |
| `BASE_URL` | Sitenin tam adresi; sonunda `/` olmamalı |
| `OPENAI_API_KEY` | İsteğe bağlı AI bağlantısı için sunucuda saklanan anahtar |

`.env` dosyası otomatik yüklenmez; ortam değişkenlerini barındırma panelinizde veya PHP işleminde tanımlayın.

```sh
php scripts/migrate.php
```

Bu komut editoryal ve RSS tablolarını oluşturur; tekrar çalıştırılabilir. Var olan üretim veritabanına `database.sql` dosyasını yeniden aktarmayın.

Apache veya Nginx/PHP-FPM ile sunun. `uploads` ve gerekli `config` alt dizinlerine PHP kullanıcısının yazabilmesi gerekir. `config`, `includes`, `scripts`, `tests` ve gizli dosyalar web üzerinden erişilebilir olmamalıdır. Apache için `.htaccess` dosyaları sağlanır; Nginx için aynı kısıtlamaları sunucu yapılandırmasına ekleyin. Yükleme dizininde PHP çalıştırmayın.

Örnek hesap: `admin` / `Admin123!`. Bu hesap yalnızca ilk kurulum içindir; dışarı açmadan önce parolasını değiştirin ve HTTPS yapılandırın.

## Panel adresleri

- Yönetici: `/admin/`
- Editör: `/editor/`
- Yazar: `/author/`

Girişten sonra kullanıcı kendi rolünün paneline yönlendirilir. Rolleri yönetici panelindeki Kullanıcılar bölümünden atayın. Kullanıcı kaldırma, hesabı pasifleştirir ve yazarlık bilgilerini korur.

## Haber botu

Yönetici/Editör → Dünya Haber Botu ekranından kaynak seçip haberleri elle çekin. Yönetici özel HTTPS RSS/Atom kaynakları ekleyebilir. Kaynak ekleme akışı doğrular; haber aktarmaz. Akış sunmayan siteler özel entegrasyon gerektirir.

Bot kaynak başlığını, bağlantısını ve tarihini saklar; tam haber metni veya kaynak görsellerini indirmez. Her çalıştırmada kaynak başına en fazla 30 kayıt inceler. Haberler `pending` durumunda kalır; editör kaynağı kontrol edip Türkçe içeriği hazırladıktan sonra yayımlar.

```sh
php scripts/import-feeds.php --manual
```

CLI, ayarlarda etkin kaynakları tarar. Parametresiz çağrı işlem yapmaz. Proje kendiliğinden zamanlanmış görev kurmaz.

## Yapay zekâ

Sunucuda `OPENAI_API_KEY` tanımlayın. Yönetici → Yapay Zekâ Ayarları ekranında hesabınızın erişebildiği Responses API model kimliğini seçip etkinleştirin.

Yalnızca “Öneri oluştur” düğmesine basıldığında editördeki metin OpenAI API'ye gönderilir. Öneriler ayrı bir alanda gösterilir; kullanıcı uygulamadıkça haber metni değişmez. API kullanımı ayrıca ücret doğurabilir. Anahtar tarayıcıya gönderilmez. `store=false` kullanımı sağlayıcının tüm veri saklama politikalarını kaldırmaz.

[Resmi Responses API dokümantasyonu](https://developers.openai.com/api/reference/cli/resources/responses/methods/create)

## Testler

```sh
php tests/permissions.php
php tests/security.php
php tests/publishing.php
php tests/editorial.php
php tests/feeds.php
php tests/ai.php
```

Bu testler gerçek AI çağrısı veya üretim veritabanı gerektirmez. GitHub Actions, PHP sözdizimi ve bu altı test grubunu çalıştırır.

`tests/http-workflows.cjs`, yalnızca ayrı QA veritabanındaki HTTP akışları içindir. Yerel `config/demo.sqlite` mevcutsa `php tests/prepare-http.php` ile QA kopyasını oluşturun; `config/qa-check.sqlite` veritabanını ve `http://127.0.0.1:8091` adresini kullanan ayrı PHP sunucusunda çalıştırın. Bu test hesabı hazırlayıcısını üretimde kullanmayın. Demo veritabanı depoya dahil değildir.

## Yedekleme ve güncelleme

```sh
php scripts/backup.php
```

SQL yedeği `config/backups` dizinine yazılır. Yedeği aynı veritabanı motorunda boş bir veritabanına geri yükleyin. `uploads` klasörünü ayrıca yedekleyin. Özel MySQL prosedürleri, event ve trigger'lar için sunucunuzun yedekleme araçlarını kullanın.

Güncellemeden önce yedek alın; kodu güncelledikten sonra `php scripts/migrate.php` çalıştırın. Yerel SQLite geri yüklemesi doğrulanmıştır; üretim MySQL geri dönüşü ayrıca test edilmelidir.

## Yayın öncesi sınırlar

- Gerçek yayıncı bilgileri, sosyal hesaplar, politikalar ve yazar biyografileri doldurulmalıdır.
- Piyasa kutusu gerçek zamanlı fiyat hizmeti değildir; manuel ve API değerleri birlikte kullanılır.
- SMTP gönderim entegrasyonu yoktur; AI, anahtar ve erişilebilir model olmadan çalışmaz.
- Üretim MySQL/HTTPS, e-posta ve canlı veri sağlayıcısı yerel testlerle doğrulanmış sayılmaz.
- Haber/görsellerin yayın hakları ve gerçek alan adında Search Console kontrolleri yayıncının sorumluluğundadır.
- GitHub kaynak kodunu barındırır; uygulamanın çalışması için PHP/MySQL sunucusu gerekir.

Çalışan sitenin veritabanı, yedekleri, günlükleri, yerel yapılandırması ve yüklenen medya depoya dahil değildir. `config/database.sql` yalnızca kurulum şeması ve örnek içerik içerir.
